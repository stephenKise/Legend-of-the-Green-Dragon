<?php
// mail ready
// addnews ready
// translator ready
ob_start();
set_error_handler("payment_error");
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");

tlschema("payment");

// read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';

$post = httpallpost();
reset($post);
foreach ($post as $key => $value) {
	$value = urlencode(stripslashes($value));
	$req .= "&$key=$value";
}

// post back to PayPal system to validate
$header = "";
$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
$fp = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30);

// assign posted variables to local variables
$item_name = httppost('item_name');
$item_number = httppost('item_number');
$payment_status = httppost('payment_status');
$payment_amount = httppost('mc_gross');
$payment_currency = httppost('mc_currency');
$txn_id = httppost('txn_id');
$receiver_email = httppost('receiver_email');
$payer_email = httppost('payer_email');
$payment_fee = httppost('mc_fee');

$response='';
if (!$fp) {
	// HTTP ERROR
	payment_error(E_ERROR,"Unable to open socket to verify payment",__FILE__,__LINE__);
} else {
	fputs ($fp, $header . $req);
	while (!feof($fp)) {
		$res = fgets ($fp, 1024);
		$response .= $res;

		if (strcmp ($res, "VERIFIED") == 0) {
			// check the payment_status is Completed
			// check that txn_id has not been previously processed
			// check that receiver_email is your Primary PayPal email
			// check that payment_amount/payment_currency are correct
			// process payment
			if ($payment_status=="Completed" || $payment_status == 'Refunded'){
				if ($payment_status == 'Refunded'){
					//sanitize the data to look like a completed transaction
					$payment_amount = $mc_gross;
					$payment_fee = 0;
					$txn_type = 'refund';
				}
				$sql = "SELECT * FROM " . db_prefix("paylog") . " WHERE txnid='{$txn_id}'";
				$result = db_query($sql);
				if (db_num_rows($result)==1){
					$emsg .= "Already logged this transaction ID ($txn_id)\n";
					payment_error(E_ERROR,$emsg,__FILE__,__LINE__);
				}
				if (($receiver_email != "logd@mightye.org") &&
					($receiver_email != getsetting("paypalemail", ""))) {
					$emsg = "This payment isn't to me!  It's to $receiver_email.\n";
					payment_error(E_WARNING,$emsg,__FILE__,__LINE__);
				}
				writelog($response);

			}else{
				payment_error(E_ERROR,"Payment Status isn't 'Completed' it's '$payment_status'",__FILE__,__LINE__);
			}
		}
		else if (strcmp ($res, "INVALID") == 0) {
			// log for manual investigation
			payment_error(E_ERROR,"Payment Status is 'INVALID'!\n\nPOST data:`n".serialize($_POST),__FILE__,__LINE__);
		}
	}
	fclose ($fp);
}

function writelog($response){
	global $post;
	global $item_name, $item_number, $payment_status, $payment_amount;
	global $payment_currency, $txn_id, $receiver_email, $payer_email;
	global $payment_fee,$txn_type;
	$match = array();
	preg_match("'([^:]*):([^/])*'",$item_number,$match);
	if ($match[1]>""){
		$match[1] = addslashes($match[1]);
		$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE login='{$match[1]}'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		$acctid = $row['acctid'];
		if ($acctid>0){
			$donation = $payment_amount;
			// if it's a reversal, it'll only post back to us the amount
			// we received back, with out counting the fees, which we
			// receive under a different transaction, but get no
			// notification for.
			if ($txn_type =="reversal") $donation -= $payment_fee;

			$hookresult = modulehook("donation_adjustments",array("points"=>$donation*100,"amount"=>$donation,"acctid"=>$acctid,"messages"=>array()));
			$hookresult['points'] = round($hookresult['points']);

			$sql = "UPDATE " . db_prefix("accounts") . " SET donation = donation + '{$hookresult['points']}' WHERE acctid=$acctid";

			$result = db_query($sql);
			debuglog("Received donator points for donating -- Credited Automatically",false,$acctid,"donation",$hookresult['points'],false);
			if (!is_array($hookresult['messages'])){
				$hookresult['messages'] = array($hookresult['messages']);
			}
			foreach ($hookresult['messages'] as $id=>$message){
				debuglog($message,false,$acctid,"donation",0,false);
			}
			if (db_affected_rows()>0) $processed = 1;
			modulehook("donation", array("id"=>$acctid, "amt"=>$donation*100, "manual"=>false));
		}
	}
	$sql = "
		INSERT INTO " . db_prefix("paylog") . " (
			info,
			response,
			txnid,
			amount,
			name,
			acctid,
			processed,
			filed,
			txfee,
			processdate
		)VALUES (
			'".addslashes(serialize($post))."',
			'".addslashes($response)."',
			'$txn_id',
			'$payment_amount',
			'{$match[1]}',
			".(int)$acctid.",
			".(int)$processed.",
			0,
			'$payment_fee',
			'".date("Y-m-d H:i:s")."'
		)";
	db_query($sql);
	$err = db_error();
	if ($err) {
		payment_error(E_ERROR,"SQL: $sql\nERR: $err", __FILE__,__LINE__);
	}
}

function payment_error($errno, $errstr, $errfile, $errline){
	global $payment_errors;
	if (!is_int($errno) || (is_int($errno) && ($errno & error_reporting()))) {
		$payment_errors.="Error $errno: $errstr in $errfile on $errline\n";
	}
}

$adminEmail = getsetting("gameadminemail", "postmaster@localhost.com");
if ($payment_errors>"") {
	$subj = translate_mail("Payment Error",0);
	// $payment_errors not translated
	ob_start();
	echo "<b>GET:</b><pre>";
	reset($_GET);
	var_dump($_GET);
	echo "</pre><b>POST:</b><pre>";
	reset($_POST);
	var_dump($_POST);
	echo "</pre><b>SERVER:</b><pre>";
	reset($_SERVER);
	var_dump($_SERVER);
	echo "</pre>";
	$contents = ob_get_contents();
	ob_end_clean();
	$payment_errors .= "<hr>".$contents;

	mail($adminEmail,$subj,$payment_errors."<hr>","From: " . getsetting("gameadminemail", "postmaster@localhost.com"));
}
$output = ob_get_contents();
if ($output > ""){
	if ($adminEmail == "") $adminEmail = "trash@mightye.org";
	echo "<b>GET:</b><pre>";
	reset($_GET);
	var_dump($_GET);
	echo "</pre><b>POST:</b><pre>";
	reset($_POST);
	var_dump($_POST);
	echo "</pre><b>SERVER:</b><pre>";
	reset($_SERVER);
	var_dump($_SERVER);
	echo "</pre>";
	mail($adminEmail,"Serious LoGD Payment Problems on {$_SERVER['HTTP_HOST']}",ob_get_contents(),"Content-Type: text/html");
}
ob_end_clean();
?>