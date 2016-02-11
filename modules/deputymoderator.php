<?php

// this module allows for players to become deputy moderators, who
// are allowed to delete comments from one specific commentary area

// in order to deputize a player, someone with user editor access should
// set the module pref named "area" to the specific commentary area
// that the player being deputized should have the jurisdiction over.

// a deputy cannot be assigned to more than one area.  this way,
// deputies can only have the responsibility of one area, so they will
// more likely focus their attention to that one area.

// deputies are given a deletestick™ that can be used from the deputy's
// moderation screen, which is accessible from the villages under the
// header "deputy duties".  this screen allows deputies to view the
// last 100 comments in the area assigned to them, and also allows them
// to add comments to the same area, so that when they must exercise
// their powers, they don't have to continually flip back and forth
// between the moderation screen and the actual commentary area.  they
// are also allowed to break post limits from the moderation screen.

// the ability to review even earlier comments is deliberately left out,
// since, in theory, deputies are given their powers so that they can
// delete comments in case moderators don't get there as quickly, and
// it is unlikely that a moderator has not had the chance to review
// an inappropriate comment by the time it scrolls off the deputy's
// moderation screen.  nevertheless, the number of comments that the
// deputy can review at once can be changed, as it is a module setting,
// going by the name "numcomments".

// all comments deleted by deputies can be reviewed by any moderator
// with the Audit flag.
// the screen from which these comments can be reviewed is accessible
// from the superuser grotto, under the header "actions".  the nav is
// named "review deputy actions".  from this screen, moderators can
// either validate or restore deleted comments.  validated comments
// are entered into the moderatedcomments table, so that staff with
// access to audit moderated comments can override any validation
// done by the moderators.  restored comments are put back into the
// commentary areas.  in either case, reviewed comments are kept in
// the deputy review screen for five days after they have been marked
// as either validated or restored.

// note that under the moderatedcomments table, the moderator who
// validated the comment is listed under the "moderator" column,
// but the deputy who deleted the comment is still recorded under
// the deputy review screen.

// note that deputies are only given a deletestick™ that works only
// in one specific commentary area, and that they are deliberately not
// given a mutestick™ nor a banstick™.  this way, the deputy has the
// powers to do the job required of them, but if the deputy abuses
// the granted powers, the worst that could happen is that only one
// commentary area gets wiped out, which any moderator will have the
// ability to reverse at any time.

// deputies are also not given access to the grotto, the commentary
// in the character biographies, the ability to speak without getting
// one's comments parsed through the drunk filter, or anything else
// aside from the deletestick™ that works only in that one specific
// commentary area assigned to them, and the ability to bypass post
// limits from the deputy's moderation screen.

// 3rd Sept 2005 alteration to audit permissions to allow only
// SU with the Audit flag to perform this role
// - Saucy

function deputymoderator_getmoduleinfo() {
	$info = array(
		"name"=>"Deputy Moderator",
		"version"=>"0.1",
		"author"=>"dying",
		"category"=>"Administrative",
		"download"=>"core_module",
		"settings"=>array(
			"Deputy Moderator Settings, title",
			"numcomments"=>"Limit to number of recent comments retrieved for deputies,range,0,500,5|100",
			"toreview"=>"Comments deleted by deputies that have not been reviewed yet,viewonly",
		),
		"prefs"=>array(
			"Deputy Moderator Preferences, title",
			"Player is deputized by placing the name of a commentary area below., note",
			"Examples of commentary area names include \"gardens\" and \"village-Elf\"., note",
			"area"=>"Commentary area for which the player has delete privileges",
		),
	);
	return $info;
}

function deputymoderator_install() {
	module_addhook("village");     // for deputies
	module_addhook("shades");
	module_addhook("superuser");   // for moderators
	return true;
}

function deputymoderator_uninstall() {
	return true;
}

