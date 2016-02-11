<?php

// Dragon Buff-ercizer, a little utility that gives some more options as to
// how to determine the dragon's stats, as well as give the option of
// having the dragon breath fire as a buff.
//
// The three methods of determining the dragon's stats are as follows:
// 1. the standard method built into the core
// 2. a scaled doppleganger where the dragon's atk/def/hp are scaled versions
//    of the players
// 3. linear increase in stats with dks, independant of the choices for dk
//    points.

//Version History
// 1.0 Basic Code
// 1.1 Fixed buff removal typo


function dragonbuffercizer_getmoduleinfo(){
	$info = array(
		"name"=>"Dragon Buffercizer",
		"version"=>"1.1",
		"author"=>"Dan Norton",
		"category"=>"Dragon Mods",
		"download"=>"core_module",
		"settings"=>array(
			"Manual of operations,title",
			"So you want to make the dragon a bit tougher - well you've come to the right place,note",
			"This module will allow the dragon to breath fire as a buff and also give many,note",
			"(perhaps too many) options for determining the dragon's stats,note",
			"There are three options for determining the dragon's stats - the default core,note",
			"method - one based on a doppleganger of the player - and one based strictly on,note",
			"the number of dks the player has. It is possible to choose any of these methods,note",
			"or a mixture of them. The recommended idea would be to slowly alter the mixture,note",
			"from the core's method (the default method) to the final method that is desired,note",
			"so as to not freak out the players,note",
			"Fire Breathing Options,title",
			"breathfire"=>"Allow dragon to breath fire as a buff,bool|false",
			"firebase"=>"basefiredamage - base max buff fire damage,float|0.0",
			"firebydk"=>"firedamageperdk - max buff fire damage per dk,float|0.0",
			"firebydkpoints"=>"firedamageperdkpoints - max buff fire damager per dk points,float|0.0",
			"firechance"=>"chance of dragon breathing fire,range,0,100,5|20",
			"maxfiredamage = basefiredamage + firedamageperdk*numberofdks + firedamageperdkpoints*numberofdkpoints,note",
			"where numberofdkpoints is the dk points spent on attack or defense or one point for every 5 hp over 150,note",
			"Weighting Options and randomization,title",
			"standardweight"=>"W1 - Weighting to give standard dragon settings,float|1.0",
			"doppleweight"=>"W2 - Weighting to give scaled doppleganger dragon settings,float|0.0",
			"dkmodelweight"=>"W3 - Weighting to give dragon kills model for dragon settings,float|0.0",
			"stat = (W1*standardstat+W2*dopplegangerstat+W3*dkstat)/(W1+W2+W3),note",
			"statrandom"=>"Allow further randomization of stats,bool|true",
			"Further randomization is not necessary if only the standard dragon settings are used,note",
			"randompercent"=>"Maximum percentage randomization allowed,int|5",
			"Doppleganger Settings,title",
			"attackratio"=>"ratio of dragons attack to players,float|1.1",
			"defenseratio"=>"ratio of dragons defense to players,float|1.1",
			"hpratio"=>"ratio of dragons hp to players,float|1.5",
			"Dragon stats by dragon kills model,title",
			"dkbasehp"=>"Base dragon hitpoints,int|300",
			"dkhprate"=>"Increase dragon hitpoints by how much per dragon kill,int|3",
			"dkbaseatk"=>"Base dragon attack,int|30",
			"dkatkrate"=>"Increase dragon attack by how much per dragon kill,int|0.4",
			"dkbasedef"=>"Base dragon defense,int|30",
			"dkdefrate"=>"Increase dragon defense by how much per dragon kill,int|0.4",
			"stat = basestat + numberofdks*rate,note",
		),
	);
	return $info;
}

function dragonbuffercizer_install(){
	module_addhook("buffdragon");
	return true;
}

function dragonbuffercizer_uninstall(){
	return true;
}

