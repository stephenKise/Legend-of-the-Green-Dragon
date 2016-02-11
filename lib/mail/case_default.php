<?php
output("`b`iMail Box`i`b");
if (isset($session['message'])) {
	output($session['message']);
}
$session['message']="";
$mail = db_prefix("mail");
$accounts = db_prefix("accounts");
$sql = "SELECT subject,messageid,$accounts.name,msgfrom,seen,sent FROM $mail LEFT JOIN $accounts ON $accounts.acctid=$mail.msgfrom WHERE msgto=\"".$session['user']['acctid']."\" ORDER BY seen ASC, sent DESC";
$result = db_query($sql);
$db_num_rows = db_num_rows($result);
if ($db_num_rows>0){
	$no_subject = translate_inline("`i(No Subject)`i");
	rawoutput("<form action='mail.php?op=process' method='post'><table>");
	while($row = db_fetch_assoc($result)){
		rawoutput("<tr>");
		rawoutput("<td nowrap><input type='checkbox' name='msg[]' value='{$row['messageid']}'>");
		rawoutput("<img src='images/".($row['seen']?"old":"new")."scroll.GIF' width='16px' height='16px' alt='".($row['seen']?"Old":"New")."'></td>");
		rawoutput("<td>");
		if ($row['msgfrom']==0 || !is_numeric($row['msgfrom'])){
			if ($row['msgfrom'] == 0 && is_numeric($row['msgfrom'])) {
				$row['name']=translate_inline("`i`^System`0`i");
			} else {
				$row['name']=$row['msgfrom'];
			}
			// Only translate the subject if it's an array, ie, it came from the game.
			$row_subject = @unserialize($row['subject']);
			if ($row_subject !== false) {
				$row['subject'] = call_user_func_array("sprintf_translate", $row_subject);
			} else {
         			$row['subject'] = translate_inline($row['subject']);
        		}
		}
		// In one line so the Translator doesn't screw the Html up
		output_notl("<a href='mail.php?op=read&id={$row['messageid']}'>".((trim($row['subject']))?$row['subject']:$no_subject)."</a>", true);
		rawoutput("</td><td><a href='mail.php?op=read&id={$row['messageid']}'>");
		output_notl($row['name']);
		rawoutput("</a></td><td><a href='mail.php?op=read&id={$row['messageid']}'>".date("M d, h:i a",strtotime($row['sent']))."</a></td>");
		rawoutput("</tr>");
	}
	rawoutput("</table>");
	$checkall = htmlentities(translate_inline("Check All"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
	rawoutput("<input type='button' value=\"$checkall\" class='button' onClick='
		var elements = document.getElementsByName(\"msg[]\");
		for(i = 0; i < elements.length; i++) {
			elements[i].checked = true;
		}
	'>");
	$delchecked = htmlentities(translate_inline("Delete Checked"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
	rawoutput("<input type='submit' class='button' value=\"$delchecked\">");
	rawoutput("</form>");
}else{
	output("`iAww, you have no mail, how sad.`i");
}
if (db_num_rows($result) == 1) {
	output("`n`n`iYou currently have 1 message in your inbox.`nYou will no longer be able to receive messages from players if you have more than %s unread messages in your inbox.  `nMessages are automatically deleted (read or unread) after %s days.",getsetting('inboxlimit',50),getsetting("oldmail",14));
} else {
	output("`n`n`iYou currently have %s messages in your inbox.`nYou will no longer be able to receive messages from players if you have more than %s unread messages in your inbox.  `nMessages are automatically deleted (read or unread) after %s days.",db_num_rows($result),getsetting('inboxlimit',50),getsetting("oldmail",14));
}
?>