<?php
// addnews ready
// mail ready
// translator ready

//Added another event for 2/3 days before Christmas with buff
//Limited receipt of buffs to once per day

function stocking_getmoduleinfo(){
	$info = array(
		"name"=>"Christmas Stocking",
		"author"=>"Talisman and Robert",
		"version"=>"1.3",
		"category"=>"Inn",
		"download"=>"core_module",
		"settings"=>array(
			"Christmas Stocking Settings,title",
			"date"=>"Date of holiday (mm-dd) [12-25 for Christmas],|12-25",
			"prizes"=>"Number of Stocking Presents taken,viewonly|0",
		),
		"prefs"=>array(
			"Christmas Stocking User Prefs, title",
			"gotprez"=>"Has the player got gift,bool|0",
			"gotbuff"=>"Has the player got the buff today,bool|0",
		)
	);
	return $info;
}

function stocking_install(){
	global $session;
	module_addhook("inn-desc");
	module_addhook("inn");
	module_addhook("moderate");
	module_addhook("newday");

	return true;
}

function stocking_uninstall(){
	return true;
}

function stocking_dohook($hookname, $args){

	global $session;
	switch($hookname){
	case "newday":
		set_module_pref("gotbuff",0);
		break;
	case "moderate":
		$args['fireplace'] = translate_inline("Inn Fireplace");
		break;
	case "inn-desc":
		output("`n`6You see an inviting fireplace, and some people gathered around.`n");
		break;
	case "inn":
		addnav("Things to do");
		addnav("Walk over to the Fireplace","runmodule.php?module=stocking");
		break;
	}
	return $args;
}

