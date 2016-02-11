<?php
/* The Haunted Library     */
/* ver 1.1 by Chris Murray */
/* 17th Sep 2005           */
/* 19 Oct -- fixed XP bug  */

function hauntedlibrary_getmoduleinfo(){
	$info = array(
		"name"=>"The Haunted Library",
		"version"=>"0.5",
		"author"=>"Chris Murray",
		"category"=>"Village", //  esoterra!
		"download"=>"core_module",
		"settings"=>array(
			"The Haunted Library - Settings,title",
			"hauntedlibraryloc"=>"Where does the library appear,location|".getsetting ("villagename", LOCATION_FIELDS),
			"hploss"=>"Percent of hitpoints that can be lost from a book,range,2,80,2|10",
			"goldmin"=>"Minimum amount gold in the book,int|5",
			"goldmax"=>"Maximum amount gold in the book,int|50",
		),
		"prefs"=>array(
			"The Haunted Library - User Preferences,title",
			"readtoday"=>"Has the user read a book today?,bool|0",
		)
	);
	return $info;
}
function hauntedlibrary_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
	return true;
}

function hauntedlibrary_uninstall(){
	return true;
}

function hauntedlibrary_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		set_module_pref("readtoday",0);
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("hauntedlibraryloc")) {
				set_module_setting("hauntedlibraryloc", $args['new']);
			}
		}
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("hauntedlibraryloc")) {
			tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
			tlschema();
			addnav("The Library","runmodule.php?module=hauntedlibrary");
		}
		break;
	}
	return $args;
}

