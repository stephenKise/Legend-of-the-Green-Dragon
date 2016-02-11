<?php
/*
Based on v0.9.7 Travelling Mercenaries Forest Special
By Robert (Maddnet) and Talisman (DragonPrime)
*/
// translator ready
// addnews ready
// mail ready

function mercs_getmoduleinfo(){
	$info = array(
		"name"=>"Mercenaries",
		"version"=>"1.0",
		"author"=>"Talisman",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Mercenaries in the Forest,title",
			"cost"=>"Number of gems charged,int|1",
			"dailyuses"=>"How many can be bought in a day (0=unlimited),int|2",
		),
		"prefs"=>array(
			"Mercenaries in the Forest User Prefs, title",
			"gotmerc"=>"Mercs hired today,int|0",
		)
	);
	return $info;
}

function mercs_install(){
	module_addeventhook("forest", "return (is_module_active('cities')?0:100);");
	module_addeventhook("travel", "return (is_module_active('cities')?100:0);");
	module_addhook("newday");
	return true;
}

function mercs_uninstall(){
	return true;
}

function mercs_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		set_module_pref("gotmerc",0);
		break;
	}
	return $args;
}

function mercs_runevent($type, $link)
{
	global $session;
	$session['user']['specialinc'] = "module:mercs";
	$from = $link;

	$op = httpget('op');
	if ($op==""){
		$uses = get_module_setting("dailyuses");
		$had = get_module_pref("gotmerc");
		$cost = get_module_setting("cost");

		if (($uses > $had) || ($uses==0)){
	    	output("`n`n`2You encounter a Covered Wagon filled with mercenary warriors who clamor loudly for your favor, eager to join you in battle.");
	    	output("`n`nFor the price of only %s %s, one of them will fight at your side for a while.`n`n", $cost, translate_inline($cost == 1? "gem": "gems"));
	    	output("What will you do?");
    		addnav("Hire a Mercenary", $from . "op=give");
    		addnav("Forget it", $from . "op=leave");
		} else {
			output("`n`n`2You again spot the mercenary warriors and their covered wagon, but they pay you no heed.");
		}
	} elseif ($op == "leave") {
		$session['user']['specialinc'] = "";
		output("`@You decide not to avail yourself of their services, as some of them appear rather unsavory and possibly even incompetent.`n`n");
	} elseif ($op == "give") {
		$cost = get_module_setting("cost");
		if ($session['user']['gems']>=$cost){
			output("`n`n`%You are not sure which mercenary to choose, so you tell them to stand on the other side of the wagon, then toss a gem over the wagon.");
			output(" The one who catches it will join you in battle...`n`n ");

			$session['user']['specialinc'] = "";
			$cost = get_module_setting("cost");
			$session['user']['gems'] -= $cost;
			$had = get_module_pref("gotmerc");
			$had += 1;
			set_module_pref("gotmerc",$had);
			debuglog("gave $cost gems to the travelling mercenaries");

			$mercturns = (e_rand(5,15));
			switch(e_rand(1,7)){
			case 1:
				output("Almarea the Paladin catches the gem and will fight with you for a while!");
				$buff = array(
					"name"=>"`#Paladin",
					"rounds"=>$mercturns,
					"wearoff"=>"The Paladin has tired, and leaves you in search of Ale.",
					"defmod"=>1.2,
					"atkmod"=>1.5,
					"roundmsg"=>"The Paladin draws her sword and attacks with a frenzy.",
					"schema"=>"module-mercs"
				);
				break;
			case 2:
				output("Tryxlk the Blind Troll somehow catches the gem and will fight with you for a while!");
				$buff = array(
					"name"=>"`#Blind Troll",
					"rounds"=>$mercturns,
					"wearoff"=>"The Blind Troll loses sight of you.",
					"defmod"=>1.2,
					"atkmod"=>1.0,
					"roundmsg"=>"Tryxlk seems to sense the {badguy}'s position, and swings his sword.",
					"schema"=>"module-mercs"
				);
                break;
			case 3:
				output("Grog the Drunken Dwarf falls on the gem and will fight with you for a while!");
				$buff = array(
					"name"=>"`#Drunk Dwarf",
					"rounds"=>($mercturns-2),
					"wearoff"=>"Grog passes out behind a shrub.",
					"defmod"=>.8,
					"atkmod"=>.8,
					"roundmsg"=>"The Drunk Dwarf proves more of a liability than a help, and stumbles against you.",
					"schema"=>"module-mercs"
				);
				break;
			case 4:
				output("Longstepper the Ranger snatches the gem and will fight with you for a while!");
				$buff = array(
					"name"=>"`#Ranger",
					"rounds"=>$mercturns,
					"wearoff"=>"The Ranger disappears through the forest.",
					"defmod"=>1.3,
					"atkmod"=>1.4,
					"roundmsg"=>"The Ranger attacks the {badguy} with a vengeance.",
					"schema"=>"module-mercs"
				);
				break;
			case 5:
				output("Tavaril the Elf Archer beats the others to the gem and will fight with you for a while!");
				$buff = array(
					"name"=>"`#Elf Archer",
					"rounds"=>$mercturns,
					"wearoff"=>"Tavaril runs out of arrows and returns to her clan.",
					"defmod"=>1.0,
					"atkmod"=>1.4,
					"roundmsg"=>"Tavaril's aim is true and an arrow strikes {badguy}.",
					"schema"=>"module-mercs"
				);
                break;
			case 6:
				output("Dagnar the Veteran Knight catches the gem and will fight with you for a while!");
				$buff = array(
					"name"=>"`#Knight",
					"rounds"=>($mercturns+3),
					"wearoff"=>"Dagnar can no longer maintain your pace, and retires.",
					"defmod"=>1.5,
					"atkmod"=>1.5,
					"roundmsg"=>"The Veteran Knight's skill at arms is let loose on the {badguy}.",
					"schema"=>"module-mercs"
				);
				break;
			case 7:
				output("Bjorn the Pikeman snags the gem and will fight with you for a while!");
				$buff = array(
					"name"=>"`#Pikeman",
					"rounds"=>$mercturns,
					"wearoff"=>"The Pikeman retreats when his weapon shatters.",
					"defmod"=>1.3,
					"atkmod"=>1.1,
					"roundmsg"=>"{badguy} has a hard time avoiding the Pikeman's jabs.",
					"schema"=>"module-mercs"
				);
				break;
			}
			apply_buff("mercbuff", $buff);
		}else{
			$hploss = round($session['user']['hitpoints']*.09);
			if ($hploss > ($session['user']['hitpoints'] - 1))
				$hploss = $session['user']['hitpoints'] - 1;
			$session['user']['hitpoints'] -= $hploss;
			output("`n`n`2You promise to give the mercenaries %s %s, however, when you open your purse, you discover that you don't have that many.", $cost, translate_inline($cost == 1 ? "gem" : "gems"));
			output("The mercenaries draw their weapons and attack you.`n");
			output("`^You lose %s hitpoints before escaping.", $hploss);
		}
	}
}

function mercs_run(){
}
?>