function dragonbuffercizer_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "buffdragon":
		//Checking for and setting up the dragonfire buff
		$breathfire=get_module_setting("breathfire");
		if($breathfire && e_rand(1, 100) < get_module_setting("firechance")){
			$firebase=get_module_setting("firebase");
			$firebydk=get_module_setting("firebydk");
			$firebydkpoints=get_module_setting("firebydkpoints");
			$dkpoints=0;
			if($firebydkpoints>0){
				$dkpoints=round(($session['user']['maxhitpoints']-150)/5,0);
				//I'm not exactly certain that the following while loop works.
				while(list($key,$val)=each($session['user']['dragonpoints'])){
					if ($val=="at" || $val == "de") $dkpoints++;
				}
			}
			$maxfiredamage=round($firebase +
				$firebydk*$session['user']['dragonkills'] +
				$firebydkpoints*$dkpoints,0);
			apply_buff('dragonfire', array(
				"startmsg"=>"`n`^The Dragon breathes fire down upon you!`n`n",
				"name"=>"`%Dragon Fire",
				"rounds"=>30,
				"wearoff"=>"The dragon's fire appears to be extinguished. It must be getting weak now.",
				"minioncount"=>1,
				"mingoodguydamage"=>0,
				"maxgoodguydamage"=>$maxfiredamage,
				"effectmsg"=>"`7You are engulfed in `4Dragon's fire`7 and take`4 {damage} `7damage.`0",
				"effectnodmgmsg"=>"`7You are engulfed in `4Dragon's fire`7 but take no damage.`0",
				"effectfailmsg"=>"`7Miraculously, the `4Dragon's fire`7 misses you.`0",
				"schema"=>"module-dragonbuffercizer",
			));
			debug("DEBUG: Max $maxfiredamage damage of dragonfire.");
		}

		//Compiling the different methods of dragon stat determination
		$standardweight=get_module_setting("standardweight");
		$doppleweight=get_module_setting("doppleweight");
		$dkmodelweight=get_module_setting("dkmodelweight");

		// The scaled doppleganger model
		$attackratio=get_module_setting("attackratio");
		$defenseratio=get_module_setting("defenseratio");
		$hpratio=get_module_setting("hpratio");
		// starting with a dragon that is the same level as the player,
		// with a level 15 weapon and armor
		$dopplegangeratk=$session['user']['attack']*$attackratio;
		$dopplegangerdef=$session['user']['defense']*$defenseratio;
		$dopplegangerhealth=$hpratio*$session['user']['maxhitpoints'];

		//dkmodel stats
	   	$numdks=$session['user']['dragonkills'];
		$dkmodelhealth=get_module_setting("dkbasehp")+
			$numdks*get_module_setting("dkhprate");
		$dkmodelatk=get_module_setting("dkbaseatk")+
			$numdks*get_module_setting("dkatkrate");
		$dkmodeldef=get_module_setting("dkbasedef")+
			$numdks*get_module_setting("dkdefrate");

		//Adding up the differently weighted components
		$statrandom=get_module_pref("statrandom");
		$args['creatureattack']=round(
			($args['creatureattack']*$standardweight+
			 $dopplegangeratk*$doppleweight+
			 $dkmodelatk*$dkmodelweight)/
			($standardweight+$doppleweight+$dkmodelweight),0);
		$args['creaturedefense']=round(
			($args['creaturedefense']*$standardweight+
			 $dopplegangerdef*$doppleweight+
			 $dkmodeldef*$dkmodelweight)/
			($standardweight+$doppleweight+$dkmodelweight),0);
		$args['creaturehealth']=round(
			($args['creaturehealth']*$standardweight+
			 $dopplegangerhealth*$doppleweight+
			 $dkmodelhealth*$dkmodelweight)/
			($standardweight+$doppleweight+$dkmodelweight),0);
		if($statrandom){
			$randompercent=get_module_setting("randompercent");
			$args['creatureattack']=e_rand(
					round($args['creatureattack']*(1-$randompercent/100),0),
					round($args['creatureattack']*(1+$randompercent/100),0));
			$args['creaturedefense']=e_rand(
					round($args['creaturedefense']*(1-$randompercent/100),0),
					round($args['creaturedefense']*(1+$randompercent/100),0));
			$args['creaturehealth']=e_rand(
					round($args['creaturehealth']*(1-$randompercent/100),0),
					round($args['creaturehealth']*(1+$randompercent/100),0));
		}
		break;
	case "dragonkilltext":
		strip_buff('dragonfire');
		break;
	case "shades":
		strip_buff('dragonfire');
		break;
	}
	$dragonatk=$args['creatureattack'];
	$dragondef=$args['creaturedefense'];
	debug("DEBUG-dragonbuffercizer: dragon attack set to $dragonatk");
	debug("DEBUG-dragonbuffercizer: dragon defense set to $dragondef");
	return $args;
}

function dragonbuffercizer_run(){
}
?>
