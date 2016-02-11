<?php
$subject=httppost('subject');
$body="";
$row="";
$replyto = (int)httpget('replyto');
if ($session['user']['superuser'] & SU_IS_GAMEMASTER) {
	$from = httppost('from');
}
if ($replyto!=""){
	$mail = db_prefix("mail");
	$accounts = db_prefix("accounts");
	$sql = "SELECT ".$mail.".sent,".$mail.".body,".$mail.".msgfrom, ".$mail.".subject,".$accounts.".login, ".$accounts.".superuser, ".$accounts.".name FROM ".$mail." LEFT JOIN ".$accounts." ON ".$accounts.".acctid=".$mail.".msgfrom WHERE msgto=\"".$session['user']['acctid']."\" AND messageid=\"".$replyto."\"";
	$result = db_query($sql);
	if ($row = db_fetch_assoc($result)){
		if ($row['login']=="") {
			output("You cannot reply to a system message.`n");
			$row=array();
		}
	}else{
		output("Eek, no such message was found!`n");
	}
}
$to = httpget('to');
if ($to){
	$sql = "SELECT login,name, superuser FROM " . db_prefix("accounts") . " WHERE login=\"$to\"";
	$result = db_query($sql);
	if (!($row = db_fetch_assoc($result))){
		output("Could not find that person.`n");
	}
}
if (is_array($row)){
	if (isset($row['subject']) && $row['subject']){
		if ((int)$row['msgfrom']==0){
			$row['name']=translate_inline("`i`^System`0`i");
			// No translation for subject if it's not an array
			$row_subject = @unserialize($row['subject']);
			if ($row_subject !== false) {
				$row['subject'] = call_user_func_array("sprintf_translate", $row_subject);
			}
			// No translation for body if it's not an array
			$row_body = @unserialize($row['body']);
			if ($row_body !== false) {
				$row['body'] = call_user_func_array("sprintf_translate", $row_body);
			}
		}
		$subject=$row['subject'];
		if (strncmp($subject,"RE: ",4) !== 0 ) {
			$subject="RE: $subject";
		}
	}
	if (isset($row['body']) && $row['body']){
		$body="\n\n---".sprintf_translate(array("Original Message from %s (%s)",sanitize($row['name']),date("Y-m-d H:i:s",strtotime($row['sent']))))."---\n".$row['body'];
	}
}
rawoutput("<form action='mail.php?op=send' method='post'>");
if ($session['user']['superuser'] & SU_IS_GAMEMASTER) {
	rawoutput("<input type='hidden' name='from' value='".htmlentities(stripslashes($from), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."'>");
}
rawoutput("<input type='hidden' name='returnto' value=\"".htmlentities(stripslashes(httpget("replyto")), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">");
$superusers = array();
if (($session['user']['superuser'] & SU_IS_GAMEMASTER) && $from > "") {
	output("`2From: `^%s`n", $from);
}
if (isset($row['login']) && $row['login']!=""){
	output_notl("<input type='hidden' name='to' id='to' value=\"".htmlentities($row['login'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">",true);
	output("`2To: `^%s`n",$row['name']);
	if (($row['superuser'] & SU_GIVES_YOM_WARNING) && !($row['superuser'] & SU_OVERRIDE_YOM_WARNING)) {
		array_push($superusers,$row['login']);
	}
}else{
	output("`2To: ");
	$to = httppost('to');
	$sql = "SELECT login,name,superuser FROM accounts WHERE login = '".addslashes($to)."' AND locked = 0";
	$result = db_query($sql);
	$db_num_rows = db_num_rows($result);
	if($db_num_rows != 1) {
		$string="%";
		$to_len = strlen($to);
		for($x=0; $x < $to_len; ++$x) {
			$string .= $to{$x}."%";
		}
		$sql = "SELECT login,name,superuser FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($string)."' AND locked=0 ORDER by login='$to' DESC, name='$to' DESC, login";
		$result = db_query($sql);
		$db_num_rows = db_num_rows($result);
	}
	if ($db_num_rows==1){
		$row = db_fetch_assoc($result);
		output_notl("<input type='hidden' id='to' name='to' value=\"".htmlentities($row['login'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">",true);
		output_notl("`^{$row['name']}`n");
		if (($row['superuser'] & SU_GIVES_YOM_WARNING) && !($row['superuser'] & SU_OVERRIDE_YOM_WARNING)) {
			array_push($superusers,$row['login']);
		}
	}elseif ($db_num_rows==0){
		output("`\$No one was found who matches \"%s\".`n",stripslashes($to));
		output("`@Please try again.`n");
		httpset('prepop', $to, true);
		rawoutput("</form>");
		require("lib/mail/case_address.php");
		popup_footer();
	}else{
		output_notl("<select name='to' id='to' onchange='check_su_warning();'>",true);
		$superusers = array();
		while($row = db_fetch_assoc($result)) {
			output_notl("<option value=\"".htmlentities($row['login'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">",true);
			require_once("lib/sanitize.php");
			output_notl("%s", full_sanitize($row['name']));
			if (($row['superuser'] & SU_GIVES_YOM_WARNING) && !($row['superuser'] & SU_OVERRIDE_YOM_WARNING)) {
				array_push($superusers,$row['login']);
			}
		}
		output_notl("</select>`n",true);
	}
}
rawoutput("<script type='text/javascript'>var superusers = new Array();");
foreach($superusers as $val) {
	rawoutput("	superusers['".addslashes($val)."'] = true;");
}
rawoutput("</script>");
output("`2Subject:");
rawoutput("<input name='subject' value=\"".htmlentities($subject).htmlentities(stripslashes(httpget('subject')), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"><br>");
rawoutput("<div id='warning' style='visibility: hidden; display: none;'>");
output("`2Notice: `^$superusermessage`n");
rawoutput("</div>");
output("`2Body:`n");
require_once("lib/forms.php");
previewfield("body", "`^", false, false, array("type"=>"textarea", "class"=>"input", "cols"=>"60", "rows"=>"9", "onKeyDown"=>"sizeCount(this);"), htmlentities($body, ENT_COMPAT, getsetting("charset", "ISO-8859-1")).htmlentities(stripslashes(httpget('body')), ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
//rawoutput("<textarea name='body' id='textarea' class='input' cols='60' rows='9' onKeyUp='sizeCount(this);'>".htmlentities($body, ENT_COMPAT, getsetting("charset", "ISO-8859-1")).htmlentities(stripslashes(httpget('body')), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br>");
$send = translate_inline("Send");
rawoutput("<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td><input type='submit' class='button' value='$send'></td><td align='right'><div id='sizemsg'></div></td></tr></table>");
rawoutput("</form>");
$sizemsg = "`#Max message size is `@%s`#, you have `^XX`# characters left.";
$sizemsg = translate_inline($sizemsg);
$sizemsg = sprintf($sizemsg,getsetting("mailsizelimit",1024));
$sizemsgover = "`\$Max message size is `@%s`\$, you are over by `^XX`\$ characters!";
$sizemsgover = translate_inline($sizemsgover);
$sizemsgover = sprintf($sizemsgover,getsetting("mailsizelimit",1024));
$sizemsg = explode("XX",$sizemsg);
$sizemsgover = explode("XX",$sizemsgover);
$usize1 = addslashes("<span>".appoencode($sizemsg[0])."</span>");
$usize2 = addslashes("<span>".appoencode($sizemsg[1])."</span>");
$osize1 = addslashes("<span>".appoencode($sizemsgover[0])."</span>");
$osize2 = addslashes("<span>".appoencode($sizemsgover[1])."</span>");
rawoutput("
<script type='text/javascript'>
	var maxlen = ".getsetting("mailsizelimit",1024).";
	function sizeCount(box){
		if (box==null) return;
		var len = box.value.length;
		var msg = '';
		if (len <= maxlen){
			msg = '$usize1'+(maxlen-len)+'$usize2';
		}else{
			msg = '$osize1'+(len-maxlen)+'$osize2';
		}
		document.getElementById('sizemsg').innerHTML = msg;
	}
	sizeCount(document.getElementById('inputbody'));
		function check_su_warning(){
		var to = document.getElementById('to');
		var warning = document.getElementById('warning');
		if (superusers[to.value]){
			warning.style.visibility = 'visible';
			warning.style.display = 'inline';
		}else{
			warning.style.visibility = 'hidden';
			warning.style.display = 'none';
		}
	}
	check_su_warning();
</script>");
?>
