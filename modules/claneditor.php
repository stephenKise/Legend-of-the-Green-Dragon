<?php
// translator ready
// addnews ready
// mail ready
/*
Details:
 * This is a module that allows you to edit clans
 * There are no settings or preferences to set
 * The link will be shown in the Grotto if you can edit users
 * Clans can be created, deleted, and modified
 * You can set Clan Prefrences as you can with mounts and drinks
Version Log:
 v1.1:
 o Bugfixed Confirm link
 v1.2:
 o Bugfixed search to work with logins not name
*/

require_once("lib/nltoappon.php");
require_once("lib/commentary.php");
require_once("lib/systemmail.php");
require_once("lib/sanitize.php");
require_once("lib/http.php");
require_once("lib/superusernav.php");
require_once("lib/showform.php");

function claneditor_getmoduleinfo(){
	$info = array(
		"name"=>"Clan Editor",
		"version"=>"1.2",
		"author"=>"<a href='http://cortalux.tczhost.net' class='colLtGreen'>CortalUX</a>",
		"category"=>"Administrative",
		"download"=>"core_module",
	);
	return $info;
}

function claneditor_install() {
	module_addhook("superuser");
	return true;
}

function claneditor_uninstall() {
	return true;
}

function claneditor_dohook($hookname,$args) {
	global $session;
	switch ($hookname) {
	case "superuser":
		if ($session['user']['superuser'] & SU_EDIT_USERS) {
			addnav("Editors");
			addnav("Clan Editor","runmodule.php?module=claneditor&admin=true");
		}
		break;
	}
	return $args;
}