function hauntedlibrary_run() {
	global $session;
	require_once("lib/increment_specialty.php");
	require_once("lib/villagenav.php");
	$yourname = $session['user']['login'];
	page_header("The Haunted Library");
	$op = httpget('op');
	$readtoday=get_module_pref("readtoday");
	output("`Q`c`bThe `\$Haunted`Q Library`b`c`n");
	if ($op == "") {
		if ($readtoday < 1) {
			output("`QYou cautiously peer into this building.");
			output("`QIt appears that it was a library at one point, but it seems to be long since abandoned.`n");
			output("`QYou walk past shelves that may have held books once, but now hold naught but dust.`n");
			output("`QTurning a corner, you see a desk and a few chairs and couches, illuminated by some source you can't make out.`n`n");
			output("`QYou walk up to the desk, where you see an small brass bell and hammer.");
			output("`QBlowing some dust off of the desk, you find a sign marked \"`\$Ring Bell for Service`Q\".`n");
			output("`QWhat kind of service you can't imagine, however, as the place looks like it's been deserted for years.`n`n");
			output("`QYour hand moves to the hammer, seemingly of its own volition, and rings the bell.");
			output("`QA spectre shimmers into view behind the desk, taking the appearance of a tall, thin, bespectacled man.`n");
			output("`QAlthough you cannot see through the glass of his spectacles, you feel a shiver run down your spine as he holds your gaze for a long moment.`n");
			output("`QSomething in his demeanor changes, and he favors you with an eerie smile.`n`n");
			output("`\$\"Ah, yes, %s. I've been expecting you. Wait right there.\"`n`n", $yourname);
			output("`QThe spectre turns away and disappears into the stacks.`n");
			output("`QAlthough every fiber of your being wants to run screaming into the night, you seem to be rooted to the spot!`n");
			output("After what seems to be a minor eternity, the spectre returns.`n`n");
			output("`\$\"I have the perfect book for you,\"`Q he says, and holds it out for you to take.`n`n");
			output("As he does, you find yourself able to move again.`n");
			output("You're not sure what a book from this place could do to you. ");
			output("You've heard stories of places like this, where the books could suck you in, perhaps steal your very `\$soul`Q!`n`n");
			output("`#Will you take the offered book, or flee this place?");
			addnav("Take the book", "runmodule.php?module=hauntedlibrary&op=stay");
			addnav("Take off running", "runmodule.php?module=hauntedlibrary&op=flee");
		} else {
			output("`Q`nThe library seems completely deserted.`n");
			output("`QYou ring the bell a few times, but nothing happens.");
			villagenav();
		}
	}elseif ($op=="flee") {
		output("`QYou turn on your heel and start to `\$run`Q!`n");
		output("`QThe sound of laughter comes from behind you.");
		output("`QYou look over your shoulder as you run, but the desk, and the entire library, seems to be deserted once more.");
		output("`QThe feeling of overwhelming dread slowly drains from you, and your pace slows correspondingly.`n");
		output("`QYou look over your shoulder once more as you reach the door, wondering what could have been in that book.`n");
		output("`@It's not like books could harm anyone, right? Just words on paper.");
		output("`@Ah well, perhaps you'll never know.");
		output("`@It's probably better that way, you think to yourself.");
		addnav("Leave","village.php");
	}elseif ($op=="stay") {
		set_module_pref("readtoday", get_module_pref("readtoday")+1);
		output("`QYou take the book from the spectre.");
		output("`QYou notice a comfortable-looking seat to your left, and sit down to read the book.`n");
		output("`@You notice the title of the book:");
		$rnd = e_rand(1,20);
		switch ($rnd){
		case 1: case 2: case 3:
			//charm++ (1-3)
			output("`@How To Gain Friends and Make Them Happier!.`n`n");
			output("`QYou page through the book quickly.");
			output("`QYou can't wait to try the techniques out on a friend, or perhaps `\$someone special`Q...`n");
			output("`QYou feel `\$charming`6!");
			$session['user']['charm']++;
			break;
		case 4: case 5: case 6:
			//gold++ (4-6)
			output("`^The Lost Treasure of the Green Dragon!`n`n");
			output("`QThis one looks like it will be a real thriller!`n");
			output("`QYou open the book, and suddenly `6gold`Q pours into your lap!`n");
			$goldmax = get_module_setting("goldmax");
			$goldmin = get_module_setting("goldmin");
			$goldgain = e_rand($goldmin,$goldmax);
			$session['user']['gold'] += $goldgain;
			debuglog("gained $goldgain from the haunted library");
			output("`#You count the gold, and find `6%s`# in total!", $goldgain);
			break;
		case 7: case 8: case 9:
			//hp++ (7-9)
			output("`6I'm Pretty Swell, You Are Too!`n`n");
			output("`QYou read the book, and feel much better.`n");
			if ($session['user']['hitpoints'] <
					$session['user']['maxhitpoints']) {
				$session['user']['hitpoints'] =
					$session['user']['maxhitpoints'];
				output("`\$Your hitpoints are restored to full!`n");
			}else{
				$session['user']['hitpoints'] =
					round($session['user']['hitpoints']*1.10);
				output("`QYou feel `^healthy`Q!`n");
			}
			break;
		case 10: case 11: case 12:
			//ff++ (10-13)
			output("`@The Fantastic Adventures of J.R., the Dragon Hunter!`n`n");
			output("`QThe grand deeds of the protagonist inspire you!`n");
			output("`@You feel like you can take on another `@forest creature`Q!`n");
			$session['user']['turns']++;
			debuglog("gained a turn from the haunted library");
			break;
		case 13:
			//xp++ (13)
			output("`qMighty`QE`q's Guide to Mightyness!`n`n");
			output("`!You are greatly inspired by the words of this book!`n");
			output("`!You feel you understand the world around you a little better.`n");
			$xpgain = round($session['user']['experience'] * 0.05, 0);
			output("`@You gain `7%s`@ experience!", $xpgain);
			$session['user']['experience'] += $xpgain;
			break;

		case 14:
			//special++ (14); if not, gain gem!
			output("`^The Giant Book of Amazing Stuff!`n");
			if ($session['user']['specialty'] != "") {
				output("`#You quickly flip to the section that interests you.`n`n");
				output("`#You understand more about your chosen profession!`n");
				increment_specialty("`3");
			} else {
				output("`QYou quickly skip to the section on `6treasure hunting`Q. `n");
				output("`QAs you finish, you see something `#sparkly`Q in the dust by the desk.`n");
				output("`QYou find a `#gem`Q!");
				$session['user']['gems']++;
				debuglog("gained a gem from the haunted library");
			}
			break;
		case 15: case 16:
			output("`6How To Lose Friends and Make Them Angry!`n`n");
			output("`!Just reading this book makes you feel less social.`n");
			if ($session['user']['charm'] > 0){
				output("`QYou feel much less charming!`n");
				$session['user']['charm']--;
			} else {
				output("`^It doesn't teach you anything you don't already know!");
			}
			break;
		case 17: case 18:
			//ff-- (17-18)
			output("`5The Life And Loves Of The Local Barmaid!`n`n");
			output("`%You find yourself unable to put the book down!`n");
			output("`%When you finally finish, you find that much time has passed.`n");
			if ($session['user']['turns']>0){
				output("`QYou lost a forest fight!`n");
				$session['user']['turns']--;
			}
			break;
		case 19:
			//hp-- (19)
			output("`6Graceful as a Gazelle.`n`n");
			output("`QYou drop the book before you reach the chair.");
			output("As you bend down to reach it, you trip and fall!`n");
			output("You hear a rumble, and look up to see one of the bookcases falling over onto you!`n");
			output("Fortunately, it was empty, but it still hurt!`n");
			$hploss = get_module_setting("hploss");
			$loss = round($session['user']['maxhitpoints'] * ($hploss/100), 0);
			if ($loss >= $session['user']['hitpoints'])
				$loss = $session['user']['hitpoints']-1;
			if ($loss < 0) $loss = 0;
			if ($loss) {
				output("`QYou lose some hit points!`n");
				$session['user']['hitpoints']-= $loss;
			}
			break;
		case 20:
			//xp-- (20)
			output("`\$The Big Book of Vampires!`n`n");
			output("`#You open the book to read it, but as you do, you feel something tugging at you!`n");
			output("`^The book itself is trying to steal your soul!`n");
			output("`#You struggle to close the book, but you feel that you've lost a little piece of yourself in the struggle.`n");
			if ($session['user']['experience'] > 0) {
				$loss = round($session['user']['experience'] * 0.005, 0);
				$session['user']['experience'] -= $loss;
				output("`&You lose some experience!`n");
			}
			break;
		}
		villagenav();
	}
	page_footer();
}
?>
