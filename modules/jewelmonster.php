<?php

function jewelmonster_getmoduleinfo(){
	$info = array(
		"name"=>"Jewelry Monster",
		"author"=>"Shannon Brown, based on tatmonster by Chris Vorndran",
		"version"=>"1.0",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Jewelry Monster Settings,title",
			"expgain"=>"Experience multiplier,floatrange,0,1,.1|.1",
			"hploss"=>"HP to take the monster down per jewel item on the user,int|12",
			"dk"=>"Under what DK do players get granted 'grace' jewelery items (makes monster easier),int|8",
			"grac"=>"Add how many jewelry pieces as a 'grace' for low DK players?,int|2",
			"Jewel Monster Specs,title",
			"name"=>"Name of Jewel Monster,text|Gorgon",
		),
		"requires"=>array(
			"jeweler"=>"1.0| by Shannon Brown",
		),
	);
	return $info;
}

function jewelmonster_install(){
	module_addeventhook("forest", "return 20;");
	return true;
}

function jewelmonster_uninstall(){
	return true;
}

function jewelmonster_runevent($type,$link){
	global $session;


	$op = httpget('op');
	$session['user']['specialinc'] = "module:jewelmonster";
	$battle = false;


	switch ($op){
	case "":
	case "search":
		$count = 0;
		if (is_module_active("jeweler"))
			$count = get_module_pref("totalheld","jeweler");
		output("`3While searching for gems and gold, you feel a shadow fall upon you.");
		output("With a terrible sense of foreboding, you raise your head.");
		output("The creature before your eyes terrifies you to the bone.");
		output("`3From a scaly neck emerges the %s's head of horror, huge tusks leaning towards you in a menacing manner.`n`n", translate_inline(get_module_setting("name")));
		if ($count > 0)
			output("Catching sight of your jeweled adornments, it rears back for a moment, before moving towards you once again and snarling.");
		output("`3Snakes in its hair tell you this fight is very real, and you had best prepare!");
		addnav("Fight",$link."op=pre");
		break;
	case "pre":
		$op = "fight";
		httpset("op",$op);

		$count = 0;
		if (is_module_active("jeweler"))
			$count = get_module_pref("totalheld","jeweler");

		if ($session['user']['dragonkills'] <= get_module_setting("dk"))
			$count+=get_module_setting("grace");

		$hpl = get_module_setting("hploss")*$count;
		if ($count == $session['user']['dragonkills'] || $count == 10){
			$monhp = round($session['user']['maxhitpoints']+10)-$hpl;
			$monatk = $session['user']['attack'] * 1.1;
			$mondef = $session['user']['defense'] * 1.1;
		}else{
			$monhp = round($session['user']['maxhitpoints']+40)-$hpl;
			$monatk = round($session['user']['attack']) * 1.2;
			$mondef = round($session['user']['defense']) * 1.2;
		}

		# if we have a too small hp, just set it to something more reasonable.
		if ($monhp < 10)
			$monhp = $session['user']['maxhitpoints']+($hpl/2);

		// even out his strength a bit
		$badguylevel = $session['user']['level']+1;
		if ($session['user']['level'] > 9) $monhp*=1.05;
		if ($session['user']['level'] < 4) $badguylevel--;

		$badguy = array(
			"creaturename"=>translate_inline(get_module_setting("name")),
			"creatureweapon"=>translate_inline("Rotten Tusks"),
			"creaturelevel"=>$badguylevel,
			"creaturehealth"=>round($monhp),
			"creatureattack"=>$monatk,
			"creaturedefense"=>$mondef,
			"noadjust"=>1,
			"diddamage"=>0,
			"type"=>"jewelmonster"
		);

		$session['user']['badguy'] = createstring($badguy);
		break;
	}

	if ($op == "fight"){
		$battle = true;
	}

	if ($battle){
		include("battle.php");
		if ($victory){
			output("`n`n`3You have overcome %s!", translate_inline(get_module_setting("name")));
			if (get_module_pref("totalheld","jeweler") > 0)
				output("Your jewelry burns your skin, as if to remind you of your narrow escape.`n`n");
			output("You aren't waiting around to see if it is dead or just resting!`n`n");
			if ($session['user']['hitpoints'] <= 0) {
				output("`n`n`^With the last of your energy, you press a piece of cloth to your wounds, stopping your bloodloss before you are completely dead.`n");
				$session['user']['hitpoints'] = 1;
			}
			$exp = round($session['user']['experience'] *
					get_module_setting("expgain"));

			// even out the gain a bit... it was too huge at the top and pathetic at the bottom
			if ($session['user']['level'] > 9) $exp *= 0.8;
			if ($session['user']['level'] < 6) $exp *= 1.2;
			if ($session['user']['level'] == 1) $exp += 20; // to stop people sometimes gaining 2 xp
			$exp = round($exp);

			output("`3The fight earns you `^%s `3experience.`0",$exp);
			$session['user']['experience']+=round($exp);
			$badguy=array();
			$session['user']['badguy'] = "";
			$session['user']['specialinc'] = "";
		}elseif($defeat){
			$badguy=array();
			$session['user']['badguy'] = "";
			$session['user']['specialinc'] = "";
			output("`n`n`3With one final crushing blow, %s pins you to the ground.", translate_inline(get_module_setting("name")));
			if ($session['user']['gold'] > 10) {
				output("While you lie there, helpless, its snake hair extricates some of your gold.");
				$lost = round($session['user']['gold']*.2, 0);
				$session['user']['gold']-=$lost;
				debuglog("lost $lost gold to the jewelmonster");
			}
			if (e_rand(0,2) == 2 && $session['user']['gems'] > 4) {
				output("In several snakes' mouths you see 4 of your precious gems.`0",$cl);
				$session['user']['gems'] -= 4;
				debuglog("lost 4 gems to the jewelmonster");
			}
			output("An evil smile comes across the %s's grotesque face, before it leaves you lying on the ground rather than kill you.", translate_inline(get_module_setting("name")));
			$exp = round($session['user']['experience'] *
					get_module_setting("expgain"));
			output("With this humiliation, you lose `^%s `3experience.`0",$exp);
			output("You are able to cling to life... barely.`0");
			$session['user']['experience'] -= $exp;
			$session['user']['hitpoints']=1;
		}else{
			require_once("lib/fightnav.php");
			if ($type == "forest"){
				fightnav(true,false);
			}else{
				fightnav(true,false,$link);
			}
		}
	}
}
?>
