<?php
// translator ready
// addnews ready
// mail ready

// some recent additions to the code
// 1.  ability to review more than one player's name at once
// 2.  ability to change the range of players being examined
// 3.  ability for a moderator to supply an explanation for the rename to
//     the player
// 4.  ability for the moderator to review the mail being sent to the player
//     being renamed
// 5.  ability to click on a player's name during the renaming process to
//     review the player's biography
// 6.  ability to review the list of recently renamed players and associated
//     information
// 7.  ability to revert the name of a recently renamed player in case the
//     rename is no longer desired
// 7.  debug log entries accessible via the logs of both the renamer and
//     the renamed
// 8.  various ways to prevent accidental renaming, including a check box and
//     a confirm button

require_once("common.php");
require_once("lib/villagenav.php");
require_once("lib/http.php");
require_once("lib/names.php");
require_once("lib/systemmail.php");

function changename_getmoduleinfo(){
	$info = array(
		"name"=>"Name Change module",
		"version"=>"1.13",
		"author"=>"Shannon Brown",
		"category"=>"Administrative",
		"download"=>"core_module",
		"settings"=>array(
			"Name Change Settings,title",
			"basename"=>"What should we use as the basis for the newname?|NewName",
			"addcount"=>"Number appended to last new name,int|0",
			"history"=>"Rename history, viewonly",
		),
		"prefs"=>array(
			"Name Change User Preferences,title",
			"lastplayer"=>"End of player id range this moderator last examined,int|",
			"canchange"=>"Can this user change names,bool|0",
		)
	);
	return $info;
}

function changename_install(){

	module_addhook("header-moderate");
	module_addhook("bioend");
	return true;
}

function changename_uninstall(){
	return true;
}

function changename_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "header-moderate":
		if (get_module_pref("canchange")) {
			tlschema('nav');
			addnav("Other");
			tlschema();
			addnav("Check Most Recent Names","runmodule.php?module=changename");
		}
		break;
	case "bioend":
		if (get_module_pref("canchange")) {
			set_module_pref("viewplayer", $args['acctid']);
			set_module_pref("curname", $args['name']);
			addnav("Rename player");
			addnav(" ?Rename player", "runmodule.php?module=changename&op=view1");
		}
	}
	return $args;
}

