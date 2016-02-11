<?php
// addnews ready
// mail ready
// translator ready
session_start();
//unset($_SESSION['logdnet']);
//session_register("session");
if (isset($_GET['op']) && $_GET['op']=="register"){
	if (!isset($_SESSION['logdnet']) || !isset($_SESSION['logdnet']['']) ||
			$_SESSION['logdnet']['']==""){
		//register with LoGDnet
		$a = $_GET['a'];
		$c = $_GET['c'];
		$l = $_GET['l'];
		$d = $_GET['d'];
		$e = $_GET['e'];
		$v = $_GET['v'];
		$u = $_GET['u'];
		$url = $u. //central server
			"logdnet.php?". //logdnet script
			"addy=".rawurlencode($a). //server URL
			"&desc=".rawurlencode($d). //server description
			"&version=".rawurlencode($v). //game version
			"&admin=".rawurlencode($e). //admin email
			"&c=".$c. // player count (for my own records, this isn't used
					  // in the sorting mechanism)
			"&v=2".   // LoGDnet version.
			"&l=".$l. // primary language of this server -- you may change
					  // this if it turns out to be inaccurate.
			"";
		require_once("../lib/pullurl.php");
		$info = @pullurl($url);
		if ($info !== false) {
			$info = base64_decode(join("",$info));
			$_SESSION['logdnet'] = unserialize($info);
			$_SESSION['logdnet']['when'] = date("Y-m-d H:i:s");
			$_SESSION['logdnet']['note'] =  "\n// registered with logdnet successfully";
			$_SESSION['logdnet']['note'] .= "\n// ".$url;
		}else{
			$_SESSION['logdnet']['when'] = date("Y-m-d H:i:s");
			$_SESSION['logdnet']['note'] =  "\n// There was trouble registering on logdnet.";
			$_SESSION['logdnet']['note'] .= "\n// ".$url;
		}
	} else {
		$info = true;
	}
	if ($info !== false) {
		require_once("../lib/sanitize.php");
		$o = addslashes($_SESSION['logdnet']['']);
		$o = str_replace("\n\r","\n",$o);
		$o = str_replace("\r","\n",$o);
		$o = str_replace("\n","\\n",$o);
		$refer = "";
		if (isset($_SERVER['HTTP_REFERER'])) {
			$refer = $_SERVER['HTTP_REFERER'];
		}
		if (isset($_SESSION['session']['user'])) {
			echo $_SESSION['logdnet']['note']."\n";
			echo "// At {$_SESSION['logdnet']['when']}\n";
			//require_once("../lib/dbwrapper.php");
			//require_once("../lib/settings.php");
			echo "document.write(\"".sprintf($o,full_sanitize($_SESSION['session']['user']['login']),
						htmlentities($_SESSION['session']['user']['login']).":".$_SERVER['HTTP_HOST'].$refer,ENT_COMPAT,"ISO-8859-1")."\");";
		} else {
			$image = join("",file("paypal1.gif"));
			header("Content-Type: image/gif");
			header("Content-Length: ".strlen($image));
			echo $image;
		}
	} else {
		// We failed to connect to central, just use our local image!
		$image = join("",file("paypal1.gif"));
		header("Content-Type: image/gif");
		header("Content-Length: ".strlen($image));
		echo $image;
	}
}elseif (isset($_SESSION['logdnet'])){
	header("Content-Type: ".$_SESSION['logdnet']['content-type']);
	header("Content-Length: ".strlen($_SESSION['logdnet']['image']));
	echo $_SESSION['logdnet']['image'];
}else{
	$image = join("",file("paypal1.gif"));
	header("Content-Type: image/gif");
	header("Content-Length: ".strlen($image));
	echo $image;
}
?>
