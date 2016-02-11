<?php
// translator ready
// addnews ready
// mail ready

/* Crying Lady */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 31 Aug 2004 */

// This module checks for a diamond ring found by the player
// in the Breakin module.
// Without the Breakin module, this special will have a 0 raw chance.

// 3rd Sept ver 1.1 now interfaces with Matthias the Astute


require_once("lib/http.php");

function crying_getmoduleinfo(){
	$info = array(
		"name"=>"Crying Lady",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Inn Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Crying Lady Settings, title",
			"rawchance"=>"Raw chance of seeing the lady,range,5,50,5|25",
		),
		"prefs"=>array(
			"Crying Lady User Preferences,title",
			"seentoday"=>"Has player seen the lady today,bool|0",
			"hasbracelet"=>"Does the player have the bracelet,bool|0",
			"upmatt"=>"Has Matthias been updated,bool|0",
		)
	);
	return $info;
}

function crying_seentest(){
	// only if they haven't seen her today, only if they have the ring and
	// no bracelet, and only in the Capital's Inn
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	if (get_module_pref("ring","breakin") == 1 &&
			get_module_pref("seentoday", "crying") == 0 &&
			($vname == $session['user']['location']) &&
			get_module_pref("hasbracelet", "crying")==0) {
		$canvisit = 1;
	}else{
		$canvisit = 0;
	}
	$rawchance=get_module_setting("rawchance","crying");
	$chance=($canvisit?$rawchance:0);
	return $chance;
}

function crying_install(){
	global $session;
	module_addeventhook("inn",
			"require_once(\"modules/crying.php\"); return crying_seentest();");
	module_addhook("newday");
	return true;
}


function crying_uninstall(){
	return true;
}

function crying_dohook($hookname,$args){
	global $session;
	switch($hookname){
		case "newday":
			set_module_pref("seentoday",0);
		break;
	}
	return $args;
}

function crying_runevent($type) {
	global $session;
	$innname = getsetting("innname", LOCATION_INN);
	$op = httpget('op');
	$session['user']['specialinc'] = "module:crying";
	$from = "inn.php?";

	require_once("lib/partner.php");
	$partner = get_partner();

	if ($op == "") {
		output("`7As you are standing in %s, minding your own business, a woman begins to sob loudly.",$innname);
		output("You watch as she walks away from %s`7 and over to where her husband is sitting, crying, \"I lost it, and now HE has lost it too! We shall never afford another ring as beautiful as that one!\"`n`n", getsetting("barkeep", "`tCedrik"));
		output("You watch her as she wrings her hands and cries inconsolably.`n`n");
		output("Your hand strays to the piece of jewelry in your pocket, and you wonder whether you should return the ring to her.");
		output("After all, it might be very valuable, and perhaps you could sell it for gems.");
		output("You struggle with your conscience, wondering what to do.");
		set_module_pref("seentoday",1);
		addnav("Return the Ring",$from."op=give");
		addnav("Ignore the Lady",$from."op=ignore");
		page_footer();
	}elseif ($op == "give") {
		set_module_pref("ring",0,"breakin");
		$whathappens=e_rand(1,5);
		output("You approach the lady and gently touch her shoulder.");
		output("As she turns around and faces you with teary eyes, you extend your hand and offer the ring.`n`n");
		output("Her eyes become saucers, and she grabs the ring in gratitude, before throwing her arms about you in ecstatic gratitude.`n`n");
		output("`&\"You are indeed a warrior of true noble heart! Please, accept this gift of my gratitude, and wear it with pride!\"");
		output("`7She grabs your wrist, and around it ties a leather band that carries a tiger's tooth.");
		output("You're a little hesitant to accept such a strange gift, but you don't want to offend her, so you thank her and walk away from her table.");
		set_module_pref("hasbracelet",1);
		$upmatt = get_module_pref("upmatt");
		// need to check if matthias module exists on this server, and only award astute once
		if (is_module_active("matthias") && $upmatt == 0){
			$astute=get_module_pref("astuteness","matthias");
			$astute+=2;
			set_module_pref("astuteness",$astute,"matthias");
			set_module_pref("upmatt",1);
		}

		if ($whathappens<=2){
			// Seth/Violet sees and approves
			output("`n`nFrom the other side of the room, %s`7 is watching.`n`n", $partner);
			if ($session['user']['sex'] == SEX_MALE) {
				output("She beams at you for your kindheartedness.`n`n");
			} else {
				output("He beams at you for your kindheartedness.`n`n");
			}
			output("`&You gain a charm point!");
			$session['user']['charm']++;
		}elseif ($whathappens==3){
			// Seth/Violet sees and disapproves
			output("`n`nFrom the other side of the room, %s`7 is watching.`n`n", $partner);
			if ($session['user']['sex'] == SEX_MALE) {
				output("She glares at you in jealous anger.`n`n");
			} else {
				output("He glares at you in jealous anger.`n`n");
			}
			output("You `4lose`7 a charm point!");
			if ($session['user']['charm'] > 0){
				$session['user']['charm']--;
			}
		}elseif ($whathappens>=4){
			output("You are so tremendously happy at her reaction, that you feel fantastic!");
			// gain feelgood vibes (buff)
			apply_buff('feelgood',
			array(
				"name"=>"`%Feelgood Vibes",
				"rounds"=>15,
				"wearoff"=>"You feel normal again.",
				"atkmod"=>1.05,
				"roundmsg"=>"Your positivity helps you hit harder!",
				"schema"=>"module-crying"
				)
			);
		}
		$session['user']['specialinc'] = "";
	}elseif ($op == "ignore")  {
		output("`n`7The lady is still sobbing, but hey, you're a heartless warrior, and you don't care. Right?`n");
		$session['user']['specialinc'] = "";
	}
}

function crying_run(){
}

?>