function changename_run(){
	global $session;
	//check_su_access(SU_EDIT_COMMENTS);
	$op = httpget("op");

	page_header("Name Change");
	output("`&`c`bName Change`b`c`n");
	villagenav();
	addnav(",?Comment Moderation", "moderate.php");
	if ($session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO){
		addnav("X?`bSuperuser Grotto`b","superuser.php");
	}
	if ($op=="") {
		changename_purgehistoryofdeletedandrenamedaccounts();

		$lastplayer = getsetting("newestplayer", "");
		set_module_pref("lastplayer",$lastplayer);

		changename_displaysetrangeform($lastplayer);
		changename_displaywarning();
		changename_displayplayerrange($lastplayer);
		changename_addreversenav();
		changename_addrenamednav();
	} elseif (($op=="cancel") || ($op=="refresh")) {
		$curname = httppost("curname");
		if ($curname != "") {
			$cancelop = httppost("cancelop");

			if ($cancelop == "rename") {
				output("`\$`bRenaming of player `0%s`\$ cancelled.`b",
						$curname);
			} else {
				output("`\$`bReverting of player `0%s`\$'s name cancelled.`b",
						$curname);
			}
			output_notl("`n");
			rawoutput("<hr>");
		}

		$lastplayer = get_module_pref("lastplayer");

		changename_displaysetrangeform($lastplayer);
		changename_displaywarning();
		changename_displayplayerrange($lastplayer);
		changename_addreversenav();
		changename_addrenamednav();
	} elseif ($op=="setid") {
		$setid = httppost("setid");

		if (($setid > getsetting("newestplayer", "")) || ($setid < 1)) {
			output("`\$Error: `&Desired account id out of range.`0`n`n");
			$lastplayer = get_module_pref("lastplayer");
		}
		else {
			$lastplayer = $setid;
			set_module_pref("lastplayer",$lastplayer);
		}

		changename_displaysetrangeform($lastplayer);
		changename_displaywarning();
		changename_displayplayerrange($lastplayer);
		changename_addreversenav();
		changename_addrenamednav();
	} elseif ($op=="reverse") {
		$back = httpget("back");
		if ($back == "") $back = 10;
		$setid = get_module_pref("lastplayer") - $back;

		if ($setid < 1) {
			output("`\$Error: `&Unable to go back further.`0`n`n");
			$lastplayer = get_module_pref("lastplayer");
		} else {
			$lastplayer = $setid;
			set_module_pref("lastplayer",$lastplayer);
		}

		changename_displaysetrangeform($lastplayer);
		changename_displaywarning();
		changename_displayplayerrange($lastplayer);
		changename_addreversenav();
		changename_addrenamednav();
	} elseif ($op=="view1") {
		$acctid = get_module_pref("viewplayer");
		set_module_pref("lastplayer",$acctid);

		changename_displaywarning();
		changename_displayplayerdetail($acctid, true);
		changename_addreversenav(1);
		changename_addrenamednav();
	} elseif ($op=="focus") {
		// the difference between "focus" and "view1" is that
		//    "view1" is used when the staff member selects
		//    the player to rename from the character biography,
		//	while "focus" is generally used when the player
		//	selection occurs from the displayed range of players

			// module preference "viewplayer" is set in "focus",
			//    as it is not always the same as module preference "lastplayer"
		$acctid = httppost("acctid");
		set_module_pref("viewplayer",$acctid);

		changename_displaywarning();
		changename_displayplayerdetail($acctid);
		changename_addrefreshnav();
		changename_addrenamednav();
	} elseif ($op=="focusrefresh") {
		// "focusrefresh" is used when returning from a character biography
		//    to the screen in which reasons for a rename can be selected
		$acctid = get_module_pref("viewplayer");

		changename_displaywarning();
		changename_displayplayerdetail($acctid, false, true);
		changename_addrefreshnav();
		changename_addrenamednav();
	} elseif ($op=="renamed") {
		changename_purgehistoryofdeletedandrenamedaccounts();

		$historyserialized = get_module_setting("history");
		if ($historyserialized == "") $history = array();
		else $history = unserialize($historyserialized);

		if (count($history) == 0) {
			output("`&No players have been recently renamed and have yet to request a new name.`0`n");
		} else {
			output("`&List of players that have been recently renamed and have yet to request a new name:`0");
			rawoutput("<blockquote>");
			rawoutput("<table>");
			foreach ($history as $acctid => $acctinfoserialized) {
				$acctinfo = unserialize($acctinfoserialized);

				$sql = "SELECT name FROM " . db_prefix("accounts") .
					" WHERE acctid='$acctid'";
				$res = db_query($sql);
				if (db_num_rows($res)) {
					$row = db_fetch_assoc($res);
					$curname = $row['name'];

					rawoutput("<tr><td>");
					output_notl("`0[" . $acctid . "]`0");
					rawoutput("</td><td>");
					output("`&On `@%s`& at `@%s`&, `^%s`& was renamed.",
							date("Y.m.d",$acctinfo['renamedate']),
							date("H:i:s",$acctinfo['renamedate']),
							$acctinfo['newname']);
					rawoutput("</td><td>");

					addnav("", "runmodule.php?module=changename&op=revert");

					$buttonrevert = htmlentities(translate_inline("Revert"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
					rawoutput("<form action='runmodule.php?module=changename&op=revert' method='post'>");
					rawoutput("&nbsp;&nbsp;</td><td>");
					rawoutput("<input name='acctid' type='hidden' value='$acctid'>");
					rawoutput("<input name='curname' type='hidden' value='$curname'>");
					rawoutput("<input name='confirm' type='hidden' value='false'>");
					rawoutput("<input name='confirmbox' type='hidden' value='none'>");
					rawoutput("<input type='submit' class='button' value='$buttonrevert'>");
					rawoutput("</form>");
					rawoutput("</td></tr><tr><td></td><td>");
					output("`&The player was formerly named `^%s`&.`0",
							$acctinfo['formername']);
					if (!array_key_exists('renamer', $acctinfo)) {
						rawoutput("</td></tr><tr><td><br></td></tr>");
					} else {
						rawoutput("</td></tr><tr><td></td><td>");
						$str = "`#%s`& renamed this player";
						if ($acctinfo['reason']) {
							$str .= ", supplying the following explanation.";
						} else {
							$str .= ".";
						}
						output($str, $acctinfo['renamer']);
						if ($acctinfo['reason'] != "") {
							rawoutput("<table cellspacing=0><tr><td height=5></td></tr><tr><td>&nbsp;&nbsp;</td><td>");
							output_notl($acctinfo['reason'] . "`0");
							rawoutput("</td></tr></table>");
						}
						rawoutput("</td></tr><tr><td><br></td></tr>");
					}
				}
			}
			rawoutput("</table>");
			rawoutput("</blockquote>");
		}
		changename_addrefreshnav();
		addnav("Refresh list", "runmodule.php?module=changename&op=renamed");
	} elseif (($op=="rename") || ($op=="revert")) {
		$acctid = httppost("acctid");
		$curname = httppost("curname");
		$confirm = httppost("confirm");
		$confirmbox = httppost("confirmbox");

		$historyserialized = get_module_setting("history");
		if ($historyserialized == "") $history = array();
		else $history = unserialize($historyserialized);

		if ($op == "rename") {
			$subject = "Name Change";
			$mail1 = "`2Your name was found to be inappropriate for our server, and has been changed to `0%s`2.`n`nPlease use the `#\"Petition for Help\"`2 link to request a new, more appropriate name.`n`n`^Should you not request a new name within five days, `\$your character may be deleted`^.`0%s";
		} else {
			$subject = "Name Revert";
			$mail1 = "`2After further consideration, the staff has determined that your previous name is acceptable for this server.`n`nYour name has been changed to `0%s`2.`n`nApologies for the inconvenience.`0%s";
		}

		if ($op == "rename") {
			// reasonx determines whether or not reason x was selected,
			// while reasonxd gives details for reason x
			$reasonl = httppost("reasonl");
			$reasonld = stripslashes(urldecode(httppost("reasonld")));
			$reasond = httppost("reasond");
			$reasondd = stripslashes(urldecode(httppost("reasondd")));
			$reasont = httppost("reasont");
			$reasontd = stripslashes(urldecode(httppost("reasontd")));
			$reasoni = httppost("reasoni");
			$reasonid = stripslashes(urldecode(httppost("reasonid")));
			$reasonv = httppost("reasonv");
			$reasonvd = stripslashes(urldecode(httppost("reasonvd")));
			$reasona = httppost("reasona");
			$reasonad = stripslashes(urldecode(httppost("reasonad")));
			$reasono = httppost("reasono");
			$reasonod = stripslashes(urldecode(httppost("reasonod")));

			$numreason = ($reasonl ? 1 : 0) + ($reasond ? 1 : 0) +
				($reasont ? 1 : 0) + ($reasoni ? 1 : 0) + ($reasonv ? 1 : 0) +
				($reasona ? 1 : 0) + ($reasono ? 1 : 0);

			if ($numreason > 0) {
				if ($reasonl) {
					$reasonls = translate_inline("- Your name contains foul or inappropriate language.") . (($reasonld != "") ? " ($reasonld)" : "");
				}
				if ($reasond) {
					$reasonds = translate_inline("- Your name references drugs.") . (($reasondd != "") ? " ($reasondd)" : "");
				}
				if ($reasont) {
					$reasonts = translate_inline("- Your name contains a title that is typically earned.") . (($reasontd != "") ? " ($reasontd)" : "");
				}
				if ($reasoni) {
					$reasonis = translate_inline("- Your name appears to be an attempt to impersonate staff.") . (($reasonid != "") ? " ($reasonid)" : "");
				}
				if ($reasonv) {
					$reasonvs = translate_inline("- Your name contains excessive violence.") . (($reasonvd != "") ? " ($reasonvd)" : "");
				}
				if ($reasona) {
					$reasonas = translate_inline("- Your name is a form of advertisement.") . (($reasonad != "") ? " ($reasonad)" : "");
				}
				if ($reasono) {
					$reasonos = ($reasonod != "") ? "- $reasonod" : "";
				}

				// if the checkbox labelled "other" was checked, but details
				// weren't given, then don't count it as a reason, as it won't
				// be displayed
				if ($reasono && ($reasonos == ""))
					$numreason--;

				// check again, in case the only reason given was "other",
				// without any details
				if ($numreason > 0) {
					$nle = 1;  // stands for "new line exists"

					if (isset($reasonls)) $nle = 0;

					if (isset($reasonds)) {
						if ($nle == 0) $reasonds = "`n" . $reasonds;
						else $nle = 0;
					}

					if (isset($reasonts)) {
						if ($nle == 0) $reasonts = "`n" . $reasonts;
						else $nle = 0;
					}

					if (isset($reasonis)) {
						if ($nle == 0) $reasonis = "`n" . $reasonis;
						else $nle = 0;
					}

					if (isset($reasonvs)) {
						if ($nle == 0) $reasonvs = "`n" . $reasonvs;
						else $nle = 0;
					}

					if (isset($reasonas)) {
						if ($nle == 0) $reasonas = "`n" . $reasonas;
						else $nle = 0;
					}

					if (isset($reasonos)) {
						if ($nle == 0) $reasonos = "`n" . $reasonos;
						else $nle = 0;
					}

					$reasonstring = translate_inline("Reason" . (($numreason > 1) ? "s" : "") . " why you were renamed:`n") .
						($reasonl ? $reasonls : "") .
						($reasond ? $reasonds : "") .
						($reasont ? $reasonts : "") .
						($reasoni ? $reasonis : "") .
						($reasonv ? $reasonvs : "") .
						($reasona ? $reasonas : "") .
						($reasono ? $reasonos : "");
				} else
					$reasonstring = "";
			}
			else $reasonstring = "";
		} else {
			$reasonstring = "";

			$formername = "";

			if (array_key_exists($acctid, $history)) {
				$userhistory = unserialize($history[$acctid]);
				if ($userhistory['newname'] !=  $curname) {
					// remove from history if player has had a name change
					// after the one this module provided
					unset($history[$acctid]);
					set_module_setting("history", serialize($history));
				} else {
					$formername = $userhistory['formername'];
					if (array_key_exists('formerbasename',$userhistory))
						$formerbase = $userhistory['formerbase'];
					else $formerbase = "";
				}
			}
		}

		if ( $reasonstring == "" ) $spacedreasonstring = "";
		else $spacedreasonstring = "`n`n" . $reasonstring;

		if (($confirm === "true") && ($confirmbox == true)) {
			// query must be done again to make sure that data has not changed
			$sql = "SELECT name,title,ctitle FROM " . db_prefix("accounts") .
				" WHERE acctid='$acctid'";
			$res = db_query($sql);
			$valid = 0;
			if (db_num_rows($res)) {
				$row = db_fetch_assoc($res);
				if ($row['name'] == $curname) {
					// note that although there may be a clash of names here,
					// if someone else just happens to have name $newname,
					// it shouldn't be a big deal, as the logins don't
					// actually change with this renaming
					if ($op == "rename") {
			 			$addcount = get_module_setting("addcount");
						$basename = get_module_setting("basename");
						$addcount++;
						$newnamestring = $basename.$addcount;
						$newname = change_player_name($newnamestring, $row);
					} else {
						if ($formerbase != "")
							$newname = change_player_name($formerbase, $row);
						else $newname = $userhistory['formername'];
					}

					$sql = "UPDATE " . db_prefix("accounts") .
						" SET name='$newname' WHERE acctid='$acctid'";
					db_query($sql);

					systemmail($acctid,array($subject),array($mail1,$newname,$spacedreasonstring));

					if ($op == "rename") {
						output("`0Player `0%s`0 renamed to `0%s`0.",$row['name'],$newname);
						debuglog("Player {$row['name']} renamed to $newname by {$session['user']['name']}.",$acctid);

						set_module_setting("addcount",$addcount);

						$historyserialized = get_module_setting("history");
						if ($historyserialized == "") $history = array();
						else $history = unserialize($historyserialized);
						$history[$acctid] =
							serialize(array('newname'=>$newname, 'renamedate'=>time(), 'formername'=>$row['name'], 'formerbase'=>get_player_basename($row), 'renamer'=>$session['user']['name'], 'reason'=>$reasonstring));
						set_module_setting("history", serialize($history));

						changename_addrefreshnav();
						changename_addrenamednav();
					} else {
						output("`0Player `0%s`0's name reverted to `0%s`0.",$row['name'],$newname);
						debuglog("Player {$row['name']}'s name reverted to $newname by {$session['user']['name']}.",$acctid);

						$historyserialized = get_module_setting("history");
						if ($historyserialized == "") $history = array();
						else $history = unserialize($historyserialized);

						if (array_key_exists($acctid, $history))
							unset($history[$acctid]);
						set_module_setting("history", serialize($history));

						changename_addrefreshnav();
						changename_addrenamednav();
					}
				} else {
					output("`\$Error: `&Player `0%s`& appears to already have been renamed to `0%s`&.`0`n`n", $curname, $row['name']);
					changename_addcancelbutton();
				}
			} else {
				output("`\$Error: `&Player no longer exists.`0`n`n");
				changename_addcancelbutton();
			}
		} elseif (strstr($reasonstring, "\\")) {
			output("`\$Error: `&Given reason contains a backslash character, which system mail is very unhappy with.`0`n`n");
			changename_addcancelbutton();
		} else {
			if (($confirm === "true") && ($confirmbox == false))
				output("`\$Error: `&Please confirm by clicking on the check box before hitting the confirm button.`0`n`n");

			$sql = "SELECT name,title,ctitle FROM " . db_prefix("accounts") .
				" WHERE acctid='$acctid'";
			$res = db_query($sql);
			if (db_num_rows($res)) {
				$row = db_fetch_assoc($res);

				if ($op == "rename") {
					output("`0[%s]`0 `@Player name:`0 `b%s `b`0`n",
							$acctid,$curname);
				} else {
					rawoutput("<table>");
					rawoutput("<tr><td>");
					output_notl("`0[" . $acctid . "]`0");
					rawoutput("</td><td>");
					output("`@Player name:`0 `b%s `b`0`n",$curname);
					rawoutput("</td></tr><tr><td></td><td>");
					output("`&Formerly `0%s`0.", $formername);
					rawoutput("</td></tr>");
					rawoutput("</table>");
				}

				if ($op == "rename") {
					$newnamestring = get_module_setting("basename") .
						(get_module_setting("addcount")+1);
					$newname = change_player_name($newnamestring, $row);
				} else {
					if ($formerbase != "")
						$newname = change_player_name($formerbase, $row);
					else $newname = $userhistory['formername'];
				}

				changename_displaywarning();

				output("`@The following ye olde mail will be sent to `&%s`@.`n", $curname);
				output("Please confirm that this is indeed the player you wish to rename and that all the information below is correct.`0`n`n");
				output("`\$Note`&: The tentative new name, `0%s`&, may not be the actual name used by the time the player is actually renamed.", $newname);
				output("Also, depending on the default language selected by the recipient, the preview below may not be what the recipient actually sees.");

				rawoutput("<blockquote><hr>");

				output("`b`2From:`b `^`iSystem`i`0`n");
				output("`b`2Subject:`b `^%s`0`n", translate_inline($subject));
				rawoutput("<img src='../images/uscroll.GIF' width=182 height=11 alt='' align='center'><br>");
				output($mail1, $newname, $spacedreasonstring);
				output_notl("`n");
				rawoutput("<img src='../images/lscroll.GIF' width=182 height=11 alt='' align='center'><br>");
				rawoutput("<hr></blockquote>");

				if ($op == "rename") {
					addnav("", "runmodule.php?module=changename&op=rename");

					rawoutput("<form action='runmodule.php?module=changename&op=rename' method='post'>");
					// these have to be passed in again in case the check box
					// wasn't checked
					rawoutput("<input name='reasonl' type='hidden' value='$reasonl'>");
					rawoutput("<input name='reasonld' type='hidden' value='".urlencode($reasonld)."'>");
					rawoutput("<input name='reasond' type='hidden' value='$reasond'>");
					rawoutput("<input name='reasondd' type='hidden' value='".urlencode($reasondd)."'>");
					rawoutput("<input name='reasont' type='hidden' value='$reasont'>");
					rawoutput("<input name='reasontd' type='hidden' value='".urlencode($reasontd)."'>");
					rawoutput("<input name='reasoni' type='hidden' value='$reasoni'>");
					rawoutput("<input name='reasonid' type='hidden' value='".urlencode($reasonid)."'>");
					rawoutput("<input name='reasonv' type='hidden' value='$reasonv'>");
					rawoutput("<input name='reasonvd' type='hidden' value='".urlencode($reasonvd)."'>");
					rawoutput("<input name='reasona' type='hidden' value='$reasona'>");
					rawoutput("<input name='reasonad' type='hidden' value='".urlencode($reasonad)."'>");
					rawoutput("<input name='reasono' type='hidden' value='$reasono'>");
					rawoutput("<input name='reasonod' type='hidden' value='".urlencode($reasonod)."'>");
				} else {
					addnav("", "runmodule.php?module=changename&op=revert");

					rawoutput("<form action='runmodule.php?module=changename&op=revert' method='post'>");
				}

				rawoutput("<input name='acctid' type='hidden' value='$acctid'>");
				rawoutput("<input name='curname' type='hidden' value='$curname'>");

				rawoutput("<input name='confirm' type='hidden' value='true'>");

				$buttonconfirm = htmlentities(translate_inline("Confirm!"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
				output("`&Check this box and hit confirm to " . $op . " this player.`0");
				rawoutput("<input name='confirmbox' type='checkbox' value='confirm'>");
				rawoutput("<input type='submit' class='button' value='$buttonconfirm'>");
				rawoutput("</form>");

				$buttoncancel = htmlentities(translate_inline("Aagh! No! Cancel! I messed up!"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
				addnav("", "runmodule.php?module=changename&op=cancel");
				rawoutput("<form action='runmodule.php?module=changename&op=cancel' method='post'>");
				rawoutput("<input type='submit' class='button' value='$buttoncancel'>");
				rawoutput("<input name='curname' type='hidden' value='$curname'>");
				rawoutput("<input name='cancelop' type='hidden' value='$op'>");
				rawoutput("</form>");
			} else {
				output("`\$Error: `&Player no longer exists.`0`n`n");
				changename_addcancelbutton();
			}
		}
	}
	page_footer();
}

	// yeah, ok, i admit, this is a bad function name
function changename_purgehistoryofdeletedandrenamedaccounts()
{
	$historyserialized = get_module_setting("history");
	if ($historyserialized == "") return;
	else $history = unserialize($historyserialized);

	$acctids = array_keys($history);

	foreach ($acctids as $acctid)
	{
		$sql = "SELECT name,login FROM " . db_prefix("accounts") . " WHERE acctid='$acctid'";
		$res = db_query($sql);
		if (db_num_rows($res)) {
			$row = db_fetch_assoc($res);
			$userhistory = unserialize($history[$acctid]);
			if ($userhistory['newname'] !=  $row['name']) {
				// remove from history if player has had a name change after
				// the one this module provided
				unset($history[$acctid]);
				set_module_setting("history", serialize($history));
			}
		} else {
			unset($history[$acctid]);
			set_module_setting("history", serialize($history));
		}
	}
}

	// allows user to change the range of players to review
function changename_displaysetrangeform($lastacctid)
{
	addnav("", "runmodule.php?module=changename&op=setid");

	output("`0The most recent player has account id %s`0.`0`n", getsetting("newestplayer", ""));

	$buttonchangerange = htmlentities(translate_inline("Change Range"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
	rawoutput("<form action='runmodule.php?module=changename&op=setid' method='post'>");
	output("Set new player range to review, ending at");
	rawoutput("<input name='setid' type='text' value='$lastacctid' size='6'>");
	output_notl(".");
	rawoutput("&nbsp;&nbsp;");
	rawoutput("<input type='submit' class='button' value='$buttonchangerange'>");
	rawoutput("</form>");
}

	// outputs table tags and iterates over the players in the relevant range
function changename_displayplayerrange($lastacctid)
{
	$acctid = $lastacctid - 10 + 1;
	if ($acctid < 1) $acctid = 1;

	rawoutput("<table cellspacing=10>");
	while ($acctid <= $lastacctid) {
		changename_displayplayer($acctid++);
	}
	rawoutput("</table>");
}

	// outputs details of one player as one row in a table
function changename_displayplayer($acctid)
{
	rawoutput("<tr><td>");

	$sql = "SELECT name,login,dragonkills FROM " . db_prefix("accounts") . " WHERE acctid='$acctid'";
	$res = db_query($sql);
	if (db_num_rows($res)) {
		$row = db_fetch_assoc($res);
		$curname = $row['name'];

		if ($row['dragonkills'] > 0) {
			output("`0[%s]`0 `2Player name:`0 `b%s `b`0",$acctid,$curname);
			output("`n`7This player already has %s dragon kill%s.`0",
					$row['dragonkills'], ($row['dragonkills']>1)?"s":"");
			rawoutput("</td><td>");
			output_notl(changename_renamehistory($acctid,$curname));
		} else {
			$renamehistory = changename_renamehistory($acctid,$curname);

			if ($renamehistory == "") {
				$biourl = "bio.php?char=" . $acctid . "&ret=%2Frunmodule.php%3Fmodule%3Dchangename%26op%3Drefresh";

				addnav("", "runmodule.php?module=changename&op=focus");
				addnav("", $biourl);

				rawoutput("<form action='runmodule.php?module=changename&op=focus' method='post'>");
				output("`0[%s]`0 `@Player name:`0 `b",$acctid);
				rawoutput("<a href='" . $biourl . "'>");
				output_notl($curname);
				rawoutput("</a>");
				output_notl("`b`0");
				rawoutput("</td><td>");
				rawoutput("<input name='acctid' type='hidden' value='$acctid'>");
				rawoutput("<input name='curname' type='hidden' value='$curname'>");

				$buttonrename = htmlentities(translate_inline("Rename"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
				rawoutput("<input type='submit' class='button' value='$buttonrename'>");
				rawoutput("</form>");
			} else {
				output("`0[%s]`0 `2Player name:`0 `b%s `b`0",$acctid,$curname);
				rawoutput("</td><td>");
				output_notl($renamehistory);
			}
		}
	} else {
		output("`0[%s]`0 `2Player no longer exists.`0`n",$acctid);
	}

	rawoutput("</tr></td>");
}

	// generates a form for the user to input reasons for a rename
function changename_displayplayerdetail($acctid,$frombio=false,$focusrefresh=false)
{
	if ($frombio || $focusrefresh) $curname = get_module_pref("curname");
	else set_module_pref("curname", $curname = httppost("curname"));

	$sql = "SELECT name,login FROM " . db_prefix("accounts") . " WHERE acctid='$acctid'";
	$res = db_query($sql);
	if (db_num_rows($res)) {
		$row = db_fetch_assoc($res);
		if ($row['name'] == $curname) {
			if ($frombio) $returnop = "view1";
			else $returnop = "focusrefresh";

			$biourl = "bio.php?char=" . $acctid . "&ret=%2Frunmodule.php%3Fmodule%3Dchangename%26op%3D" . $returnop;

			addnav("", "runmodule.php?module=changename&op=rename");
			addnav("", $biourl);

			rawoutput("<form action='runmodule.php?module=changename&op=rename' method='post'>");
			output("`0[%s]`0 `@Player name:`0 `b",$acctid);
			rawoutput("<a href='" . $biourl . "'>");
			output_notl($curname);
			rawoutput("</a>");
			output_notl("`b`0");

			rawoutput("<input name='acctid' type='hidden' value='$acctid'>");
			rawoutput("<input name='curname' type='hidden' value='$curname'>");
			rawoutput("<input name='confirm' type='hidden' value='false'>");
			rawoutput("<input name='confirmbox' type='hidden' value='none'>");

			rawoutput("<table cellspacing=7>");

			rawoutput("<tr><td>");
			rawoutput("<input name='reasonl' type='checkbox' value='foul'>");
			rawoutput("</td><td>");
			output("Foul or Inappropriate Language");
			rawoutput("</td><td>&nbsp;&nbsp;");
			output("Details (optional)");
			rawoutput("<input name='reasonld' type='text' value=''>");
			rawoutput("</td></tr>");

			rawoutput("<tr><td>");
			rawoutput("<input name='reasond' type='checkbox' value='drug'>");
			rawoutput("</td><td>");
			output("Reference to Drugs");
			rawoutput("</td><td>&nbsp;&nbsp;");
			output("Details (optional)");
			rawoutput("<input name='reasondd' type='text' value=''>");
			rawoutput("</td></tr>");

			rawoutput("<tr><td>");
			rawoutput("<input name='reasont' type='checkbox' value='title'>");
			rawoutput("</td><td>");
			output("Unearned Title");
			rawoutput("</td><td>&nbsp;&nbsp;");
			output("Details (optional)");
			rawoutput("<input name='reasontd' type='text' value=''>");
			rawoutput("</td></tr>");

			rawoutput("<tr><td>");
			rawoutput("<input name='reasoni' type='checkbox' value='impersonation'>");
			rawoutput("</td><td>");
			output("Staff Impersonation");
			rawoutput("</td><td>&nbsp;&nbsp;");
			output("Details (optional)");
			rawoutput("<input name='reasonid' type='text' value=''>");
			rawoutput("</td></tr>");

			rawoutput("<tr><td>");
			rawoutput("<input name='reasonv' type='checkbox' value='violence'>");
			rawoutput("</td><td>");
			output("Excessive Violence");
			rawoutput("</td><td>&nbsp;&nbsp;");
			output("Details (optional)");
			rawoutput("<input name='reasonvd' type='text' value=''>");
			rawoutput("</td></tr>");

			rawoutput("<tr><td>");
			rawoutput("<input name='reasona' type='checkbox' value='advertising'>");
			rawoutput("</td><td>");
			output("Advertising");
			rawoutput("</td><td>&nbsp;&nbsp;");
			output("Details (optional)");
			rawoutput("<input name='reasonad' type='text' value=''>");
			rawoutput("</td></tr>");

			rawoutput("<tr><td>");
			rawoutput("<input name='reasono' type='checkbox' value='other'>");
			rawoutput("</td><td>");
			output("Other");
			rawoutput("</td><td>&nbsp;&nbsp;");
			output("Details (optional)");
			rawoutput("<input name='reasonod' type='text' value=''>");
			rawoutput("</td></tr>");

			rawoutput("<tr><td></td><td></td><td align='right'>");
			$buttonrename = htmlentities(translate_inline("Rename"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
			rawoutput("<input type='submit' class='button' value='$buttonrename'>");
			rawoutput("</td></tr>");

			rawoutput("</table>");

			rawoutput("</form>");
		} else {
			output("`\$Error: `&Player `0%s`& appears to already have been renamed to `0%s`&.`0`n`n", $curname, $row['name']);
			changename_addcancelbutton();
		}
	} else {
		output("`\$Error: `&Player no longer exists.`0`n`n");
		changename_addcancelbutton();
	}
}

function changename_renamehistory($acctid, $curname)
{
	$historyserialized = get_module_setting("history");
	if ($historyserialized == "") $history = array();
	else $history = unserialize($historyserialized);

	$output = "";

	if (array_key_exists($acctid, $history)) {
		$userhistory = unserialize($history[$acctid]);
		if ($userhistory['newname'] !=  $curname) {
			// remove from history if player has had a name change after the
			// one this module provided
			unset($history[$acctid]);
			set_module_setting("history", serialize($history));
		} else {
			$output = sprintf_translate("`7Renamed on %s`7 at %s`7.`n Formerly `0%s`7.", date("Y.m.d", $userhistory['renamedate']), date("H:i:s", $userhistory['renamedate']), $userhistory['formername']);
		}
	}

	return $output;
}

function changename_displaywarning()
{
	output("`n`c`b`\$WARNING`&: `^Please ensure that you are changing the correct name.`b`c`0`n");
}

function changename_addreversenav($back=false)
{
	addnav("Name Changing");

	if ($back) addnav("Previous players' names","runmodule.php?module=changename&op=reverse&back=$back");
	else addnav("Previous players' names","runmodule.php?module=changename&op=reverse");
}

function changename_addrefreshnav()
{
	addnav("Name Changing");
	addnav("Review player names", "runmodule.php?module=changename&op=refresh");
}

function changename_addrenamednav()
{
	addnav("Name Changing");
	addnav("Recently renamed", "runmodule.php?module=changename&op=renamed");
}

function changename_addcancelbutton()
{
	$buttoncancel = htmlentities(translate_inline("Cancel"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
	addnav("", "runmodule.php?module=changename&op=cancel");
	rawoutput("<form action='runmodule.php?module=changename&op=cancel' method='post'>");
	rawoutput("<input type='submit' class='button' value='$buttoncancel'>");
	rawoutput("</form>");
}

?>
