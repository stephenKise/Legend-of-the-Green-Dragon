<?php
// translator ready
// addnews ready
// mail ready
define("OVERRIDE_FORCED_NAV",true);
require_once("common.php");
require_once("lib/systemmail.php");
require_once("lib/sanitize.php");
require_once("lib/http.php");

tlschema("mail");

$superusermessage = getsetting("superuseryommessage","Asking an admin for gems, gold, weapons, armor, or anything else which you have not earned will not be honored.  If you are experiencing problems with the game, please use the 'Petition for Help' link instead of contacting an admin directly.");

$sql = "DELETE FROM " . db_prefix("mail") . " WHERE sent<'".date("Y-m-d H:i:s",strtotime("-".getsetting("oldmail",14)."days"))."'";
db_query($sql);
// really needs to relocated. Performancekiller.
// Ndro with global mail-* invalidation
//


$op = httpget('op');
$id = httpget('id');
if($op=="del"){
	$sql = "DELETE FROM " . db_prefix("mail") . " WHERE msgto='".$session['user']['acctid']."' AND messageid='$id'";
	db_query($sql);
	//<Edo>
	invalidatedatacache("mail-{$session['user']['acctid']}");
	//</Edo>
	header("Location: mail.php");
	exit();
}elseif($op=="process"){
	$msg = httppost('msg');
	if (!is_array($msg) || count($msg)<1){
		$session['message'] = "`\$`bYou cannot delete zero messages!  What does this mean?  You pressed \"Delete Checked\" but there are no messages checked!  What sort of world is this that people press buttons that have no meaning?!?`b`0";
		header("Location: mail.php");
	}else{
		$sql = "DELETE FROM " . db_prefix("mail") . " WHERE msgto='".$session['user']['acctid']."' AND messageid IN ('".join("','",$msg)."')";
		db_query($sql);
		invalidatedatacache("mail-{$session['user']['acctid']}");
		header("Location: mail.php");
		exit();
	}
}elseif ($op=="unread"){
	$sql = "UPDATE " . db_prefix("mail") . " SET seen=0 WHERE msgto='".$session['user']['acctid']."' AND messageid='$id'";
	db_query($sql);
	invalidatedatacache("mail-{$session['user']['acctid']}");
	header("Location: mail.php");
	exit();
}

popup_header("Ye Olde Poste Office");
$inbox = translate_inline("Inbox");
$write = translate_inline("Write");

// Build the initial args array
$args = array();
array_push($args, array("mail.php", $inbox));
array_push($args, array("mail.php?op=address",$write));
// to use this hook,
// just call array_push($args, array("pagename", "functionname"));,
// where "pagename" is the name of the page to forward the user to,
// and "functionname" is the name of the mail function to add
$mailfunctions = modulehook("mailfunctions", $args);

//output_notl("<table width='25%' border='0' cellpadding='0' cellspacing='2'><tr><td><a href='mail.php' class='motd'>$inbox</a></td><td><a href='mail.php?op=address' class='motd'>$write</a></td>", true);
rawoutput("<table width='50%' border='0' cellpadding='0' cellspacing='2'>");
rawoutput("<tr>");
for($i=0;$i<count($mailfunctions);$i++) {
	if (is_array($mailfunctions[$i])) {
		if (count($mailfunctions[$i])==2) {
			$page = $mailfunctions[$i][0];
			$name = $mailfunctions[$i][1]; // already translated
			rawoutput("<td><a href='$page' class='motd'>$name</a></td>");
			// addnav("", $page);
			// No need for addnav since mail function pages are (or should
			// be) outside the page nav system.
		}
	}
}
rawoutput("</tr></table>");
output_notl("`n`n");

if($op=="send"){
	$to = httppost('to');
	$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE login='$to'";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		$row1 = db_fetch_assoc($result);
		$sql = "SELECT count(messageid) AS count FROM " . db_prefix("mail") . " WHERE msgto='".$row1['acctid']."' AND seen=0";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		if ($row['count']>=getsetting("inboxlimit",50)) {
			output("`\$You cannot send that person mail, their mailbox is full!`0`n`n");
		}else{
			$subject =  str_replace("`n","",httppost('subject'));
			$body = str_replace("`n","\n",httppost('body'));
			$body = str_replace("\r\n","\n",$body);
			$body = str_replace("\r","\n",$body);
			$body = addslashes(substr(stripslashes($body),0,(int)getsetting("mailsizelimit",1024)));

			systemmail($row1['acctid'],$subject,$body,$session['user']['acctid']);
			output("Your message was sent!`n");
		}
	}else{
		output("Could not find the recipient, please try again.`n");
	}
	if (httppost("returnto")>""){
		$op="read";
		httpset('op','read');
		$id = httppost('returnto');
		httpset('id',$id);
	}else{
		$op="";
		httpset('op', "");
	}
}

