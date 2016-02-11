<?php
$mail = db_prefix('mail');
$accounts = db_prefix('accounts');
$sql = "SELECT $mail.*, $accounts.name FROM $mail LEFT JOIN $accounts ON $accounts.acctid=$mail.msgfrom WHERE msgto=\"".$session['user']['acctid']."\" AND messageid=\"".$id."\"";
$result = db_query($sql);
if (db_num_rows($result)>0){
	$row = db_fetch_assoc($result);
	if ($row['msgfrom']==0  || !is_numeric($row['msgfrom'])){
		if ($row['msgfrom'] == 0 && is_numeric($row['msgfrom'])) {
			$row['name']=translate_inline("`i`^System`0`i");
		} else {
			$row['name']=$row['msgfrom'];
		}
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
	if (!$row['seen']) {
		output("`b`#NEW`b`n");
	}else{
		output("`n");
	}
	output("`b`2From:`b `^%s`n",$row['name']);
	output("`b`2Subject:`b `^%s`n",$row['subject']);
	output("`b`2Sent:`b `^%s`n",$row['sent']);
	output_notl("<img src='images/uscroll.GIF' width='182px' height='11px' alt='' align='center'>`n",true);
	output_notl(str_replace("\n","`n",$row['body']));
	output_notl("`n<img src='images/lscroll.GIF' width='182px' height='11px' alt='' align='center'>`n",true);
	$sql = "UPDATE " . db_prefix("mail") . " SET seen=1 WHERE  msgto=\"".$session['user']['acctid']."\" AND messageid=\"".$id."\"";
	db_query($sql);
	invalidatedatacache("mail-{$session['user']['acctid']}");
	$reply = translate_inline("Reply");
	$del = translate_inline("Delete");
	$unread = translate_inline("Mark Unread");
	$report = translate_inline("Report to Admin");
	$problem = "Abusive Email Report:\nFrom: {$row['name']}\nSubject: {$row['subject']}\nSent: {$row['sent']}\nID: {$row['messageid']}\nBody:\n{$row['body']}";
	rawoutput("<table width='50%' border='0' cellpadding='0' cellspacing='5'><tr>");
	if ($row['msgfrom'] > 0 && is_numeric($row['msgfrom'])) {
		rawoutput("<td><a href='mail.php?op=write&replyto={$row['messageid']}' class='motd'>$reply</a></td>");
	} else {
		rawoutput("<td>&nbsp;</td>");
	}
	rawoutput("<td><a href='mail.php?op=del&id={$row['messageid']}' class='motd'>$del</a></td>
		</tr><tr>
		<td><a href='mail.php?op=unread&id={$row['messageid']}' class='motd'>$unread</a></td>");
	// Don't allow reporting of system messages as abuse.
	if ((int)$row['msgfrom']!=0) {
		rawoutput("<td><a href=\"petition.php?problem=".rawurlencode($problem)."&abuse=yes\" class='motd'>$report</a></td>");
	} else {
		rawoutput("<td>&nbsp;</td>");
	}
	rawoutput("</tr><tr>");
	$sql = "SELECT messageid FROM $mail WHERE msgto='{$session['user']['acctid']}' AND messageid < '$id' ORDER BY messageid DESC LIMIT 1";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		$row = db_fetch_assoc($result);
		$pid = $row['messageid'];
	}else{
		$pid = 0;
	}
	$sql = "SELECT messageid FROM $mail WHERE msgto='{$session['user']['acctid']}' AND messageid > '$id' ORDER BY messageid  LIMIT 1";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		$row = db_fetch_assoc($result);
		$nid = $row['messageid'];
	}else{
		$nid = 0;
	}
	$prev = translate_inline("< Previous");
	$next = translate_inline("Next >");
	rawoutput("<td nowrap='true'>");
	if ($pid > 0) {
		rawoutput("<a href='mail.php?op=read&id=$pid' class='motd'>".htmlentities($prev, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</a>");
	}else{
		rawoutput(htmlentities($prev), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
	}
	rawoutput("</td><td nowrap='true'>");
	if ($nid > 0){
		rawoutput("<a href='mail.php?op=read&id=$nid' class='motd'>".htmlentities($next, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</a>");
	}else{
		rawoutput(htmlentities($next), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
	}
	rawoutput("</td>");
	rawoutput("</tr></table>");
}else{
	output("Eek, no such message was found!");
}
?>