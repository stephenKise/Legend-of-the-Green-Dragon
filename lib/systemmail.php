<?php
// translator ready
// addnews ready
// mail ready
require_once("lib/is_email.php");
require_once("lib/safeescape.php");
require_once("lib/sanitize.php");

function systemmail($to,$subject,$body,$from=0,$noemail=false){
	global $session;
	$sql = "SELECT prefs,emailaddress FROM " . db_prefix("accounts") . " WHERE acctid='$to'";
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	db_free_result($result);
	$prefs = unserialize($row['prefs']);
	$serialized=0;
	if ($from==0){
		if (is_array($subject)){
			$subject = serialize($subject);
			$serialized=1;
		}
		if (is_array($body)){
			$body = serialize($body);
			$serialized+=2;
		}
		$subject = safeescape($subject);
		$body = safeescape($body);
	}else{
		$subject = safeescape($subject);
		$subject=str_replace("\n","",$subject);
		$subject=str_replace("`n","",$subject);
		$body = safeescape($body);
		if ((isset($prefs['dirtyemail']) && $prefs['dirtyemail']) || $from==0){
		}else{
			$subject=soap($subject,false,"mail");
			$body=soap($body,false,"mail");
		}
	}

	$sql = "INSERT INTO " . db_prefix("mail") . " (msgfrom,msgto,subject,body,sent,originator) VALUES ('".$from."','".(int)$to."','$subject','$body','".date("Y-m-d H:i:s")."', ".($session['user']['acctid']).")";
	db_query($sql);
	invalidatedatacache("mail-$to");
	$email=false;
	if (isset($prefs['emailonmail']) && $prefs['emailonmail'] && $from>0){
		$email=true;
	}elseif(isset($prefs['emailonmail']) && $prefs['emailonmail'] &&
			$from==0 && isset($prefs['systemmail']) && $prefs['systemmail']){
		$email=true;
	}
	$emailadd = "";
	if (isset($row['emailaddress'])) $emailadd = $row['emailaddress'];

	if (!is_email($emailadd)) $email=false;
	if ($email && !$noemail){
		if ($serialized&2){
			$body = unserialize(stripslashes($body));
			$body = translate_mail($body,$to);
		}
		if ($serialized&1){
			$subject = unserialize(stripslashes($subject));
			$subject = translate_mail($subject,$to);
		}

		$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$from'";
		$result = db_query($sql);
		$row1=db_fetch_assoc($result);
		db_free_result($result);
		if ($row1['name']!="")
			$fromline=full_sanitize($row1['name']);
		else
			$fromline=translate_inline("The Green Dragon","mail");

		$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$to'";
		$result = db_query($sql);
		$row1=db_fetch_assoc($result);
		db_free_result($result);
		$toline = full_sanitize($row1['name']);

		// We've inserted it into the database, so.. strip out any formatting
		// codes from the actual email we send out... they make things
		// unreadable
		$body = preg_replace("'[`]n'", "\n", $body);
		$body = full_sanitize($body);
		$subject = htmlentities($subject, ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
		$mailsubj = translate_mail(array("New LoGD Mail (%s)", $subject),$to);
		$mailbody = translate_mail(array("You have received new mail on LoGD at http://%s`n`n"
			."-=-=-=-=-=-=-=-=-=-=-=-=-=-`n"
			."From: %s`n"
			."To: %s`n"
			."Subject: %s`n"
			."Body: `n%s`n"
			."-=-=-=-=-=-=-=-=-=-=-=-=-=-"
			."`nDo not respond directly to this email, it was sent from the game email address, and not the email address of the person who sent you the "
			."message.  If you wish to respond, log into Legend of the Green Dragon at http://%s .`n`n"
			."You may turn off these alerts in your preferences page, available from the village square.",
			$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']),
			$fromline,
			$toline,
			full_sanitize(stripslashes($subject)),
			stripslashes($body),
			$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])
		),$to);
		mail($row['emailaddress'],$mailsubj,str_replace("`n","\n",$mailbody),"From: ".getsetting("gameadminemail","postmaster@localhost"));
	}
	invalidatedatacache("mail-$to");
}

?>
