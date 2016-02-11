<?php

// addnews ready
// mail ready
// translator ready

/* Azrael the Spook */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 8th Sept 2004 */

require_once("lib/villagenav.php");
require_once("lib/http.php");

function azrael_getmoduleinfo(){
	$info = array(
		"name"=>"Azrael the Spook",
		"version"=>"1.0",
		"author"=>"Shannon Brown",
		"category"=>"Village Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Azrael the Spook - Settings,title",
			"azraelloc"=>"Where does the Azrael appear,location|".getsetting("villagename", LOCATION_FIELDS)
		)
	);
	return $info;
}

function azrael_install(){
	module_addhook("changesetting");
	module_addeventhook("village",
			"require_once(\"modules/azrael.php\"); return azrael_test();");
	return true;
}

function azrael_uninstall(){
	return true;
}

function azrael_dohook($hookname,$args){
	global $session;
	switch($hookname){
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("azraelloc")) {
				set_module_setting("azraelloc", $args['new']);
			}
		}
		break;
	}
	return $args;
}

function azrael_test(){
	global $session;
	if ($session['user']['location'] ==
			get_module_setting("azraelloc","azrael")) {
		$canappear = 1;
	}else{
		$canappear = 0;
	}
	$chance=($canappear?100:0);
	return $chance;
}

function azrael_runevent($type) {
	global $session;
   	$session['user']['specialinc'] = "";
	$from = "village.php?";
	$city = get_module_setting("azraelloc");
	$op = httpget('op');

	require_once("lib/partner.php");
	$partner = get_partner();

	if ($op == "") {
		$session['user']['specialinc'] = "module:azrael";
		output("`7While you're exploring %s, you are approached by a four-foot-tall, walking sheet.`n`n",$city);
		output("\"`&TRICK OR TREAT!\" `7it screams in a high-pitched voice.`n`n");
		output("You survey the sheet, trying to decide what might be inside.");
		if ($city == get_module_setting("villagename", "ghosttown")) {
			output("After all, this strange tourist town seems genuinely spooky.");
		}
		output("You can't help wondering if it's a child in there, or something far more sinister.`n`n");
		addnav("Trick or Treat");
		if ($session['user']['gold']>0) {
			addnav("Treat (give him 1 gold)",$from."op=treat");
			output("You could give him some gold... `n`n");
		}elseif ($session['user']['gold']==0) {
			output("You don't have any gold to give him...`n`n");
		}
		output("You could risk letting him play a trick on you... or just ignore it and walk away.");
		output("What will you do?");
		addnav("Trick (let him play a trick)",$from."op=trick");
		addnav("Ignore the ghost",$from."op=ignore");
	}elseif($op=="ignore"){
		output("`7You're really not in the mood to let some bratty kid demand gold from you, so you turn your back on him and walk away.`n`n");
		output("`7Seconds later, you find yourself sprawled on the broken cobblestones, as the child trips you over.");
		output("`7You hear the high-pitched giggle of the ghost, and several nearby visitors smother their laughter behind their hands.`n`n");
		output("`7If only %s `7could see you now.",$partner);
		output("`7You're rather banged up, and a face full of gravel is not very attractive!`n`n");
		output("`7You `\$lose `7some charm, and some of your hitpoints!`n");
		if ($session['user']['charm']>0)
			$session['user']['charm']--;
		if ($session['user']['hitpoints']>1)
			$session['user']['hitpoints']*=0.8;
	}elseif($op=="trick"){
		output("`7You're really not in the mood to let some bratty kid demand gold from you, so you agree to let him play a trick.`n`n");
		output("After all, how bad can some little kid be?`n`n");
		$bad=e_rand(1,5);
		if ($bad==1){
			output("He stands very still, and his eyes lock on yours from the holes in the sheet.`n`n");
			output("It is more than a little spooky.");
			output("You're starting to feel seriously unnerved, but you wonder if he's actually going to `bdo`b anything.`n`n");
			output("When you've almost given up waiting, you begin to feel a warm sensation in your hair.`n`n");
			output("As it dawns on you that he has a helper, you catch wind of the concoction being poured over you.");
			output("Its foul odor is breathtaking.`n`n");
			output("You really got tricked by this little ghost.`n");
			if ($session['user']['charm']>1) {
				$session['user']['charm']-=2;
				output("`7You `\$lose `7charm!");
			}
			// Aww heck, let's have the buff survive new day.
			apply_buff('azrael',
				array(
					"name"=>"`@Trickery Stench",
					"rounds"=>60,
					"wearoff"=>"The stench begins to fade.",
					"defmod"=>1.03,
					"survivenewday"=>1,
					"roundmsg"=>"The stench of rotten eggs helps to repel your attacker.",
					)
			);
		}elseif ($bad==2){
			output("A low and gutteral moan eminates from beneath the sheet.");
			output("Chuckling at the child, you turn and walk away.`n`n");
			output("You don't get very far before it begins to chant in a strange language.`n`n");
			output("The hairs on your neck stand on end, and you begin to feel very strange.");
			output("Within seconds you find your legs stiffening, and then your arms.`n`n");
			output("`\$You're paralyzed!`n`n");
			output("`7You are frozen helplessly on the spot as the creature rifles through your purse.");
			$takegold=$session['user']['level']*3;
			$takegems=ceil(($session['user']['level']+1)/5);
			if ($session['user']['gold']==0 && $session['user']['gems']==0){
				output("It grunts in disgust at finding it empty.");
			}elseif ($session['user']['gold']>$takegold){
				output("It helps itself to `^%s gold`7.`n",$takegold);
				$session['user']['gold']=$session['user']['gold']-$takegold;
			}elseif($session['user']['gold']>0){
				output("It helps itself to `^all your gold`7.`n");
				$session['user']['gold']=0;
			}
			if($session['user']['gems']>$takegems){
				output("`7It also takes `5%s gems `7before wandering away.`n",$takegems);
				$session['user']['gems']-=$takegems;
			}elseif($session['user']['gems']>1){
				output("`7It also takes `5all your gems `7before wandering away.`n");
				$session['user']['gems']=0;
			}elseif($session['user']['gems']==1){
				output("`7It also takes `5your only gem `7before wandering away.`n");
				$session['user']['gems']=0;
			}
			debug("Lost $takegold gold and $takegems gems to the trick or treat kid.");
			output("`nAfter a few minutes you are able to begin painfully shifting your aching muscles again.`n");
		}else{
			output("A low and gutteral moan eminates from beneath the sheet.");
			output("Chuckling at the child, you reach out a hand to pat his shoulder.`n`n");
			output("Just as you do, he screams, \"`@`bBOO!!!!!!`b`7\" at the top of his lungs.`n`n");
			output("You almost jump out of your skin!");
			output("Visitors laugh at your reaction, and you feel very embarrassed.");
			output("If only %s `7could see you now.`n`n",$partner);
			output("`7You `\$lose `7charm!`n");
			if ($session['user']['charm'] > 0)
				$session['user']['charm']--;
		}
	}elseif($op=="treat"){
		output("`7You're in the mood for being nice, so you hand over a piece of gold.`n`n");
		output("The child shrieks in excitement, and runs off to his friends to show them the gold.`n`n");
		output("You feel really good about his reaction.`n");
		$session['user']['gold']--;
		apply_buff('azrael',
				array(
					"name"=>"`#Feelgood Vibes",
					"rounds"=>60,
					"wearoff"=>"You float back down to earth.",
					"atkmod"=>1.03,
					"survivenewday"=>1,
					"roundmsg"=>"Your good mood helps you hit harder.",
					)
			);
	}
}
?>