function stocking_run(){
	global $session;
	require_once("lib/commentary.php");
	addcommentary();
	page_header("The Fireplace");
	addnav("Leave Fireplace");
	addnav("R?Return to Inn","inn.php");
	output("`c`b<font size='+1'>`\$The Fireplace`b`c`n`n`&</font>",true);
	output(" `&You stroll over to the `7large, granite stone, `&fireplace.");
	output("You can see there are many logs ablaze and the fire is cracklin'. `n`n");

	$holiday = get_module_setting("date") . "-" . date("Y");
	// Make the holiday string into something that strtotime likes.
	$holiday = str_replace("-", "/", $holiday);
	$htime = strtotime($holiday);
	$days = ceil(($htime - time())/86400);

	$bard = translate_inline(getsetting("bard", "`^Seth"));
	$barmaid = translate_inline(getsetting("barmaid", "`%Violet"));

	if ($days > 8 && $days < 30){
		output("`&You notice %s the Barmaid`& and %s the Bard`& putting up a Christmas tree.", $barmaid, $bard);
		output("You watch as they decorate the tree with beautiful ornaments.`n`n");
		output("`b`2There are %s days until Christmas !`b`n`n`0", $days);
		set_module_pref("gotprez",0);
	} elseif (($days == 8) || ($days == 7)){
		output("`7You notice %s the Barmaid`7 busily hanging stockings on the mantle above the fireplace.",$barmaid);
		output("As you get closer, you can see names scribed upon each stocking.");
		output("HEY! She's putting one up with YOUR name on it!`n`n");
		output("`b`2There are %s days until Christmas !`b`n`n`0", $days);
	} elseif ($days == 6) {
		output("`& You notice all the `2s`\$t`2o`\$c`2k`\$i`2n`\$g`2s `&on the mantle above the fireplace.");
		output("Looking closer, you spot the one with your name scribed upon it.");
		output("It still looks empty!`n`n");
		output("`b`2Only %s shopping days until Christmas !`b`n`n`0", $days);
	} elseif (($days <= 5) && ($days > 3)){
		output("`& The `2s`\$t`2o`\$c`2k`\$i`2n`\$g`2s `&are hung from the mantle with care and are all still as empty as they were yesterday.");
		output("You recognize some of the villagers gathered around the Fireplace singing Christmas Carols.`n`n");
		output("`b`2Only %s days until Christmas !`b`n`n`0", $days);
	} elseif (($days <= 3) && ($days > 1)){
		output("`^It's almost Christmas!");
		output("Only %s more days!`n`n", $days);
		output("`&The `2s`\$t`2o`\$c`2k`\$i`2n`\$g`2s `&are hung from the mantle with care, you hope for a prize but find they are bare..");
		output("You recognize some of the villagers gathered around the Fireplace laughing merrily.`n`n");
		if (!get_module_pref("gotbuff")) {
			$buff = array(
				"name"=>"`2EggNog Dementia`0",
				"rounds"=>15,
				"wearoff"=>"`4`bYou could use more EggNog, but there is none to be found!.`b`0",
				"atkmod"=>1.1,
				"defmod"=>1.1,
				"roundmsg"=>"`2You become a whirling dervish with your EggNog Dementia!`0"
			);
			set_module_pref("gotbuff",1);
			apply_buff("magicweak", $buff);
			output("%s `&hands you some EggNog which you drink eagerly.`n`n",$barmaid);
		} else {
			output("You stand for a while enjoying the spirit before wandering away.`n`n");
		}
	} elseif ($days == 1){
		output("`^It's Christmas Eve!`n`n");
		output("`&The `2s`\$t`2o`\$c`2k`\$i`2n`\$g`2s `&are hung from the mantle with care, still as empty as they were yesterday.");
		output("Maybe there will be a little something from `\$S`&a`\$n`&t`\$a `&in them tomorrow.");
		output("You recognize some of the villagers gathered around the Fireplace singing Christmas Carols.`n`n");
		if (!get_module_pref("gotbuff")) {
			$buff = array(
				"name"=>"`2Hot Cocoa Melee`0",
				"rounds"=>15,
				"wearoff"=>"`4`bYou come down from that Hot Cocoa high!.`b`0",
				"atkmod"=>1.1,
				"defmod"=>1.1,
				"roundmsg"=>"`2The heat from the cocoa adds fire to your fight!`0"
			);
			set_module_pref("gotbuff",1);
			apply_buff("magicweak", $buff);
			output("%s `&hands you some Hot Cocoa which you drink eagerly.`n`n",$barmaid);
		} else {
			output("You stand for a while enjoying the spirit before wandering away.`n`n");
		}
	} elseif ($days > -3 && $days < 1) {
		if (get_module_pref("gotprez")) {
			output("You check your stocking, but it is as empty as you left it.`n`n");
			output("`\$M`@e`\$r`@r`\$y `\$C`@h`\$r`@i`\$s`@t`\$m`@a`\$s`@!!!`n`n`0");
		} else {
			$name=$session['user']['name'];
			output("`& You glance over the `2s`\$t`2o`\$c`2k`\$i`2n`\$g`2s `&hanging above the fireplace.");
			output("You search the stockings for the one with %s scribed upon it.", $name);
			output("Ah! There it is!!`n`n");
			output("`&Hey! It looks as if your stocking is full!!`n`n");
			set_module_pref("gotprez",1);
			set_module_setting("prizes",get_module_setting("prizes")+1);
			output("`^Looking inside you eagerly pull out your gift!`n`n");
			switch (e_rand(1,7)) {
			case 1:
				output("`&Someone has given you 2 `@forest fights`&!");
				$session['user']['turns']+=2;
				debuglog("received 2 fights from the Fireplace in the Inn");
				break;
			case 2:
				output("`&Someone has given you `%%s gems`&!", 2);
				$session['user']['gems']+=2;
				debuglog(" received 2 gems from the Fireplace in the Inn");
				break;
			case 3:
				output("`&Someone has given you `^%s gold`&!", 200);
				$session['user']['gold']+=200;
				debuglog(" received 200 gold from the Fireplace in the Inn");
				break;
			case 4:
				output("`&Someone has given you `%%s gems`&!", 3);
				$session['user']['gems']+=3;
				debuglog(" received 3 gems from the Fireplace in the Inn");
				break;
			case 5:
				output("`&Someone has given you `^%s gold`&!", 300);
				$session['user']['gold']+=300;
				debuglog(" received 300 gold from the Fireplace in the Inn");
				break;
			case 6:
				output("`&Someone has given you a `^basket of cheer`&!");
				$buff = array(
					"name"=>"`2Holiday Cheer`0",
					"rounds"=>35,
					"wearoff"=>"`4`bYour happiness fades!.`b`0",
					"atkmod"=>1.2,
					"defmod"=>1.2,
					"roundmsg"=>"`2Your joy increases your fighting abilities!`0"
				);
				apply_buff("magicweak", $buff);
				break;
			case 7:
				output("`&Someone thinks you haven't been a deserving person this year...");
				output("`&Your stocking is full of `)coal`&!");
				break;
			}
			output("`n`n`\$M`@e`\$r`\$r`@y `\$C`@h`\$r`@i`\$s`@t`\$m`@a`\$s`@!!!`n`n`0");
		}
	} elseif ($days <= -3){
		output("Christmas has come and gone.");
		output("You see the remnants of celebrations scattered around the inn, and most of the now empty stockings have been removed from the fireplace.`n`n`0");
	}
	modulehook("fireplace");
	commentdisplay("", "fireplace", "Chat around the fire", 25, "chats");

	page_footer();
}

?>