function claneditor_run(){
	global $session;
	tlschema("claneditor");
	$dt = httpget("dt");
	$op = httpget('op');
	if ($dt!="") {
		$sql = "SELECT * FROM " . db_prefix("clans") . " WHERE clanid='$dt'";
		$result = db_query($sql);
		$claninfo = db_fetch_assoc($result);
		if (db_num_rows($result)==0) {
			$op = "";
		}
	}
	addcommentary();
	$ranks = array(CLAN_APPLICANT=>"`!Applicant`0",CLAN_MEMBER=>"`#Member`0",CLAN_OFFICER=>"`^Officer`0",CLAN_LEADER=>"`&Leader`0", CLAN_FOUNDER=>"`\$Founder");
	$args = modulehook("clanranks", array("ranks"=>$ranks, "clanid"=>$dt));
	$ranks = translate_inline($args['ranks'], "clan");
	superusernav();
	addnav("Clans");
	addnav("List Clans","runmodule.php?module=claneditor&op=list");
	addnav("Create a New Clan","runmodule.php?module=claneditor&op=new");
	if ($op==""||$op=="list"){
		page_header("Clan Listing");
		rawoutput("<table border='0' padding='0'><tr><td>");
		$sql = "SELECT MAX(" . db_prefix("clans") . ".clanid) AS clanid, MAX(clanname) AS clanname,count(" . db_prefix("accounts") . ".acctid) AS c FROM " . db_prefix("clans") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("clans") . ".clanid=" . db_prefix("accounts") . ".clanid AND clanrank>".CLAN_APPLICANT." GROUP BY " . db_prefix("clans") . ".clanid ORDER BY c DESC";
		$result = db_query($sql);
		if (db_num_rows($result)>0){
			output("`%Karissa`7 steps in as if by magic, listing the clans for you.`n");
			output("`@`bList of Clans:`b`0`n`n");
			$v = 0;
			$memb_n = translate_inline("(%s members)");
			$memb_1 = translate_inline("(%s member)");
			rawoutput('<table cellspacing="0" cellpadding="2" align="left">');
			output_notl("<tr class='trhead'><td>%s</td><td>%s</td></tr>",translate_inline("`b`&Name of Clan`b"),translate_inline("`&`iNumber of Members`i"),true);
			for ($i=0;$i<db_num_rows($result);$i++){
				$row = db_fetch_assoc($result);
				if ($row['c']==0){
					$sql = "DELETE FROM " . db_prefix("clans") . " WHERE clanid={$row['clanid']}";
					db_query($sql);
				}else{
					rawoutput('<tr class="' . ($v%2?"trlight":"trdark").'"><td>', true);
					if ($row['c'] == 1) {
						$memb = sprintf($memb_1, $row['c']);
					} else {
						$memb = sprintf($memb_n, $row['c']);
					}
					output_notl("&#149; <a href='runmodule.php?module=claneditor&op=mview&dt=%s'>%s</a></td><td>%s`n",
							$row['clanid'],
							full_sanitize(htmlentities($row['clanname']), ENT_COMPAT, getsetting("charset", "ISO-8859-1")),	$memb, true);
					rawoutput('</td></tr>');
					addnav("","runmodule.php?module=claneditor&op=mview&dt={$row['clanid']}");
					$v++;
				}
			}
			rawoutput("</table>", true);
		}else{
			output("`7There are no clans in the database.`n`c");
		}
		rawoutput("</td></tr><tr><td>");
		output_notl("<br>[<a href='runmodule.php?module=claneditor&op=new'>%s</a>]",translate_inline("Create a New Clan"),true);
		addnav("","runmodule.php?module=claneditor&op=new");
		rawoutput("</td></tr></table>");
		page_footer();
	}elseif ($op=="new"){
		page_header("Clan Creation");
		$apply = httpget('apply');
		if ($apply==1){
			$id = httpget("id");
			$ocn = httppost('clanname');
			$ocs = httppost('clanshort');
			$clanname = stripslashes($ocn);
			$clanname = full_sanitize($clanname);
			$clanname = preg_replace("'[^[:alpha:] \\'-]'","",$clanname);
			$clanname = addslashes($clanname);
			httppostset('clanname', $clanname);
			$clanshort = full_sanitize($ocs);
			$clanshort = preg_replace("'[^[:alpha:]]'","",$clanshort);
			httppostset('clanshort', $clanshort);
			$sql = "SELECT * FROM " . db_prefix("clans") . " WHERE clanname='$clanname'";
			$result = db_query($sql);
			$e = translate_inline("`%Karissa`7 hands you a form to start a clan.");
			$e1 = translate_inline("`%Karissa`7 looks over your form but informs you that your clan name must consist only of letters, spaces, apostrophes, or dashes.  Also, your short name can consist only of letters. She hands you a blank form.");
			$e2 = translate_inline("`%Karissa`7 looks over your form but informs you that you must have at least 5 and no more than 50 characters in your clan's name (and they must consist only of letters, spaces, apostrophes, or dashes), then hands you a blank form.");
			$e3 = translate_inline("`%Karissa`7 looks over your form but informs you that you must have at least 2 and no more than 5 characters in your clan's short name (and they must all be letters), then hands you a blank form.");
			$e4 = translate_inline("`%Karissa`7 looks over your form but informs you that the clan name %s is already taken, and hands you a blank form.");
			$e5 = translate_inline("`%Karissa`7 looks over your form but informs you that the short name %s is already taken, and hands you a blank form.");
			if ($ocs==""&&$ocn==""&&!httppostisset('clanname')&&!httppostisset('clanshort')) {
				output_notl($e);
				clanform();
			}elseif ($clanname!=$ocn || $clanshort!=$ocs){
				output_notl($e1);
				clanform();
			}elseif (strlen($clanname)<5 || strlen($clanname)>50){
				output_notl($e2);
				clanform();
			}elseif (strlen($clanshort)<2 || strlen($clanshort)>5){
				output_notl($e3);
				clanform();
			}elseif (db_num_rows($result)>0){
				output_notl($e4,stripslashes($clanname));
				clanform();
			}else{
				$sql = "SELECT * FROM " . db_prefix("clans") . " WHERE clanshort='$clanshort'";
				$result = db_query($sql);
				if (db_num_rows($result)>0){
					output_notl($e5,stripslashes($clanshort));
					clanform();
				}else{
					$sql = "INSERT INTO " . db_prefix("clans") . " (clanname,clanshort) VALUES ('$clanname','$clanshort')";
					db_query($sql);
					$clid = db_insert_id();
					$sql = "UPDATE " . db_prefix("accounts") . " SET clanid='$clid',clanrank='".CLAN_LEADER."' WHERE acctid='$id'";
					db_query($sql);
					$subj = "New Clan!";
					$msg = array("%s`0`^ has made you a new clan!",$session['user']['name']);
					systemmail($id,$subj,$msg);
					output("`%Karissa`7 looks over your form, and finding that everything seems to be in order, she takes your fees, stamps the form \"`\$APPROVED`7\" and files it in a drawer.`n`n");
					output("Congratulations, you've created a new clan named %s!",stripslashes($clanname));
				}
			}
		}elseif($apply==0){
			clanuserform();
		}else{
			output("`7You teleport to the Clan Hall...");
			output("`7You approach `%Karissa`7 and mention that you would like to start a new clan.");
			output("She tells you that there are three requirements to starting a clan.");
			output("First, you have to decide on a full name for your clan.");
			output("Second, you have to decide on an abbreviation for your clan.");
			output("Third you have to decide on the person that should run the clan.");
			$e = translate_inline("`n`n\"`5If you're ok with these three requirements, please fill out the following form,`7\" she says, handing you a sheet of paper.");
			output_notl($e);
			clanuserform();
		}
	}else{
		if ($op!="deleteclan") {
			page_header("The Clan of %s", full_sanitize($claninfo['clanname']));
			output("`n`c`^`bThe Clan of %s`b`c`n`n", full_sanitize($claninfo['clanname']));
		} else {
			page_header("Clan Deletion");
		}
		if ($op=="mview"){
			$sql = "SELECT name FROM " . db_prefix("accounts")  . " WHERE acctid={$claninfo['motdauthor']}";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$motdauthname = $row['name'];

			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid={$claninfo['descauthor']}";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$descauthname = $row['name'];
			output("`&`bCurrent MoTD:`b `#by %s`2`n",$motdauthname);
			output_notl(nltoappon($claninfo['clanmotd'])."`n`n");

			commentdisplay("", "clan-{$claninfo['clanid']}","Speak into their Clan",25,"projects");
			output_notl("`n`n");
			modulehook("collapse{", array("name"=>"collapsedesc"));
			output("`&`bCurrent Description:`b `#by %s`2`n",$descauthname);
			output_notl(nltoappon($claninfo['clandesc'])."`n");
			modulehook("}collapse");
			$sql = "SELECT count(*) AS c, clanrank FROM " . db_prefix("accounts") . " WHERE clanid={$claninfo['clanid']} GROUP BY clanrank DESC";
			$result = db_query($sql);
			// begin collapse
			modulehook("collapse{", array("name"=>"clanmemberdet"));
			output("`n`bMembership Details:`b`n");
			$leaders = 0;
			while ($row = db_fetch_assoc($result)){
				output_notl($ranks[$row['clanrank']].": ".$row['c']."`n");
				if ($row['clanrank']>=CLAN_OFFICER) $leaders += $row['c'];
			}
			output("`n");
			$noleader = translate_inline("`^There is currently no leader!  Promoting %s`^ to leader as they are the highest ranking member (or oldest member in the event of a tie).`n`n");
			if ($leaders==0){
				//There's no leader here, probably because the leader's account
				//expired.
				$sql = "SELECT name,acctid,clanrank FROM " . db_prefix("accounts") . " WHERE clanid=$dt ORDER BY clanrank DESC, clanjoindate";
				$result = db_query($sql);
				$row = db_fetch_assoc($result);
				$sql = "UPDATE " . db_prefix("accounts") . " SET clanrank=".CLAN_LEADER." WHERE acctid='".$row['acctid']."'";
				db_query($sql);
				output_notl($noleader,$row['name']);
			}
			// end collapse
			modulehook("}collapse");
		}elseif ($op=="deleteclan"){
			if (httpget("sop")=="yes") {
				//notify users of the deletion of the clan
				$sql = "SELECT acctid FROM " . db_prefix("accounts")  . " WHERE clanid=$dt";
				$result = db_query($sql);
				$subj = array("Deletion of %s",$claninfo['clanname']);
				$msg = array("The clan you were in, %s, has closed its doors.\nSorry for any inconvenience.",$claninfo['clanname']);
				while ($row = db_fetch_assoc($result)){
					systemmail($row['acctid'],$subj,$msg);
				}
				//change the clan if a user is in this clan
				$sql = "UPDATE ".db_prefix("accounts")." SET clanid=0,clanrank=".CLAN_APPLICANT.",clanjoindate='0000-00-00 00:00:00' WHERE clanid=$dt";
				db_query($sql);
				//change the current users clan if this user was in that clan
				if ($session['user']['clanid']==$dt) {
					$session['user']['clanid']=0;
					$session['user']['clanrank']=CLAN_APPLICANT;
					$session['user']['clanjoindate']='0000-00-00 00:00:00';
				}
				//drop the clan.
				$sql = "DELETE FROM " . db_prefix("clans") . " WHERE clanid=$dt";
				db_query($sql);
				module_delete_objprefs('clans', $dt);
				$op = "";
				httpset("op", "");
				unset($claninfo);
				$dt = "";
				output("That clan has been wiped.`n");
				output("`@Users within the clan have been notified.");
			} else {
				output("`%`c`bAre you SURE you want to delete this clan?`b`c`n");
				$dc = translate_inline("Delete this clan? Are you sure!");
				rawoutput("[<a href='runmodule.php?module=claneditor&op=deleteclan&sop=yes&dt=$dt' onClick='return confirm(\"$dc\");'>$dc</a>]");
				addnav("","runmodule.php?module=claneditor&op=deleteclan&sop=yes&dt=$dt");
			}
		}elseif ($op=="editmodule"||$op=="editmodulesave"){
			$mdule = httpget("mdule");
			if ($op=="editmodulesave") {
				// Save module prefs
				$post = httpallpost();
				reset($post);
				while(list($key, $val) = each($post)) {
					set_module_objpref("clans", $dt, $key, $val, $mdule);
				}
				output("`^Saved!`0`n");
			}
			rawoutput("<form action='runmodule.php?module=claneditor&op=editmodulesave&dt=$dt&mdule=$mdule' method='POST'>");
			module_objpref_edit("clans", $mdule, $dt);
			rawoutput("</form>");
			addnav("","runmodule.php?module=claneditor&op=editmodulesave&dt=$dt&mdule=$mdule");
		}elseif ($op=="updinfo"){
			page_header("Update Clan Information");
			$clanmotd = substr(httppost('clanmotd'), 0, 4096);
			if (httppostisset('clanmotd') && $clanmotd!=$claninfo['clanmotd']){
				if ($clanmotd=="") {
					$mauthor=0;
				} else {
					$mauthor=$session['user']['acctid'];
				}
				$sql = "UPDATE " . db_prefix("clans") . " SET clanmotd='$clanmotd',motdauthor=$mauthor WHERE clanid={$claninfo['clanid']}";
				db_query($sql);
				invalidatedatacache("clandata-{$claninfo['clanid']}");
				$claninfo['clanmotd']=stripslashes($clanmotd);
				output("Updating MoTD`n");
				$claninfo['motdauthor']=$mauthor;
			}
			$clandesc = httppost('clandesc');
			if (httppostisset('clandesc') && $clandesc!=$claninfo['clandesc']){
				if ($clandesc=="") {
					$claninfo['descauthor']=0;
					$dauthor=0;
				} else {
					$dauthor=$session['user']['acctid'];
				}
				$sql = "UPDATE " . db_prefix("clans") . " SET clandesc='".addslashes(substr(stripslashes($clandesc),0,4096))."',descauthor=$dauthor WHERE clanid={$claninfo['clanid']}";
				db_query($sql);
				invalidatedatacache("clandata-{$claninfo['clanid']}");
				output("Updating description`n");
				$claninfo['clandesc']=stripslashes($clandesc);
				$claninfo['descauthor']=$session['user']['acctid'];
			}
			$customsay = httppost('customsay');
			if (httppostisset('customsay') && $customsay!=$claninfo['customsay']){
				$sql = "UPDATE " . db_prefix("clans") . " SET customsay='$customsay' WHERE clanid={$claninfo['clanid']}";
				db_query($sql);
				invalidatedatacache("clandata-{$claninfo['clanid']}");
				output("Updating custom say line`n");
				$claninfo['customsay']=stripslashes($customsay);
			}
			$clanname = httppost('clanname');
			if ($clanname) $clanname = full_sanitize($clanname);
			$clanshort = httppost('clanshort');
			if ($clanshort) $clanshort = full_sanitize($clanshort);
			if (httppostisset('clanname') && $clanname!=$claninfo['clanname']){
				$sql = "UPDATE " . db_prefix("clans") . " SET clanname='$clanname' WHERE clanid={$claninfo['clanid']}";
				output("Updating the clan name`n");
				db_query($sql);
				invalidatedatacache("clandata-$detail");
				$claninfo['clanname']=$clanname;
			}
			if (httppostisset('clanshort') && $clanshort!=$claninfo['clanshort']){
				$sql = "UPDATE " . db_prefix("clans") . " SET clanshort='$clanshort' WHERE clanid={$claninfo['clanid']}";
				output("Updating the short clan name`n");
				db_query($sql);
				invalidatedatacache("clandata-$detail");
				$claninfo['clanshort']=$clanshort;
			}
			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid={$claninfo['motdauthor']}";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$motdauthname = $row['name'];
			$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid={$claninfo['descauthor']}";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$descauthname = $row['name'];
			output("`&`bCurrent MoTD:`b `#by %s`2`n",$motdauthname);
			output_notl(nltoappon($claninfo['clanmotd'])."`n");
			output("`&`bCurrent Description:`b `#by %s`2`n",$descauthname);
			output_notl(nltoappon($claninfo['clandesc'])."`n");
			rawoutput("<form action='runmodule.php?module=claneditor&op=updinfo&dt=$dt' method='POST'>");
			addnav("","runmodule.php?module=claneditor&op=updinfo&dt=$dt");
			output("`&`bMoTD:`b `7(4096 chars)`n");
			rawoutput("<textarea name='clanmotd' cols='50' rows='10'>".htmlentities($claninfo['clanmotd'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br>");
			output("`bRenaming:`b`n");
			output("`iLong Name:`i ");
			rawoutput("<input name='clanname' value=\"".htmlentities($claninfo['clanname'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" maxlength=50 size=50>");
			output("`n`iShort Name:`i ");
			rawoutput("<input name='clanshort' value=\"".htmlentities($claninfo['clanshort'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" maxlength=5 size=5>");
			output_notl("`n");
			output("`n`&`bDescription:`b `7(4096 chars)`n");
			if (httppost('block')>""){
				$blockdesc = translate_inline("Description blocked for inappropriate usage.");
				$sql = "UPDATE " . db_prefix("clans") . " SET descauthor=4294967295, clandesc='$blockdesc' where clanid='".$claninfo['clanid']."'";
				output("Blocking public description`n");
				db_query($sql);
				invalidatedatacache("clandata-".$claninfo['clanid']."");
				$claninfo['blockdesc']="";
				$claninfo['descauthor']=4294967295;
			}elseif (httppost('unblock')>""){
				$sql = "UPDATE " . db_prefix("clans") . " SET descauthor=0, clandesc='' where clanid='".$claninfo['clanid']."'";
				output("Unblocking public description`n");
				db_query($sql);
				invalidatedatacache("clandata-".$claninfo['clanid']."");
				$claninfo['clandesc']="";
				$claninfo['descauthor']=0;
			}
			$blocked = translate_inline("The clan has been blocked from posting a description.`n");
			if ($claninfo['descauthor']==4294967295){
				output_notl("`b`%".$blocked."`b");
			}
			rawoutput("<textarea name='clandesc' cols='50' rows='10'>".htmlentities($claninfo['clandesc'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br>");
			output("`n`&`bCustom Talk Line`b `7(blank means \"says\" -- 15 chars max)`n");
			rawoutput("<input name='customsay' value=\"".htmlentities($claninfo['customsay'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" maxlength=\"15\"><br/>");
			$save = translate_inline("Save");
			rawoutput("<input type='submit' class='button' value=\"$save\">");
			$snu = htmlentities(translate_inline("Save & Unblock public description"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
			$snb = htmlentities(translate_inline("Save & Block public description"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
			if ($claninfo['descauthor']=="4294967295")
				rawoutput("<input type='submit' name='unblock' value=\"$snu\" class='button'>");
			else
			rawoutput("<input type='submit' name='block' value=\"$snb\" class='button'>");
			rawoutput("</form>");
		}elseif ($op=="membership"){
			output("This is the clans current membership:`n");
			$setrank = httpget('setrank');
			$who = httpget('who');
			if ($setrank>""){
				$sql = "UPDATE " . db_prefix("accounts") . " SET clanrank=$setrank WHERE login='$who'";
				db_query($sql);
			}
			$remove = httpget('remove');
			if ($remove>""){
				$sql = "UPDATE " . db_prefix("accounts") . " SET clanrank=".CLAN_APPLICANT.",clanid=0,clanjoindate='0000-00-00 00:00:00' WHERE login='$remove' AND clanrank<={$session['user']['clanrank']}";
				db_query($sql);
				//delete unread application emails from this user.
				//breaks if the applicant has had their name changed via
				//dragon kill, superuser edit, or lodge color change
				$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE login='$remove'";
				$row = db_fetch_assoc(db_query($sql));
				$subj = serialize(array($apply_short, $row['name']));
				$sql = "DELETE FROM " . db_prefix("mail") . " WHERE msgfrom=0 AND seen=0 AND subject='$subj'";
				db_query($sql);
			}
			$sql = "SELECT acctid,name,login,clanrank,laston,clanjoindate,dragonkills,level FROM " . db_prefix("accounts") . " WHERE clanid={$claninfo['clanid']} ORDER BY clanrank DESC,clanjoindate";
			$result = db_query($sql);
			rawoutput("<table border='0' cellpadding='2' cellspacing='0'>");
			$rank = translate_inline("Rank");
			$name = translate_inline("Name");
			$lev = translate_inline("Level");
			$dk = translate_inline("Dragon Kills");
			$jd = translate_inline("Join Date");
			$lo = translate_inline("Last On");
			$ops = translate_inline("Operations");
			$promote = translate_inline("Promote");
			$demote = translate_inline("Demote");
			$remove = translate_inline("Remove From The Clan");
			$confirm = translate_inline("Are you sure you wish to remove this member from the clan?");
			rawoutput("<tr class='trhead'><td>$rank</td><td>$name</td><td>$lev</td><td>$dk</td><td>$jd</td><td>$lo</td>".($session['user']['clanrank']>CLAN_MEMBER?"<td>$ops</td>":"")."</tr>",true);
			$i=0;
			$tot = 0;
			while ($row=db_fetch_assoc($result)){
				$i++;
				$tot += $row['dragonkills'];
				rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
				rawoutput("<td>");
				output_notl($ranks[$row['clanrank']]);
				rawoutput("</td><td>");
				$link = "bio.php?char=".$row['acctid']."&ret=".urlencode($_SERVER['REQUEST_URI']);
				rawoutput("<a href='$link'>", true);
				addnav("",$link);
				output_notl("`&%s`0", $row['name']);
				rawoutput("</a>");
				rawoutput("</td><td align='center'>");
				output_notl("`^%s`0",$row['level']);
				rawoutput("</td><td align='center'>");
				output_notl("`\$%s`0",$row['dragonkills']);
				rawoutput("</td><td>");
				output_notl("`3%s`0",$row['clanjoindate']);
				rawoutput("</td><td>");
				output_notl("`#%s`0",reltime(strtotime($row['laston'])));
				rawoutput("</td>");
				rawoutput("<td>");
				rawoutput("[ <a href='runmodule.php?module=claneditor&op=membership&dt=$dt&setrank=".($row['clanrank']+1)."&who=".rawurlencode($row['login'])."'>$promote</a> | ");
				addnav("","runmodule.php?module=claneditor&op=membership&dt=$dt&setrank=".($row['clanrank']+1)."&who=".rawurlencode($row['login']));
				rawoutput("<a href='runmodule.php?module=claneditor&op=membership&dt=$dt&setrank=".($row['clanrank']-1)."&who=".rawurlencode($row['login'])."'>$demote</a> | ");
				addnav("","runmodule.php?module=claneditor&op=membership&dt=$dt&setrank=".($row['clanrank']-1)."&who=".rawurlencode($row['login']));
				rawoutput("<a href='runmodule.php?module=claneditor&op=membership&dt=$dt&remove=".rawurlencode($row['login'])."' onClick=\"return confirm('$confirm');\">$remove</a> ]");
				addnav("","runmodule.php?module=claneditor&op=membership&dt=$dt&remove=".rawurlencode($row['login']));
				rawoutput("</td>");
				rawoutput("</tr>");
			}
			rawoutput("</table>");
			output("`n`n`^This clan has a total of `\$%s`^ dragon kills.",$tot);
		}
		if ($dt!=""&&isset($claninfo)) {
			addnav("Clan Options");
			addnav("Main View","runmodule.php?module=claneditor&op=mview&dt=$dt");
			addnav("Update Clan Information","runmodule.php?module=claneditor&op=updinfo&dt=$dt");
			addnav("Delete this Clan","runmodule.php?module=claneditor&op=deleteclan&dt=$dt");
			addnav("Update Members","runmodule.php?module=claneditor&op=membership&dt=$dt");
			addnav("Module Prefs");
			module_editor_navs("prefs-clans","runmodule.php?module=claneditor&op=editmodule&dt=".$claninfo['clanid']."&mdule=");
		}
	}
	page_footer();
}

function clanform(){
	$id = httpget("id");
	rawoutput("<form action='runmodule.php?module=claneditor&op=new&apply=1&id=".$id."' method='POST'>");
	addnav("","runmodule.php?module=claneditor&op=new&apply=1&id=".$id."");
	output("`@`b`cNew Clan Form`c`b");
	output("Clan Name: ");
	rawoutput("<input name='clanname' maxlength='50' value=\"".htmlentities(stripslashes(httppost('clanname')), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">");
	output("`nShort Name: ");
	rawoutput("<input name='clanshort' maxlength='5' size='5' value=\"".htmlentities(stripslashes(httppost('clanshort')), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">");
	output("`nNote, color codes are permitted in neither clan names nor short names.");
	output("The clan name is shown on player bios and on clan overview pages while the short name is displayed next to players' names in comment areas and such.`n");
	$apply = translate_inline("Apply");
	rawoutput("<input type='submit' class='button' value='$apply'></form>");
}

function clanuserform(){
	global $session;
	$n = httppost("n");
	rawoutput("<form action='runmodule.php?module=claneditor&op=new&apply=0' method='POST'>");
	addnav("","runmodule.php?module=claneditor&op=new&apply=0");
	if ($n!="") {
		$string="%";
		for ($x=0;$x<strlen($n);$x++){
		$string .= substr($n,$x,1)."%";
		}
		$sql = "SELECT login,name,acctid FROM ".db_prefix("accounts")." WHERE login LIKE '%$n%' AND locked=0 AND clanid=0 ORDER BY level,login";
		$result = db_query($sql);
		if (db_num_rows($result)!=0) {
			output("`@These users were found `^(click on a name`@):`n");
			rawoutput("<table cellpadding='3' cellspacing='0' border='0'>");
			rawoutput("<tr class='trhead'><td>Login</td><td>Name</td></tr>");
			for ($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
			rawoutput("<tr class='".($i%2?"trlight":"trdark")."'><td><a href='runmodule.php?module=claneditor&op=new&apply=1&id=".$row['acctid']."'>");
			output_notl($row['login']);
			rawoutput("</a></td><td><a href='runmodule.php?module=claneditor&op=new&apply=1&id=".$row['acctid']."'>");
			output_notl($row['name']);
			rawoutput("</td></tr>");
			addnav("","runmodule.php?module=claneditor&op=new&apply=1&id=".$row['acctid']."");
			}
			rawoutput("</table>");
		} else {
			output("`@`bA user was not found with that name.`b");
		}
		output_notl("`n");
	}
	output("`^`b`cNew Clan Form`c`b");
	output("`nFirst things first, choose who should run the clan.");
	output("Name of user (cannot be in a clan): ");
	rawoutput("<input name='n' maxlength='50' value=\"".htmlentities(stripslashes(httppost('n')), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">");
	$apply = translate_inline("Search");
	rawoutput("<input type='submit' class='button' value='$apply'></form>");
}
?>
