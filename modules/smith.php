<?php
// addnews ready
// mail ready
// translator ready

function smith_getmoduleinfo(){
	$info = array(
		"name"=>"Smiythe the Smith",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Smiythe The Smith Settings,title",
			"improvechance"=>"Chance for Smiythe to improve item,range,0,100,1|60",
			"damagechance"=>"Chance for Smiythe to damage item,range,0,100,1|10",
		),
	);
	return $info;
}

function smith_install(){
	module_addeventhook("travel",
			"return (is_module_active('cities')?100:0);");
	module_addeventhook("forest",
			"return (is_module_active('cities')?0:100);");
	module_addhook("validatesettings");
	return true;
}

function smith_uninstall(){
	return true;
}

function smith_dohook($hookname,$args){
	switch ($hookname) {
	case "validatesettings":
		if ($args['improvechance'] + $args['damagechance'] > 100) {
			$args['validation_error'] = "The two chances must be <= 100.";
		}
		break;
	}
	return $args;
}

function smith_runevent($type, $link)
{
	global $session;
	$from = $link;
	$session['user']['specialinc'] = "module:smith";

	$op = httpget('op');
	if ($op=="" || $op=="search"){
		if ($type == "travel") {
			output("`7You are walking along the trail you are following when you spot a small clearing to the side of the path being taken up by a burly fellow holding a massive hammer in one hand.");
		} else {
			output("`7You step cautiously through the underbrush when you notice a burly fellow holding a massive hammer in one hand.");
		}
		output("Confident that he poses no threat to you, you approach him and say, \"`&Ho, man!`7\".`n`n");
		output("\"`6My name is Smiythe,`7\" he responds.`n`n");
		output("\"`&What?`7\" you ask, always showing your smarts.`n`n");
		output("\"`6Smiythe, that's my name.  I'm a blacksmith.  Smiythe the Smith some call me.  And I'll be glad to offer my smithing services for a fee.`7`n`n");
		output("\"`6For only 1 gem, I can attempt to augment your armor or weapon.  Now I warn you, although I'm the best smith around these parts, I still make my mistakes, and cannot always reliably improve on quality.`7\"");
		addnav("A?Attempt to augment Armor (`%1 gem`0)",$from."op=armor");
		addnav("W?Attempt to augment Weapon (`%1 gem`0)",$from."op=weapon");
		addnav("Don't augment anything",$from."op=none");
	}elseif ($op=="none"){
		output("Smiythe bids you good day and you continue on your way.");
		$session['user']['specialinc']="";
	}elseif ($session['user']['gems']>0){
		$session['user']['specialinc']="";
		$previously_upgraded =
			strpos($session['user'][$op]," +1")!==false ? true : false;
		$previously_downgraded =
			strpos($session['user'][$op]," -1")!==false ? true : false;
		output("`7You hand Smiythe your `#%s`7, which he eyes up carefully.",
				$session['user'][$op]);
		if ($previously_upgraded){
			output("\"`6Aah, I see I've done work on this before.  But I ask you, how can you improve on perfection?`7`n`n");
			output("\"`6No, I'm afraid there is nothing I can do to improve this.  Fare thee well friend!`7\" he says as he disappears into the underbrush.");
		}elseif ($previously_downgraded){
			output("\"`6Aah, I see some butcher has been at this $op!  I'd never produce such terrible quality.  No matter though, I can easily repair the damage!`7\"`n`n");
			output("`^Your %s has been upgraded!", $session['user'][$op]);
			output("It no longer has a -1 penalty!");
			$session['user']['gems']--;
			debuglog("spent a gem at the smith to repair $op");
			$session['user'][$op."value"]*=1.33;
			$session['user'][$op] =
				str_replace(" -1","",$session['user'][$op]);
			$session['user'][$op.($op=="weapon"?"dmg":"def")]+=1;
			$session['user'][($op=="weapon"?"attack":"defense")]++;
		}else{
			$r = e_rand(1,100);
			$ichance = get_module_setting("improvechance");
			$dchance = get_module_setting("damagechance");
			if ($r <= $ichance){
				$session['user']['gems']--;
				debuglog("spent a gem at the smith to upgrade $op");
				output("He examines it for a moment, then pulls an anvil and forge from behind his back, and sets to work.");
				output("In a few hours, he hands back your %s, better than before!", $session['user'][$op]);
				$session['user'][$op] = $session['user'][$op]." +1";
				$session['user'][$op.($op=="weapon"?"dmg":"def")]+=1;
				$session['user'][($op=="weapon"?"attack":"defense")]++;
				$session['user'][$op."value"]*=1.33;
			}else if ($r <= $ichance+$dchance) {
				$session['user']['gems']--;
				debuglog("spent a gem at the smith to upgrade $op but failed");
				output("He examines it for a moment, then pulls an anvil and forge from behind his back, and sets to work.");
				output("A short while later, he mutters, \"`6Oops.`7\" and hands your %s back to you.", $session['user'][$op]);
				output("Your %s was downgraded!", $session['user'][$op]);
				$session['user'][$op] = $session['user'][$op]." -1";
				$session['user'][$op.($op=="weapon"?"dmg":"def")]-=1;
				$session['user'][($op=="weapon"?"attack":"defense")]--;
				$session['user'][$op."value"]*=0.75;
			} else {
				// The two chances don't cover the space, Smith does
				// nothing (default 30%).
				output("\"`6Not much I can do here I'm afraid, friend.`7\" he says as he hands it back to you.");
			}
		}
	}else{
		output("You do not have the gems to augment your equipment, so you slowly walk away, ashamed of your pauperism.");
		$session['user']['specialinc']="";
	}
}

function smith_run(){
}
?>
