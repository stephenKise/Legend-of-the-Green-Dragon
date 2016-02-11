<?php

/*
   I have to start by Crediting Sneakabout.  This is, blatantly, the heart, or
   at least the bowel, of his module Orb of Souls, ripped out, and placed into
   the shades.  This is here so that everybody might learn the answer to
   "what happens to dragons when they die.  Credit, as always to Saucy,
   Talisman, Deimos, JCP, Kendaer and the rest for making sure this didn't
   have completely incomprehensible and nonfunctional workings.
*/
function graveofdragons_getmoduleinfo(){
    $info = array(
        "name"=>"Grave of Dragons",
        "version"=>"1.0",
        "author"=>"Nightwind",
        "category"=>"Graveyard Specials",
        "download"=>"core_module",
		"settings"=>array(
			"Grave of Dragons Settings,title",
			"mingold"=>"Minimum amount of gold to find while searching,range,10,100,5|50",
			"maxgold"=>"Maximum amount of gold to find while searching,range,150,500,10|200",
			"mingems"=>"Minimum amount of gems to find while searching,range,1,5,1|2",
			"maxgems"=>"Maximum amount of gems to find while searching,range,1,10,1|4",
			"lethality"=>"Percent of soulpoints to take when encountering the beast, range,10,100,10|50"
		),
    );
    return $info;
}

function graveofdragons_install(){
	module_addeventhook("graveyard", "return 100;");
    return true;
}

function graveofdragons_uninstall(){
    return true;
}

function graveofdragons_dohook($hookname,$args){
    return $args;
}