function deputymoderator_dohook($hookname, $args) {
	global $session;
	switch($hookname){
	case "shades":
	case "village":
		$area = get_module_pref("area");
		if ($area=="") break;

		$navdeputyheader = translate_inline("Deputy Duties");
		$navdeputy = translate_inline("Moderate");
		addnav($navdeputyheader);
		addnav(".?" . $navdeputy . " (" . $area . ")", "runmodule.php?module=deputymoderator");
		unblocknav($navdeputyheader);
		unblocknav("runmodule.php?module=deputymoderator");
		break;
	case "superuser":
		if ($session['user']['superuser'] & SU_AUDIT_MODERATION){
			$navreview = translate_inline("Review Deputy Actions");
			addnav("Actions");
			addnav($navreview, "runmodule.php?module=deputymoderator&op=review&admin=true");
		}
		break;
	}
	return $args;
}

function deputymoderator_run()
{
	global $session;

	$getop = httpget("op");

	if ($getop=="delete") {
		$postcommentid = httppost("commentid");
		$postarea = httppost("area");

		$sql = "SELECT * FROM " . db_prefix("commentary") . " WHERE commentid='". $postcommentid . "'";
		$res = db_query($sql);
		if (db_num_rows($res)!=0) {
			$row = db_fetch_assoc($res);
			$toreview = @unserialize(get_module_setting("toreview"));
			if (!is_array($toreview)) $toreview = array();

			$asql = "SELECT " . db_prefix("accounts") . ".name, "
					. db_prefix("accounts") . ".login, "
					. db_prefix("accounts") . ".clanrank, "
					. db_prefix("clans") . ".clanshort"
					. " FROM " . db_prefix("accounts")
					. " LEFT JOIN " . db_prefix("clans")
					. " ON " . db_prefix("accounts") . ".clanid"
					. " = " . db_prefix("clans") . ".clanid"
					. " WHERE " . db_prefix("accounts") . ".acctid"
					. " = '" . $row['author'] . "'";
			$ares = db_query($asql);
			$arow = db_fetch_assoc($ares);

				// 'deputyid' isn't currently used, but is stored in case it will be
			array_push($toreview, array('deputy'=>$session['user']['name'],
								'deputyid'=>$session['user']['acctid'],
								'deletedate'=>time(),
								'comment'=>$row,
								'author'=>$arow));

			set_module_setting("toreview", serialize($toreview));

			$sql = "DELETE FROM " . db_prefix("commentary") . " WHERE commentid='". $postcommentid . "'";
			db_query($sql);
			invalidatedatacache("comments-".$postarea);
		}
	}

	if ($getop=="validate"||$getop=="restore") {
		$postcommentid = httppost("commentid");

		$toreview = @unserialize(get_module_setting("toreview"));
		if (!is_array($toreview)) $toreview = array();

		$changedtoreview = false;

		$keys = array_keys($toreview);

		for($i=0; $i<count($toreview); $i++) {
			if ($toreview[$keys[$i]]['comment']['commentid']==$postcommentid) {

				if ($getop=="validate") {

					$comment = array_merge($toreview[$keys[$i]]['comment'], $toreview[$keys[$i]]['author']);

					$sql = "INSERT LOW_PRIORITY INTO " . db_prefix("moderatedcomments")
							. " (comment, moderator, moddate)"
							. " VALUES ('" . addslashes(serialize($comment)) . "', "
							. "'" . $session['user']['acctid'] . "', "
							. "'" . date("Y-m-d H:i:s") . "')";
					db_query($sql);

					$toreview[$keys[$i]]['validatedby'] = $session['user']['name'];
					$toreview[$keys[$i]]['validatedate'] = time();

				} else {

					$sql = "INSERT LOW_PRIORITY INTO " . db_prefix("commentary")
							. " (commentid, section, author, comment, postdate)"
							. " VALUES ('" . $toreview[$keys[$i]]['comment']['commentid'] . "', "
							. "'" . addslashes($toreview[$keys[$i]]['comment']['section']) . "', "
							. "'" . addslashes($toreview[$keys[$i]]['comment']['author']) . "', "
							. "'" . addslashes($toreview[$keys[$i]]['comment']['comment']) . "', "
							. "'" . $toreview[$keys[$i]]['comment']['postdate'] . "')";
					db_query($sql);
					invalidatedatacache("comments-".$toreview[$keys[$i]]['comment']['section']);

					$toreview[$keys[$i]]['restoredby'] = $session['user']['name'];
					$toreview[$keys[$i]]['restoredate'] = time();
				}

				$changedtoreview = true;

				break;
			}
		}

		if ($changedtoreview) set_module_setting("toreview", serialize($toreview));
	}

	if (($getop=="")||($getop=="delete")) {
		$area = get_module_pref("area");
		if ($area=="") deputymoderator_die("Unauthorized Access");

		page_header("Moderate (" . $area . ")");
		villagenav();

		$navrefresh = translate_inline("Refresh Comments");
		addnav($navrefresh, "runmodule.php?module=deputymoderator");

		output("`c`b`^Moderate (%s)`0`b`c`n", $area);

		if ($getop=="delete") output ("`b`\$Comment deleted.`0`b`n`n");

		require_once("lib/commentary.php");
		addcommentary();

		$numcomments = get_module_setting("numcomments");

		$sql = "SELECT * FROM " . db_prefix("commentary") . " WHERE section='" . $area . "' ORDER BY postdate DESC LIMIT " . $numcomments;
		$res = db_query($sql);

		if (db_num_rows($res)==0) {
			output("This commentary area currently contains no comments.");
		} else {
			addnav("", "runmodule.php?module=deputymoderator&op=delete");
			$buttondelete = translate_inline("Delete");

			$stack = array();

			while ($row=db_fetch_assoc($res)) array_push($stack, $row);

			rawoutput("<table>");

			while ($row=array_pop($stack)) {
				rawoutput("<tr><td valign='top'>");
				rawoutput("<form action='runmodule.php?module=deputymoderator&op=delete' method='post'>");
				rawoutput("<input type='hidden' name='commentid' value='" . $row['commentid'] . "'>");
				rawoutput("<input type='hidden' name='area' value='" . $area . "'>");
				rawoutput("<input type='submit' class='button' value='" . $buttondelete . "'>");
				rawoutput("</form>");
				rawoutput("</td><td>");
				deputymoderator_displaycomment($row);
				rawoutput("</td></tr>");
			}

			rawoutput("</table>");
		}

			// note:  the usage of "says" here may clash with what
			//        may be typically used in the section in question,
			//        but retrieving the appropriate string to use
			//        here instead does not appear to be feasible.
			//        also, the usage of "500" here allows the deputy
			//        to basically avoid post limits without actually
			//        having to get access to the grotto.
		talkform($area, "says", 500);

		page_footer();
	} elseif ($getop=="review"||$getop=="validate"||$getop=="restore") {
		deputymoderator_checkforsuperuser();

		page_header("Review Deputy Actions");
		require_once("lib/superusernav.php");
		superusernav();

		$navrefresh = translate_inline("Refresh Deleted Comments");
		addnav($navrefresh, "runmodule.php?module=deputymoderator&op=review");

		deputymoderator_pruneoldentries();

		output("`c`b`^Review Deputy Actions`0`b`c`n");

		if ($getop=="validate") output ("`b`\$Deleted comment validated.`0`b`n`n");
		if ($getop=="restore") output ("`b`\$Deleted comment restored.`0`b`n`n");

		$toreview = @unserialize(get_module_setting("toreview"));
		if (!is_array($toreview)) set_module_setting("toreview", serialize($toreview = array()));

		output("`b`#Comments Deleted By Deputies`0`b");
		if (count($toreview)==0) {
			rawoutput("<blockquote>");
			output("There are no deleted comments to review.");
			rawoutput("</blockquote>");
		} else {
			addnav("", "runmodule.php?module=deputymoderator&op=validate");
			$buttonvalidate = translate_inline("Validate");

			addnav("", "runmodule.php?module=deputymoderator&op=restore");
			$buttonrestore = translate_inline("Restore");

			rawoutput("<blockquote>");
			output("`&When you `b`@validate`&`b a comment, the comment will be marked as validated.`0`n");
			output("`&When you `b`%restore`&`b a comment, the comment will be marked as restored and inserted back into the commentary.`0`n");
			output("`&Comments that have been reviewed will remain in this list for five days.`0");
			rawoutput("</blockquote>");

			rawoutput("<table>");

			$toreviewreverse = array_reverse($toreview);

			foreach($toreviewreverse as $element) {
				rawoutput("<tr>");
				if (!isset($element['validatedby'])&&!isset($element['restoredby'])) {
					rawoutput("<td>");
					rawoutput("<form action='runmodule.php?module=deputymoderator&op=validate' method='post'>");
					rawoutput("<input type='hidden' name='commentid' value='" . $element['comment']['commentid'] . "'>");
					rawoutput("<input type='submit' class='button' value='" . $buttonvalidate . "'>");
					rawoutput("</form>");
					rawoutput("</td><td>");
					rawoutput("<form action='runmodule.php?module=deputymoderator&op=restore' method='post'>");
					rawoutput("<input type='hidden' name='commentid' value='" . $element['comment']['commentid'] . "'>");
					rawoutput("<input type='submit' class='button' value='" . $buttonrestore . "'>");
					rawoutput("</form>");
				} else {
					rawoutput("<td colspan='2' align='center'>");
					if (isset($element['validatedby'])) {
						rawoutput("<table cellspacing=0><tr><td>&nbsp;&nbsp;</td><td>");
						output("`c`b`@Validated! `b`c");
						rawoutput("</td><td>&nbsp;&nbsp;</td></tr></table>");
						rawoutput("</td><td>");
						output("`#Validated`& on %s at %s by %s`0.", date("Y.m.d", $element['validatedate']), date("H:i:s", $element['validatedate']), $element['validatedby']);
					} else {
						rawoutput("<table cellspacing=0><tr><td>&nbsp;&nbsp;</td><td>");
						output("`c`b`%Restored! `b`c");
						rawoutput("</td><td>&nbsp;&nbsp;</td></tr></table>");
						rawoutput("</td><td>");
						output("`#Restored`& on %s at %s by %s`0.", date("Y.m.d", $element['restoredate']), date("H:i:s", $element['restoredate']), $element['restoredby']);
					}
					rawoutput("</td></tr><tr><td></td><td>");
				}

				rawoutput("</td><td>");
				output("`#Deleted`& on %s at %s by %s`0.", date("Y.m.d", $element['deletedate']), date("H:i:s", $element['deletedate']), $element['deputy']);
				rawoutput("</td></tr><tr><td></td><td></td><td>");
				deputymoderator_displaycomment($element['comment']);
				output_notl("`n");
				rawoutput("</td></tr>");
			}

			rawoutput("</table>");
		}

		page_footer();
	} else {
		deputymoderator_die("Operation Not Defined");
	}
}

