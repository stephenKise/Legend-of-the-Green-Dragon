<?php


function tatmonster_getmoduleinfo(){
	$info = array(
		"name"=>"Tattoo Monster",
		"author"=>"Chris Vorndran",
		"version"=>"1.0",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Tattoo Monster Settings,title",
			"mincharmloss"=>"Minimum charm loss upon death,int|1",
			"maxcharmloss"=>"Maximum charm loss upon death,int|10",
			"expgain"=>"Experience multiplier,floatrange,0,1,.1|.1",
			"hploss"=>"HP to take the monster down per tattoo on the user,int|10",
			"dk"=>"Under what DK do users get granted 'grace' Tattoos (makes monster easier),int|10",
			"grace"=>"How many tattoos to count as a 'grace' for low DK players,int|2",
			"name"=>"Name of Tattoo Monster,text|Cerberus",
		),
		"requires"=>array(
			"petra"=>"1.0| by Shannon Brown,Part of Core Download",
		),
	);
	return $info;
}

function tatmonster_install(){
	module_addeventhook("forest", "return 30;");
	return true;
}

function tatmonster_uninstall(){
	return true;
}

function tatmonster_runevent($type,$link){
	global $session;

	// Handle the case where Petra gets deactivated.
	if (!is_module_active("petra")) {
		output("You hear a rustling in the underbrush, which dies away after a few moments.`n`n");
		output("When nothing at all happens after a couple of minutes, you continue on your way.");
		return;
	}

	$op = httpget('op');
	require_once("lib/partner.php");
	$partner = get_partner();
	$session['user']['specialinc'] = "module:tatmonster";
	$battle = false;

	switch ($op){
	case "":
	case "search":
		output("`3Walking down a deserted trail, you hear a rustling sound coming from the bushes.");
		output("You can smell something burning, and hear something churning.");
		output("You have no idea if you should check it out, but your curiosity is getting the upper hand.");
		output("`3As you step closer and closer to the bush, the burning scent gets more pronounced and the churning grows louder and louder.`n`n");
		if (get_module_pref("tatnumber", "petra") > 0)
			output("You feel a brief burning sensation from the tattoos on your arm, as if they are reacting to something!`n`n");
		output("`3There is a feeling of dread, deep in your bones.");
		output("Do you want to wait and see what is making the noise, or flee?");
		addnav("W?Wait", $link. "op=wait");
		addnav("R?Run", $link. "op=flee");
		break;
	case "flee":
		$charmloss = e_rand(get_module_setting("mincharmloss")*2,
				get_module_setting("maxcharmloss") *2);
		output("`3Turning around, you hasten back the way you came.");
		output("With a glance backwards, you see a stray cat come out of the trees.`n`n");
		output("Face red with shame, you don't know if you'll ever be able to let %s`3 know that you got scared by a cat!`n`n",$partner);
		output("You lose %s charm from the shame of your cowardice.",
				$charmloss);
		debuglog("lost $charmloss charm from cowardice to the tatmonster");
		$session['user']['charm'] -= $charmloss;
		if ($session['user']['charm'] < 0)
			$session['user']['charm'] = 0;
		$session['user']['specialinc'] = "";
		break;
	case "wait":
		output("`3You wait for a moment to see what transpires.`n`n");
		output("`3Out from the bushes springs the Mighty `#%s`3.",
				get_module_setting("name"));
		output("It lashes out with its three slobbering maws, each of them snarling and growling.");
		output("As you rear back in fear, one of the powerful heads narrowly misses you with its teeth.`n");

		output("Circling around you, the beast blocks your escape!`n");
		addnav("Fight",$link . "op=pre");
		break;
	case "pre":
		$op = "fight";
		httpset("op",$op);

		// accommodate for data left from older versions of petra
		require_once("modules/petra.php");
		petra_calculate();

		// Lets build the Tat Monster NOW!
		$numtats = get_module_pref("tatpower", "petra");
		if ($session['user']['dragonkills'] <= get_module_setting("dk"))
			$numtats += get_module_setting("grace");

		$hpl = get_module_setting("hploss")*$numtats;

		// the test needs to be changed so that it no longer
		// either assumes that one can only obtain ten tattoos,
		// or that $numtats is an integer
		// JT: changed to 8.4 so existing behaviour was preserved.
		if (floor($numtats) == $session['user']['dragonkills'] ||
				$numtats >= 8.4) {
			$monhp = round($session['user']['maxhitpoints']*1.1)-$hpl;
			$monatk = round($session['user']['attack']*1.05);
			$mondef = round($session['user']['defense']*1.05);
		}else{
			$monhp = round($session['user']['maxhitpoints']*1.5)-$hpl;
			$monatk = round($session['user']['attack']*1.15);
			$mondef = round($session['user']['defense']*1.15);
		}
		// If we have too small hp, then just set the monster = to
		// the players hitpoints + 20 %.
		if ($monhp <= 10)
			$monhp = round($session['user']['maxhitpoints'] * 1.2);

		// even out his strength a bit
		$badguylevel = $session['user']['level']+1;
		if ($session['user']['level'] > 9) $monhp*=1.05;
		if ($session['user']['level'] > 3) $badguylevel--;

		$badguy = array(
			"creaturename"=>translate_inline(get_module_setting("name")),
			"creatureweapon"=>translate_inline("Slobbering Maws"),
			"creaturelevel"=>$session['user']['level']+1,
			"creaturehealth"=>round($monhp),
			"creatureattack"=>$monatk,
			"creaturedefense"=>$mondef,
			"noadjust"=>1,
			"diddamage"=>0,
		);
		$attackstack = array(
			"enemies"=>array($badguy),
			"options"=>array("type"=>"tattoomonster")
		);
		$session['user']['badguy'] = createstring($attackstack);
		break;
	}

	if ($op == "fight"){
		$battle = true;
	}

	if ($battle){
		include("battle.php");
		if ($victory){
			output("`n`n`3You have overcome the beast!");
			output("The sensation in your arms slowly fades, and you return to normal.`n`n");
			output("You approach this three-headed monstrosity, to ensure that it truly is dead.");
			output("As you near it, one of its heads slowly opens an eye!");

			if (get_module_pref("tatnumber", "petra") > 0) {
				output("It catches sight of the tattoos on your arms and recoils in horror!");
			}
			output("It twitches some more, and you realize that you have done well even to subdue it, and you had best not remain to give it another chance when it recovers.`n`n");
			if ($session['user']['hitpoints'] <= 0) {
				output("`^With the last of your energy, you press a piece of cloth to your wounds, stopping your bloodloss before you are completely dead.`n");
				$session['user']['hitpoints'] = 1;
			}
			$exp = round($session['user']['experience'] *
					get_module_setting("expgain"));


			// even out the gain a bit... it was too huge at the top and pathetic at the bottom
			if ($session['user']['level'] > 9) $exp *= 0.8;
			if ($session['user']['level'] < 6) $exp *= 1.2;
			if ($session['user']['level'] == 1) $exp += 20; // to stop people sometimes gaining 2 xp
			$exp = round($exp);

			output("`3Achieving this grand feat, you receive `^%s `3experience.`0",$exp);
			$session['user']['experience']+=round($exp);
			$badguy=array();
			$session['user']['badguy'] = "";
			$session['user']['specialinc'] = "";
		}elseif($defeat){
			$badguy=array();
			$session['user']['badguy'] = "";
			$session['user']['specialinc'] = "";
			output("`n`n`3With one final crushing blow, the beast levels you.");
			if ($session['user']['gold'] > 10) {
				output("As the blood escapes your body, your purse splits and yields some of your gold.");
				$lost = round($session['user']['gold']*0.2, 0);
				$session['user']['gold'] -= $lost;
				debuglog("lost $lost gold to the tatmonster");
			}
			$exp = round($session['user']['experience'] *
					get_module_setting("expgain"));
			output("Feeling the pain of loss, you lose `^%s `3experience.`0",
					$exp);
			$session['user']['experience']-=$exp;
			if (e_rand(0,2) == 2) {
				$charmloss = e_rand(get_module_setting("mincharmloss"),
						get_module_setting("maxcharmloss"));
				output("The beast leaves a long, jagged scar on your skin, causing you to lose `5%s `3charm.`0",$charmloss);
				$session['user']['charm']-=$charmloss;
				debuglog("lost $charmloss charm to the tatmonster");
				if ($session['user']['charm'] < 0)
					$session['user']['charm'] = 0;
			}
			output("You are able to cling to life... but just barely.`0");
			$session['user']['hitpoints']=1;
		}else{
			require_once("lib/fightnav.php");
			if ($type == "forest"){
				fightnav(true, false);
			}else{
				fightnav(true, false, $link);
			}
		}
	}
}
?>