if ($op==""){
	output("`b`iMail Box`i`b");
	if (isset($session['message'])) {
		output($session['message']);
	}
	$session['message']="";
	$sql = "SELECT subject,messageid," . db_prefix("accounts") . ".name,msgfrom,seen,sent FROM " . db_prefix("mail") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid=" . db_prefix("mail") . ".msgfrom WHERE msgto=\"".$session['user']['acctid']."\" ORDER BY sent DESC";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		output_notl("<form action='mail.php?op=process' method='POST'><table>",true);
		for ($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
			if ((int)$row['msgfrom']==0){
				$row['name']=translate_inline("`i`^System`0`i");
				// Only translate the subject if it's an array, ie, it came
				// from the game.
				if (is_array(@unserialize($row['subject']))) {
					$row['subject'] = unserialize($row['subject']);
					$row['subject'] =
						call_user_func_array("sprintf_translate",
								$row['subject']);
				}
			}
			output_notl("<tr>",true);
			output_notl("<td nowrap><input id='checkbox$i' type='checkbox' name='msg[]' value='{$row['messageid']}'><img src='images/".($row['seen']?"old":"new")."scroll.GIF' width='16' height='16' alt='".($row['seen']?"Old":"New")."'></td>",true);
			output_notl("<td><a href='mail.php?op=read&id={$row['messageid']}'>",true);
			if (trim($row['subject'])=="")
				output("`i(No Subject)`i");
			else
				output_notl($row['subject']);
			output_notl("</a></td><td><a href='mail.php?op=read&id={$row['messageid']}'>",true);
			output_notl($row['name']);
			output_notl("</a></td><td><a href='mail.php?op=read&id={$row['messageid']}'>".date("M d, h:i a",strtotime($row['sent']))."</a></td>",true);
			output_notl("</tr>",true);
		}
		output_notl("</table>",true);
		$checkall = htmlentities(translate_inline("Check All"));
		$out="<input type='button' value=\"$checkall\" class='button' onClick='";
		for ($i=$i-1;$i>=0;$i--){
			$out.="document.getElementById(\"checkbox$i\").checked=true;";
		}
		$out.="'>";
		output_notl($out,true);
		$delchecked = htmlentities(translate_inline("Delete Checked"));
		output_notl("<input type='submit' class='button' value=\"$delchecked\">",true);
		output_notl("</form>",true);
	}else{
		output("`iAww, you have no mail, how sad.`i");
	}
	output("`n`n`iYou currently have %s messages in your inbox.`nYou will no longer be able to receive messages from players if you have more than %s unread messages in your inbox.  `nMessages are automatically deleted (read or unread) after %s days.",db_num_rows($result),getsetting('inboxlimit',50),getsetting("oldmail",14));
}elseif ($op=="read"){
	$sql = "SELECT " . db_prefix("mail") . ".*,". db_prefix("accounts"). ".name FROM " . db_prefix("mail") ." LEFT JOIN " . db_prefix("accounts") . " ON ". db_prefix("accounts") . ".acctid=" . db_prefix("mail"). ".msgfrom WHERE msgto=\"".$session['user']['acctid']."\" AND messageid=\"".$id."\"";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		$row = db_fetch_assoc($result);
		if ((int)$row['msgfrom']==0){
			$row['name']=translate_inline("`i`^System`0`i");
			// No translation for subject if it's not an array
			if (is_array(@unserialize($row['subject']))) {
				$row['subject'] = unserialize($row['subject']);
				$row['subject'] =
					call_user_func_array("sprintf_translate", $row['subject']);
			}
			// No translation for body if it's not an array
			if (is_array(@unserialize($row['body']))) {
				$row['body'] = unserialize($row['body']);
				$row['body'] =
					call_user_func_array("sprintf_translate", $row['body']);
			}
		}
		if (!$row['seen']) output("`b`#NEW`b`n");
		else output("`n");
		output("`b`2From:`b `^%s`n",$row['name']);
		output("`b`2Subject:`b `^%s`n",$row['subject']);
		output("`b`2Sent:`b `^%s`n",$row['sent']);
		output_notl("<img src='images/uscroll.GIF' width='182' height='11' alt='' align='center'>`n",true);
		output_notl(str_replace("\n","`n",$row['body']));
		output_notl("`n<img src='images/lscroll.GIF' width='182' height='11' alt='' align='center'>`n",true);

		$sql = "UPDATE " . db_prefix("mail") . " SET seen=1 WHERE  msgto=\"".$session['user']['acctid']."\" AND messageid=\"".$id."\"";
		db_query($sql);

		$reply = translate_inline("Reply");
		$del = translate_inline("Delete");
		$unread = translate_inline("Mark Unread");
		$report = translate_inline("Report to Admin");
		$problem = "Abusive Email Report:\nFrom: {$row['name']}\nSubject: {$row['subject']}\nSent: {$row['sent']}\nID: {$row['messageid']}\nBody:\n{$row['body']}";
		rawoutput("<table width='50%' border='0' cellpadding='0' cellspacing='5'><tr>
			<td><a href='mail.php?op=write&replyto={$row['messageid']}' class='motd'>$reply</a></td>
			<td><a href='mail.php?op=del&id={$row['messageid']}' class='motd'>$del</a></td>
			</tr><tr>
			<td><a href='mail.php?op=unread&id={$row['messageid']}' class='motd'>$unread</a></td>");
		// Don't allow reporting of system messages as abuse.
		if ((int)$row['msgfrom']!=0) {
			rawoutput("<td><a href=\"petition.php?problem=".rawurlencode($problem)."&abuse=yes\" class='motd'>$report</a></td>");
		} else {
			rawoutput("<td align='right'>&nbsp;</td>");
		}
		rawoutput("</tr><tr>");
		$sql = "SELECT messageid FROM ".db_prefix("mail")." WHERE msgto='{$session['user']['acctid']}' AND messageid < '$id' ORDER BY messageid DESC LIMIT 1";
		$result = db_query($sql);
		if (db_num_rows($result)>0){
			$row = db_fetch_assoc($result);
			$pid = $row['messageid'];
		}else{
			$pid = 0;
		}
		$sql = "SELECT messageid FROM ".db_prefix("mail")." WHERE msgto='{$session['user']['acctid']}' AND messageid > '$id' ORDER BY messageid  LIMIT 1";
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
		if ($pid > 0) rawoutput("<a href='mail.php?op=read&id=$pid' class='motd'>".htmlentities($prev)."</a>");
		else rawoutput(htmlentities($prev));
		rawoutput("</td><td nowrap='true'>");
		if ($nid > 0) rawoutput("<a href='mail.php?op=read&id=$nid' class='motd'>".htmlentities($next)."</a>");
		else rawoutput(htmlentities($next));
		rawoutput("</td>");
		rawoutput("</tr></table>");
	}else{
		output("Eek, no such message was found!");
	}
}elseif($op=="address"){
	output_notl("<form action='mail.php?op=write' method='POST'>",true);
	output("`b`2Address:`b`n");
	$to = translate_inline("To: ");
	$search = htmlentities(translate_inline("Search"));
	output_notl("`2$to <input name='to' value=\"".htmlentities(stripslashes(httpget('prepop')))."\"> <input type='submit' class='button' value=\"$search\"></form>",true);
}elseif($op=="write"){
	$subject=httppost('subject');
	$body="";
	$row = "";
	output_notl("<form action='mail.php?op=send' method='POST'>",true);
	$replyto = httpget('replyto');
	if ($replyto!=""){
		$sql = "SELECT ". db_prefix("mail") . ".body," . db_prefix("mail") . ".msgfrom, " . db_prefix("mail") . ".subject,". db_prefix("accounts") . ".login, superuser, " . db_prefix("accounts"). ".name FROM " . db_prefix("mail") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid=" . db_prefix("mail") . ".msgfrom WHERE msgto=\"".$session['user']['acctid']."\" AND messageid=\"".$replyto."\"";
		$result = db_query($sql);
		if (db_num_rows($result)>0){
			$row = db_fetch_assoc($result);
			if ($row['login']=="") {
				output("You cannot reply to a system message.`n");
				$row=array();
			}
		}else{
			output("Eek, no such message was found!`n");
		}
	}
	$to = httpget('to');
	if ($to!=""){
		$sql = "SELECT login,name, superuser FROM " . db_prefix("accounts") . " WHERE login=\"$to\"";
		$result = db_query($sql);
		if (db_num_rows($result)>0){
			$row = db_fetch_assoc($result);
		}else{
			output("Could not find that person.`n");
		}
	}
	if (is_array($row)){
		if (isset($row['subject']) && $row['subject']!=""){
			if ((int)$row['msgfrom']==0){
				$row['name']=translate_inline("`i`^System`0`i");
				// No translation for subject if it's not an array
				if (is_array(@unserialize($row['subject']))) {
					$row['subject'] = unserialize($row['subject']);
					$row['subject'] =
						call_user_func_array("sprintf_translate",
								$row['subject']);
				}
				// No translation for body if it's not an array
				if (is_array(@unserialize($row['body']))) {
					$row['body'] = unserialize($row['body']);
					$row['body'] =
						call_user_func_array("sprintf_translate",
								$row['body']);
				}
			}
			$subject=$row['subject'];
			if (substr($subject,0,4)!="RE: ") $subject="RE: $subject";
		}
		if (isset($row['body']) && $row['body']!=""){
			$body="\n\n---".translate_inline("Original Message")."---\n".$row['body'];
		}
	}
	rawoutput("<input type='hidden' name='returnto' value=\"".htmlentities(stripslashes(httpget("replyto")))."\">");
	$superusers = array();
	if (isset($row['login']) && $row['login']!=""){
		output_notl("<input type='hidden' name='to' id='to' value=\"".htmlentities($row['login'])."\">",true);
		output("`2To: `^%s`n",$row['name']);
		if (($row['superuser'] & SU_GIVES_YOM_WARNING) &&
                !($row['superuser'] & SU_OVERRIDE_YOM_WARNING)) {
			array_push($superusers,$row['login']);
        }
	}else{
		output("`2To: ");
		$to = httppost('to');
		$string="%";
		for ($x=0;$x<strlen($to);$x++){
			$string .= substr($to,$x,1)."%";
		}
		$sql = "SELECT login,name, superuser FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($string)."' AND locked=0 ORDER by login='$to' DESC, name='$to' DESC, login";
		$result = db_query($sql);
		if (db_num_rows($result)==1){
			$row = db_fetch_assoc($result);
			output_notl("<input type='hidden' id='to' name='to' value=\"".htmlentities($row['login'])."\">",true);
			output_notl("`^{$row['name']}`n");
			if (($row['superuser'] & SU_GIVES_YOM_WARNING) &&
                    !($row['superuser'] & SU_OVERRIDE_YOM_WARNING)) {
				array_push($superusers,$row['login']);
            }
		}elseif (db_num_rows($result)==0){
			output("`@No one was found who matches \"%s\".  ",stripslashes($to));
			$try = translate_inline("Please try again");
			output_notl("<a href=\"mail.php?op=address&prepop=".rawurlencode(stripslashes(htmlentities($to)))."\">$try</a>.",true);
			popup_footer();
			exit();
		}else{
			output_notl("<select name='to' id='to' onChange='check_su_warning();'>",true);
			$superusers = array();
			for ($i=0;$i<db_num_rows($result);$i++){
				$row = db_fetch_assoc($result);
				output_notl("<option value=\"".HTMLEntities($row['login'])."\">",true);
				output_notl("%s", full_sanitize($row['name']));
				if (($row['superuser'] & SU_GIVES_YOM_WARNING) &&
                        !($row['superuser'] & SU_OVERRIDE_YOM_WARNING)) {
					array_push($superusers,$row['login']);
                }
			}
			output_notl("</select>`n",true);
		}
	}
	rawoutput("<script language='JavaScript'>
	var superusers = new Array();");
	while (list($key,$val)=each($superusers)){
		rawoutput("	superusers['".addslashes($val)."'] = true;");
	}
	rawoutput("</script>");
	output("`2Subject:");
	rawoutput("<input name='subject' value=\"".HTMLEntities($subject).HTMLEntities(stripslashes(httpget('subject')))."\"><br>");
	rawoutput("<div id='warning' style='visibility: hidden; display: none;'>");
	output("`2Notice: `^$superusermessage`n");
	rawoutput("</div>");
	output("`2Body:`n");
	rawoutput("<textarea name='body' id='textarea' class='input' cols='60' rows='9' onKeyUp='sizeCount(this);'>".HTMLEntities($body).HTMLEntities(stripslashes(httpget('body')))."</textarea><br>");
	$send = translate_inline("Send");
	rawoutput("<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td><input type='submit' class='button' value='$send'></td><td align='right'><div id='sizemsg'></div></td></tr></table>");
	output_notl("</form>",true);
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
	<script language='JavaScript'>
		var maxlen = ".getsetting("mailsizelimit",1024).";
		function sizeCount(box){
			var len = box.value.length;
			var msg = '';
			if (len <= maxlen){
				msg = '$usize1'+(maxlen-len)+'$usize2';
			}else{
				msg = '$osize1'+(len-maxlen)+'$osize2';
			}
			document.getElementById('sizemsg').innerHTML = msg;
		}
		sizeCount(document.getElementById('textarea'));

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
}
popup_footer();
?>
