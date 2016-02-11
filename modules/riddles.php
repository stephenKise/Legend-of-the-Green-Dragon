<?php
// addnews ready
// mail ready
// translator ready

function riddles_getmoduleinfo(){
	$info = array(
		"name"=>"Riddling Gnome",
		"version"=>"1.2",
		"author"=>"Joe Naylor",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"prefs"=>array(
			"Riddle Module User Preferences,title",
			"canedit"=>"Has access to the riddle editor,bool|0"
		),
	);
	return $info;
}

function riddles_install(){
	if (db_table_exists(db_prefix("riddles"))) {
		debug("Riddles table already exists");
	} else {
		debug("Creating riddles table.");
		// This is pulled out to another file just because it's so big.
		// no reason to parse it every time this module runs.
		require_once("modules/riddles/riddles_install.php");
	}
	module_addhook("superuser");
	module_addeventhook("forest", "return 100;");
	return true;
}

function riddles_uninstall(){
	debug("Dropping riddles table");
	$sql = "DROP TABLE IF EXISTS " . db_prefix("riddles");
	db_query($sql);
	return true;
}

function riddles_dohook($hookname,$args){
	global $session;
	switch($hookname) {
	case "superuser":
		if (($session['user']['superuser'] & SU_EDIT_RIDDLES) ||
				get_module_pref("canedit")) {
			addnav("Module Configurations");
			// Stick the admin=true so that when we can call runmodule
			// it'll work even if the module is deactivated.
			addnav("Riddle Editor",
					"runmodule.php?module=riddles&act=editor&admin=true");
		}
		break;
	}
	return $args;
}

//** Used to remove extra words from the beginning and end of a string
// Note that string will be converted to lowercase
function riddles_filterwords($string)
{
	$string = strtolower($string);

	//Words to remove
	$filterpre = array ( "a", "an", "and", "the", "my", "your", "someones",
		"someone's", "someone", "his", "her", "s");
	//Letters to take off the end
	$filterpost = array ( "s", "ing", "ed");

	//split into array of words
	$filtstr = explode(" ", trim($string));
	foreach ($filtstr as $key => $filtstr1)
		$filtstr[$key] = trim($filtstr1);

	//pop off word if found in $filterpre
	foreach ($filtstr as $key => $filtstr1)
		foreach ($filterpre as $filterpre1)
			if (!strcasecmp($filtstr1, $filterpre1))
				$filtstr[$key] = "";

	//trim off common word endings
	foreach ($filtstr as $key => $filtstr1)
		foreach ($filterpost as $filterpost1)
		if (strlen($filtstr1) > strlen($filterpost1))
			if (!strcasecmp(substr($filtstr1,
							-1*strlen($filterpost1)), $filterpost1))
				$filtstr[$key] =
					substr($filtstr1, 0,
							strlen($filtstr1)-strlen($filterpost1));

	//rebuild filtered input
	$tmpstring = implode("", $filtstr);
	// Make sure we have an answer .. If not, return the original input!
	if ($tmpstring) {
		$string = $tmpstring;
	}

	return $string;
}