function deputymoderator_displaycomment($ccomment)
{
	$section = translate_inline($ccomment['section']);
	$time = deputymoderator_formattedtime(strtotime($ccomment['postdate']));
	$author = deputymoderator_formattedauthor($ccomment['author']);
	$comment = deputymoderator_formattedcomment($ccomment['comment']);

	output("(%s) %s %s %s`0`n", $section, $time, $author, $comment, true);
}

	// the code here was mostly taken from the lib/commentary.php file,
	// although it may be more useful if the code was placed into one of
	// the files in the lib directory
function deputymoderator_formattedtime($timestamp)
{
	global $session;

	if ($session['user']['prefs']['timestamp']==1) {
		if (!isset($session['user']['prefs']['timeformat'])) $session['user']['prefs']['timeformat'] = "[m/d h:ia]";
		$time = strtotime("+{$session['user']['prefs']['timeoffset']} hours",$timestamp);
		return (date("`7" . $session['user']['prefs']['timeformat'] . "`0",$time));
	} elseif ($session['user']['prefs']['timestamp']==2) {
		return ("`7(" . reltime($timestamp) . ")`0");
	} else {
		return "";
	}
}

function deputymoderator_formattedauthor($acctid)
{
	$sql = "SELECT name, login, clanid, clanrank FROM " . db_prefix("accounts") . " WHERE acctid=" . $acctid;
	$res = db_query($sql);

	if (db_num_rows($res)>0) {
		$row = db_fetch_assoc($res);
		$tag = deputymoderator_formattedclantag($row['clanid'], $row['clanrank']);
		if ($tag != "") $tag .= " ";
		$link = "bio.php?char=" . $acctid . "&ret=" . URLEncode($_SERVER['REQUEST_URI']);
		addnav("",$link);
		return ($tag . "`0<a href='$link' style='text-decoration: none'>`&{$row['name']}`0</a>`&");
	}

	return "";
}