function graveofdragons_runevent($type,$from)
{
    global $session;
	$from = "graveyard.php?";
	$op = httpget('op');
    $session['user']['specialinc'] = "module:graveofdragons";
	$death = getsetting('deathoverlord', '`$Ramius');

	if ($op==""){
		output("`7In the distance in the vast fields of the dead, you see an object like a vast monolith through the fog.");
		output("`7As you approach through the swirling mists you start to notice a dark spot at the base of the obelisk.");
		output("`7A quick glance at the stone shows it to be unreadable.  You think that you might be able to enter this crevice.`n`n");
		if ($session['user']['soulpoints']>0){
			addnav("Descend into the Crack",$from."op=entercavern");
			output("Enter the darkness?");
		} else {
			Output("`7Although you drift over to the entrance to the cavern, you feel too tired to make it down that far.");
		}
		addnav("Return to the Graves",$from."op=depart");
	} elseif ($op=="depart"){
		output("`7You turn away from the ominous looking dark pit at the base of the obelisk, put it to your back, and walk back into the fields of the dead.");
		output("`7As the mists close in around it, a strange bestial roar seems to echo back at you.");
		output("Indeed, tis better safe, than sorry, even for the dead.");
		$session['user']['specialinc'] = "";
	} elseif ($op=="surface"){
		output("`7Upon realizing the bones are those of dragons, you've seen too much.");
		output("`7Bolting for the surface before you have done more then realize the need to be out of there, you recognize the sounds of the screams behind you now.");
		output("`7Reaching the surface, without a pause you run from the stone obelisk, and into the mists.");
		$session['user']['specialinc'] = "";
	} elseif ($op=="entercavern"){
		output("`7As you wander into the huge cavern, you can see bones everywhere, of all sizes, but the majority huge beyond any frame.");
		// Changed this back.. draconic is correct.
		output("`7The shape of the first skull you see confirms it to you - the draconic features are unmistakable.");
		output("`7You wonder how the skeletons all got down here, but are more immediately struck by the thought of exploration.`n`n");
		if ($session['user']['gravefights']>0) {
			addnav("Search the Cavern",	$from."op=searchcavern");
			output("`7There are most probably hidden dangers in this vast place, what do you wish to do?");
		} else {
			Output("`7When you see the size of the dimly lit cavern you realize you can't stand the torment of the search.");
		}
		addnav("Return to the Surface",$from."op=surface");
	} elseif ($op=="searchcavern"){
		$randevent=round(e_rand(1,17),0);
        switch ($randevent) {
		case 1:
			output("`7As you make your way to the furthest, darkest part of the cavern, you almost imagine that you can sense sounds coming from a shaft in the ground.");
			output("`7You peer down, to be blinded by a shining white light.");
			output("`7Though you fall back and rub your eyes to clear them, you only manage to see two huge glowing red eyes emerge from the pit before being horribly attacked.");
		$session['user']['soulpoints']-=round(((get_module_setting("lethality"))/100)*$session['user']['soulpoints'],0);
			if ($session['user']['soulpoints']==0) {
				output("`7You drift back to the shades, destroyed.");
				$session['user']['gravefights']=0;
				$session['user']['specialinc'] = "";
			} else {
				Output("`7You're barely able to drag your battered form back to the surface, haunted by the image of those terrible eyes.");
				$session['user']['gravefights']--;
			}
			break;
		case 2:
		case 3:
			output("`7As you wander amongst the piles of bones, you catch sight of a curious arrangement, hidden betwixt huge skulls of dragons past.");
			output("`7In front of you lies an altar made of bones, covered by a blood-red cloth with a small pile of gold lying on a bone plate in the center.`n");
			output("`7While there are candles burning, they shed no light.`n`n");
			output("What do you wish to do?");
			addnav("Take the Gold",$from."op=takegold");
			addnav("Pray at the Altar",$from."op=darkaltar");
			addnav("Return to the surface",$from."op=turn");
			break;
		case 4:
		case 5:
		case 6:
			output("`7While you were looking under a pile of smaller bones, you find a skeletal hand clutching a small bag and journal.");
			output("`7You flick through the journal and it tells of some boring exploration as a guide, leading someone to `i\"The First\"`i. Dull, dull, dull.");
			output("`7However, the bag is far more worthwhile, filled as it is with gold - score one for grave robbing!");
			$goldrand=e_rand(get_module_setting("mingold"),
					get_module_setting("maxgold"));
			output("`n`n`&You gain `^%s gold`&!",$goldrand);
			debuglog("gained $goldrand gold from the body of a guide in the shades");
			$session['user']['gold']+=$goldrand;
			$session['user']['specialinc'] = "";
			break;
		case 7:
		case 8:
		case 9:
		case 10:
			output("`7As you drift through the cavern, poking at interesting piles of bones, nothing of interest happens.");
			$session['user']['specialinc'] = "";
			break;
		case 11:
		case 12:
			output("`7While looking around at the half-buried skeleton of a long-dead dragon, you sigh at the thought of all the gold and gems which have already been stolen from these corpses.");
			output("`7Then while kicking aimlessly at the dirt, you note a gleam of gold.... the skeletons have been here so long their treasure has been buried!`n`n");
			$randgold=e_rand(get_module_setting("mingold"),
					get_module_setting("maxgold"));
			$randgems=e_rand(get_module_setting("mingems"),
					get_module_setting("maxgems"));
			$session['user']['gems']+=$randgems;
			$session['user']['gold']+=$randgold;
			output("`7After some serious excavations, you walk away with `^%s gold`7 and `%%s %s`^.",$randgold,$randgems, translate_inline($randgems==1?"gem":"gems"));
			debuglog("gained $randgold gold and $randgems gems digging in the Dragon's Graveyard.");
			$session['user']['specialinc'] = "";
			break;
		case 13:
		case 14:
			output("`7While looking around at the half-buried skeleton of a long-dead dragon, the skull seems to shift in the earth.");
			output("`7A little spooked, you edge closer to the head, before running in fear at the light still flickering from its eyes!`n`n");
			output("%s`) curses you for your cowardice.`n`n", $death);
			$favor = 5 + e_rand(0, $session['user']['level']);
			if ($favor > $session['user']['deathpower'])
				$favor = $session['user']['deathpower'];
			if ($favor > 0) {
				output("`)You have `\$LOST `^%s`) favor with %s`).", $favor, $death);
				$session['user']['deathpower']-=$favor;
			}
			$session['user']['specialinc'] = "";
			break;
		case 15:
		case 16:
		case 17:
			output("`7While examining the huge skeleton of a dragon, you notice the flickering of something deep within the eye-sockets... something... sinister.");
			output("Of course, you immediately climb in as far as you can into the eye-sockets to find that someone has inset gems to the skull.");
			output("How very strange.`n`n");
			output("Well, they can't have valued them that highly - yoink!");
			output("They're yours now.");
			$session['user']['gems']+=2;
			debuglog("gained 2 gems from the eye sockets of a dead dragon.");
			$session['user']['specialinc'] = "";
			break;
		}
	} elseif ($op=="turn") {
		output("`7You turn from this disturbing place of darkness, and before your will breaks, run for the surface");
		$session['user']['specialinc'] = "";
	} elseif ($op=="darkaltar"){
		output("`7You kneel down to pray before the dark altar...`n`n");
		output("`7Your mind is filled with disturbing images!`n");
		output("`7You quickly stumble away from this unholy place, barely sane.`n");
		$favor=((get_module_setting("lethality"))+10);
		output("%s`7 thanks you for the sacrifice.  You gain `^%s`7 favor.",$death, $favor);
		$session['user']['deathpower']+=$favor;
		$session['user']['soulpoints']=1;
		$session['user']['specialinc'] = "";
	} elseif ($op=="takegold"){
		output("`7As you sneak forwards you can feel dark eyes all around you.");
		output("`7As you grab the gold and go to flee, a cry of anger rings out and you are frozen to the spot.");
		output("`7Whilst an impression of great fury fills your head, you realize that it was from %s`7's altar which you stole!", $death);
		output("Eventually, you drop the gold, are freed and return to the shades, shaken and not in %s`7's best books.", $death);
		debuglog("Lost all favour from steeling from the cursed altar of $death.");
		$session['user']['deathpower']=0;
		$session['user']['specialinc'] = "";
	}
}

function graveofdragons_run(){
}
?>