function riddles_runevent($type)
{
	require_once("lib/increment_specialty.php");
	global $session;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:riddles";

	require_once("lib/partner.php");
	$partner = get_partner();

	$op = httpget('op');
	if ($op=="" || $op=="search"){
		output("`6`nA short little gnome with leaves in his hair squats beside a small tree.");
		output("He smirks, giggling behind one of his fatty hands.");
		output("For a moment, it looks like he might scramble off into the trees, but after a moment smirks and looks at you.`n`n");
		output("`6\"`@I'll give you a boon,`6\" he says, \"`@if you can think and answer my riddle!`6\"`n`n");
		output("`6He loses himself momentarily in a fit of giggling, then contains his excitement for a moment and continues.`n`n");
		output("`6\"`@But if ere long your guess is wrong, then my boon it will be!`6\"`n");
		output("`6`nDo you accept his challenge?`n`n");
		addnav("Riddle Gnome");
		addnav("Yes", $from."op=yes");
		addnav("No", $from."op=no");
		$session['user']['specialmisc']="";
	}elseif($op=="yes"){
		$subop = httpget('subop');
		if ($subop!="answer"){
			$rid = $session['user']['specialmisc'];
			if (!strpos($rid, "Riddle")) {
				$sq1 = "SELECT * FROM " . db_prefix("riddles") . " ORDER BY rand(".e_rand().")";
			}else{
				// 6 letters in "Riddle"
				$rid = substr($rid, -1*(strlen($rid)-6));
				$sq1 = "SELECT * FROM " . db_prefix("riddles") . " WHERE id=$rid";
			}
			$result = db_query($sq1);
			$riddle = db_fetch_assoc($result);
			$session['user']['specialmisc']="Riddle" . $riddle['id'];
			output("`6Giggling with glee, he asks his riddle:`n`n");
			output("`6\"`@%s`6\"`n`n", $riddle['riddle']);
			output("`6What is your guess?");
			rawoutput("<form action='".$from."op=yes&subop=answer' method='POST'>");
			rawoutput("<input name='guess'>");
			$guess = translate_inline("Guess");
			rawoutput("<input type='submit' class='button' value='$guess'>");
			rawoutput("</form>");
			addnav("",$from."op=yes&subop=answer");
		}else{
			$rid = substr($session['user']['specialmisc'], 6);
			$sq1 = "SELECT * FROM " . db_prefix("riddles") . " WHERE id=$rid";
			$result = db_query($sq1);
			$riddle = db_fetch_assoc($result);

			//*** Get and filter correct answer
			// there can be more than one answer in the database,
			// separated by semicolons (;)
			$answer = explode(";", $riddle['answer']);
			foreach($answer as $key => $answer1) {
				// changed "" to " " below, I believe this is the correct
				// implementation.
				$answer[$key] = preg_replace("/[^[:alnum:]]/"," ",$answer1);
				$answer[$key] = riddles_filterwords($answer1);
			}

			//*** Get and filter players guess
			$guess = httppost('guess');
			$guess = preg_replace("/[^[:alnum:]]/"," ",$guess);
			$guess = riddles_filterwords($guess);

			$correct = 0;
			//changed to 2 on the levenshtein just for compassion's
			// sake :-)  --MightyE
			foreach($answer as $answer1) {
				// Only allow spelling mistakes if te word is long enough
				if (strlen($answer1) > 2) {
					if (levenshtein($guess,$answer1) <= 2) {
						// Allow two letter to be off to compensate for silly
						// spelling mistakes
						$correct = 1;
					}
				} else {
					// Otherwise, they have to be exact
					if ($guess == $answer1) {
						$correct = 1;
					}
				}
			}
			// make sure an empty response from the player is never correct.
			if (!$guess) $correct = 0;

			if ($correct) {
				output("`n`6\"`@Lizards and pollywogs!!`6\" he blusters, \"`@You got it!`6\"`n");
				output("`6\"`@Oh very well.  Here's your stupid prize.`6\"`n`n");
				// It would be nice to have some more consequences
				$rand = e_rand(1, 10);
				switch ($rand){
				case 1:
				case 2:
				case 3:
				case 4:
					output("`^He gives you a `%gem`^!");
					$session['user']['gems']++;
					debuglog("gained 1 gem from the riddle master");
					break;
				case 5:
				case 6:
				case 7:
					output("`^He gives you `%two gems`^!");
					$session['user']['gems']+=2;
					debuglog("gained 2 gems from the riddle master");
					break;
				case 8:
				case 9:
					output("He does the hokey pokey, and turns himself around.");
					output("After that display, you feel ready for battle!`n`n");
					output("`^You gain a forest fight!");
					$session['user']['turns']++;
					break;
				case 10:
					output("He looks deep in your eyes, then whacks you hard across the side of your head.");
					if ($session['user']['specialty'] != "") {
						output("When you come to, you feel a little bit smarter.`n`3");
						increment_specialty("`3");
					}else{
						output("That was a fun lesson.`n`n");
						output("`^You gain some experience!");
						$session['user']['experience'] +=
							$session['user']['level'] * 10;
					}
					break;
				}
			}else{
				output("`n`6The strange gnome cackles with glee and dances around you.");
				output("You feel very silly standing there with a crazy gnome prancing around like a fairy, so you quietly make your exit while he's distracted.");
				output("Somehow you feel like less of a hero with his mocking laughter echoing in your ears.");

				// It would be nice to have some more consequences
				$rand = e_rand(1, 6);
				switch ($rand){
				case 1:
				case 2:
				case 3:
					output("It's not until much later that you also notice some of your gold is missing.`n`n");
					$gold = e_rand(1, $session['user']['level']*10);
					if ($gold > $session['user']['gold'])
						$gold = $session['user']['gold'];
					output("`^You `\$lost`^ %s gold!", $gold);
					$session['user']['gold'] -= $gold;
					debuglog("lost $gold gold to the riddlemaster");
					break;
				case 4:
				case 5:
					output("You don't think you can face another opponent right away.`n`n");
					if ($session['user']['turns']>0) {
						$session['user']['turns']--;
						output("`^You `\$lose`^ a forest fight!");
					} else {
						if ($session['user']['charm']>0)
							$session['user']['charm']--;
						output("`^You `\$lose`^ a charm point due to despair.");
					}
					break;
				case 6:
					output("What would %s`6 think?`n`n",$partner);
					output("`^You `\$lose`^ a charm point!");
					if ($session['user']['charm']>0)
						$session['user']['charm']--;
					break;
				}
			}
			$session['user']['specialinc']="";
			$session['user']['specialmisc']="";
		}
	}elseif($op=="no"){
		output("`n`6Afraid to look the fool, you decline his challenge.");
		output("He was a little bit creepy anyway.`n");
		output("`6The strange gnome giggles hysterically as he disappears into the forest.");
		$session['user']['specialinc']="";
		$session['user']['specialmisc']="";
	}
	output("`0");
}