function deputymoderator_formattedclantag($clanid, $clanrank)
{
	if ($clanrank==0) return "";

	$sql = "SELECT clanid, clanshort FROM " . db_prefix("clans") . " WHERE clanid=" . $clanid;
	$res = db_query($sql);
	if ($row=db_fetch_assoc($res)) {
		$clanshort = $row['clanshort'];

		if ($clanrank==1) return ("`#<`2$clanshort`#>`0");
		if ($clanrank==2) return ("`^<`2$clanshort`^>`0");
		if ($clanrank==3) return ("`&<`2$clanshort`&>`0");

			// should be hit only if more clan rank values are added
		return ("`%<`2$clanshort`%>`0");

	} else return "";
}

function deputymoderator_formattedcomment($comment)
{
	$emote = preg_replace("|^::|", "`&", $comment);
	if ($emote != $comment) return $emote;
	$emote = preg_replace("|^:|", "`&", $comment);
	if ($emote != $comment) return $emote;
	$emote = preg_replace("|^/me|", "`&", $comment);
	if ($emote != $comment) return $emote;

	return (sprintf("`3says, \"`#%s`3\"", $comment));
}

	// this function should primarily be used by other code
	//    to determine if the player in question has been deputized
function deputymoderator_deputized($acctid) {
	return (get_module_pref("area", false, $acctid)!="");
}

function deputymoderator_checkforsuperuser() {
	global $session;

	if (!($session['user']['superuser'] & SU_EDIT_COMMENTS)) {
		deputymoderator_die("Unauthorized Access");
	}
}

function deputymoderator_pruneoldentries() {

	$toreview = @unserialize(get_module_setting("toreview"));
	if (!is_array($toreview)) $toreview = array();

	$changedtoreview = false;

	$toreviewkeys = array_keys($toreview);

	for($j=0; $j<count($toreview); $j++) {

		$i = $toreviewkeys[$j];

		unset($date);

		if (isset($toreview[$i]['validatedate'])) $date = $toreview[$i]['validatedate'];
		if (isset($toreview[$i]['restoredate'])) $date = $toreview[$i]['restoredate'];

		if (isset($date)) {
			if ($date < strtotime("-5 days")) {
				unset($toreview[$i]);
				$changedtoreview = true;
			}
		}
	}

	if ($changedtoreview) set_module_setting("toreview", serialize($toreview));
}

function deputymoderator_die($header, $text="`&Oops.  You're not supposed to be here.") {
	page_header($header);
	output($text);
	villagenav();
	page_footer();
	die();
}

?>