function riddles_run(){
	$act = httpget("act");
	if ($act=="editor") riddles_editor();
}

function riddles_editor() {
	global $session;
	require_once("lib/nltoappon.php");


	if (!get_module_pref("canedit")) check_su_access(SU_EDIT_RIDDLES);

	$op = httpget('op');
	$id = httpget('id');

	page_header("Riddle Editor");
	require_once("lib/superusernav.php");
	superusernav();
	addnav("Riddle Editor");
	addnav("Riddle Editor Home","runmodule.php?module=riddles&act=editor&admin=true");
	addnav("Add a riddle","runmodule.php?module=riddles&act=editor&op=edit&admin=true");
	if ($op=="save"){
		$id = httppost('id');
		$riddle = trim(httppost('riddle'));
		$answer = trim(httppost('answer'));
		if ($id > "") {
			$sql = "UPDATE " . db_prefix("riddles") . " SET riddle='".nltoappon($riddle)."', answer='$answer' WHERE id='$id'";
		}else{
			$sql = "INSERT INTO " . db_prefix("riddles") . " (riddle,answer,author) VALUES('".nltoappon($riddle)."','$answer','{$session['user']['login']}')";
		}
		db_query($sql);
		if (db_affected_rows()>0){
			$op = "";
			httpset("op", "");
			output("Riddle saved.");
		}else{
			output("The query was not executed for some reason I can't fathom.");
			output("Perhaps you didn't actually make any changes to the riddle.");
		}
	}elseif ($op=="del"){
		$sql = "DELETE FROM " . db_prefix("riddles") . " WHERE id='$id'";
		db_query($sql);
		$op = "";
		httpset("op", "");
		output("Riddle deleted.");
	}
	if ($op==""){
		$sql = "SELECT * FROM " . db_prefix("riddles");
		$result = db_query($sql);
		$i = translate_inline("Id");
		$ops = translate_inline("Ops");
		$rid = translate_inline("Riddle");
		$ans = translate_inline("Answer");
		$auth = translate_inline("Author");

		rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'><tr class='trhead'><td>$i</td><td>$ops</td><td>$rid</td><td>$ans</td><td>$auth</td></tr>");
		for ($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
			rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
			rawoutput("<td valign='top'>");
			output_notl("%s", $row['id']);
			rawoutput("</td><td valign='top'>");
			$conf = translate_inline("Are you sure you wish to delete this riddle?");
			$edit = translate_inline("Edit");
			$del = translate_inline("Delete");
			$elink = "runmodule.php?module=riddles&act=editor&op=edit&id=".$row['id']."&admin=true";
			$dlink = "runmodule.php?module=riddles&act=editor&op=del&id=".$row['id']."&admin=true";
			output_notl("[");
			rawoutput("<a href='$elink'>$edit</a>");
			output_notl("|");
			rawoutput("<a href='$dlink' onClick='return confirm(\"$conf\");'>$del</a>");
			output_notl("]");

			addnav("",$elink);
			addnav("",$dlink);
			rawoutput("</td><td valign='top'>");
			output_notl("`&%s`0", $row['riddle']);
			rawoutput("</td><td valign='top'>");
			output_notl("`#%s`0", $row['answer']);
			rawoutput("</td><td valign='top'>");
			output_notl("`^%s`0", $row['author']);
			rawoutput("</td></tr>");
		}
		rawoutput("</table>");
	}elseif ($op=="edit"){
		$sql = "SELECT * FROM " . db_prefix("riddles") . " WHERE id='$id'";
		$result = db_query($sql);
		rawoutput("<form action='runmodule.php?module=riddles&act=editor&op=save&admin=true' method='POST'>",true);
		addnav("","runmodule.php?module=riddles&act=editor&op=save&admin=true");
		if ($row = db_fetch_assoc($result)){
			output("`bEdit a riddle`b`n");
			$title = "Edit a riddle";
			$i = $row['id'];
			rawoutput("<input type='hidden' name='id' value='$i'>");
		}else{
			output("`bAdd a riddle`b`n");
			$title = "Add a riddle";
			$row = array(
				"riddle"=>"",
				"answer"=>"",
				"author"=>$session['user']['login']);
		}
		$form = array(
			"Riddle,title",
			"riddle"=>"Riddle text,textarea",
			"answer"=>"Answer",
			"author"=>"Author,viewonly"
		);
		require_once("lib/showform.php");
		showform($form, $row);
		rawoutput("</form>");
		output("`^NOTE:`& Separate multiple correct answers with semicolons (;)`n`n");
		output("`7The following are ignored at the start of answers: `&a, an, and, the, my, your, someones, someone's, someone, his, hers`n");
		output("`7The following are ignored at the end of answers: `&s, ing, ed`0`n`n");
		output("`\$NOTE:  Riddles are displayed in the language they are stored in the database.");
		output("Similarly, answers are expected in the language stored in the database.");
	}
	page_footer();
}
?>
