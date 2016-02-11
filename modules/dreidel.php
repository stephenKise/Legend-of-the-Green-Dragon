<?php
// translator ready
// addnews ready
// mail ready

/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 6th December 2004 */

require_once("lib/villagenav.php");
require_once("lib/http.php");

function dreidel_getmoduleinfo(){
    $info = array(
        "name"=>"Dreidel",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village",
        "download"=>"core_module",
        "settings"=>array(
            "Dreidel - Settings,title",
			"cost"=>"Price to buy peanuts?,int|5",
			"dreidelloc"=>"Where does the stand appear,location|".getsetting("villagename", LOCATION_FIELDS)
        ),
        "prefs"=>array(
            "Dreidel - User Preferences,title",
			"peanuts"=>"How many sugared peanuts does the player have?,int|0",
			"eattoday"=>"Has the player pigged out today?,bool|0"
        )
    );
    return $info;
}

function dreidel_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
    return true;
}

function dreidel_uninstall(){
    return true;
}

function dreidel_dohook($hookname,$args){
    global $session;
    switch($hookname){
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("dreidelloc")) {
				set_module_setting("dreidelloc", $args['new']);
			}
		}
		break;
   	case "newday":
		set_module_pref("eattoday",0);
		set_module_pref("pot",0);
		set_module_pref("yourturn",0);
		$peanuts=get_module_pref("peanuts");
		if ($peanuts) output("Scowling at the old peanuts from yesterday, you toss them to the birds.`n`n");
		set_module_pref("peanuts",0);
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("dreidelloc")) {
            tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
            tlschema();
			addnav("Peanut Stall","runmodule.php?module=dreidel");
		}
		break;
	}
    return $args;
}

function dreidel_playturn(){
	$spinchance=(e_rand(0,3));
	$gender=get_module_pref("gender");
	$yourturn=get_module_pref("yourturn");
	$peanuts=get_module_pref("peanuts");
	$canplay=get_module_pref("canplay");
	$turn=get_module_pref("turn");
	$pot=get_module_pref("pot");

	// temp hard code of players and game order
	$players = array("felyne kitten", "sprite", "half-elf");
	$players = translate_inline($players);
	// Hard code the genders
	$genders = array(1, 0, 0);

	$side = array("`#Nun", "`#Gimmel",  "`#Hey", "`#Shin");

	if ($pot==1){
		output("`3There is only 1 peanut in the pot, so you each add one to the pot.`n`n");
		$peanuts--;
		$pot+=4;
		set_module_pref("peanuts",$peanuts);
		set_module_pref("pot",$pot);
	}
	if($yourturn==0){
		output("`7The %s spins the dreidel, and it lands on %s`7.`n",
				$players[$turn],$side[$spinchance]);
		switch($spinchance) {
		case 0: // Nun
			if ($genders[$turn]) output("`7She smiles.`n`n");
			else output("`7He smiles.`n`n");
			break;
		case 1: // Gimmel
			if ($genders[$turn])
				output("`#She grins with glee and takes all of the peanuts from the pot!`n`n");
			else
				output("`#He grins with glee and takes all of the peanuts from the pot!`n`n");

			set_module_pref("gamereset",1);
			set_module_pref("pot",0);
			$pot=0;
			if ($peanuts>=1)	{
				output("`7The other two groan.`n`n");
			}elseif ($peanuts<=0) {
				output("`7The other two groan, and you realise you've no peanuts to play any longer.`n`n");
				$peanuts=0;
			}
			set_module_pref("canplay",0);
			set_module_pref("peanuts",$peanuts);
			set_module_pref("gamereset",1);
			set_module_pref("turn",0);
			addnav("Play Again","runmodule.php?module=dreidel&op=play&cont=0");
			break;
		case 2: // Hey
			$pot-=ceil($pot*0.5);
			if ($genders[$turn])
				output("`7She smiles and takes half the pot leaving %s in the pot.`n`n", $pot);
			else
				output("`7He smiles and takes half the pot leaving %s in the pot.`n`n", $pot);
			set_module_pref("pot",$pot);
			break;
		case 3: // Shin
			$pot++;
			if ($genders[$turn])
				output("`7She frowns and adds a peanut to the pot bringing it up to %s.`n`n", $pot);
			else
				output("`7He frowns and adds a peanut to the pot bringing it up to %s.`n`n", $pot);
			set_module_pref("pot",$pot);
		}
		$turn++;
		set_module_pref("turn",$turn);
		$pot=get_module_pref("pot");
	}elseif($yourturn==1 && $peanuts>=0){
		output("`7You spin the dreidel, and it lands on %s.`n",
				$side[$spinchance]);
		switch($spinchance) {
		case 0: // Nun
			output("`7You smile, and wait for the next player to take their turn.`n`n");
			break;
		case 1: // Gimmel
			output("`#You grin with glee and take all of the peanuts from the pot!`n`n");
			addnav("Play Again","runmodule.php?module=dreidel&op=play&cont=0");
			$peanuts+=$pot;
			$pot=0;
			set_module_pref("gamereset",1);
			set_module_pref("pot",0);
			set_module_pref("peanuts",$peanuts);
			output("`7The other three groan, then congratulate you on winning.`n`n");
			apply_buff('dreidel',array("name"=>"`\$Peanut Power!","rounds"=>20,"atkmod"=>1.05));
			set_module_pref("turn",6);
			break;
		case 2: // Hey
			$peanuts+=ceil($pot*0.5);
			$pot-=ceil($pot*0.5);
			output("`7You smile and take half the pot leaving %s in the pot.`n`n", $pot);
			set_module_pref("pot",$pot);
			set_module_pref("peanuts",$peanuts);
			break;
		case 3: // Shin
			$pot++;
			$peanuts--;
			output("`7You frown and add a peanut to the pot bringing it up to %s.`n`n", $pot);
			set_module_pref("pot",$pot);
			set_module_pref("peanuts",$peanuts);
		}
	}
}

function dreidel_learn() {
	output("`3Dreidel is a spinning top game played by Jewish children around the world, traditionally during Hanukkah.");
	output("In Yiddish, the top is called `#sevivon`3, which means \"to turn around\".");
	output("The Dreidel has four sides, each with a letter written in Hebrew.");
	output("They are `#Nun`3, `#Gimmel`3, `#Hey`3 and `#Shin`3 (or sometimes, `#Peh`3).");
	output("Together, they represent the saying, `#\"A great miracle happened there\"`3 (or \"here\", in Jeruselem, where \"Peh\" is used).`n`n");
	output("`b`@How To Play`b`n`n");
	output("`3Any number of players can take part, and all should start with the same number of sweets or coins.");
	output("The rules vary by country and by family, but below are the basic rules we're playing here in LOTGD, with four players.`n`n");
	output("`@You need at least 10 peanuts to play, so you'll need to buy a bag if you haven't enough.`n");
	output("You're a kind warrior, so you allow the youngest players to go first, and you take the last turn.");
	output("`2(You also don't mind that they have more peanuts than you, because after all, they are much younger than you.)`n`n");
	output("`@To start the game, you each put one peanut into the `%pot`@.");
	output("The pot is just a pile of peanuts that you will be playing to win.");
	output("Each player spins the dreidel when it's their turn, and they must give or receive peanuts according to the symbol that shows:`n`n");
	output("`#Nun`2 means \"none\", so the player does nothing.`n");
	output("`#Gimmel`2 means \"everything\", so the player wins the whole pot.`n");
	output("`#Hey`2 means \"half\", so the player takes half the pot, and if there's an odd number, they get the leftover peanut too.`n");
	output("`#Shin`2 means \"put in\" (and `#Peh`2 means \"pay\"), so the player adds a peanut to the pot.`n`n");
	output("`@If you have no peanuts left, you lose.");
	output("If there is only one peanut in the pot, all the players add a peanut.");
	output("Once the pot is empty, the game is over.");
}

function dreidel_run() {
    global $session;
	$op = httpget('op');
	$cont = httpget('cont');
	$cost=get_module_setting("cost");
	$pot=get_module_pref("pot");
	$peanuts=get_module_pref("peanuts");
	$eattoday=get_module_pref("eattoday");
	$canplay=get_module_pref("canplay");
	$gamereset=get_module_pref("gamereset");
	page_header("Peanut Stall");
	output("`&`c`bDreidel Games`b`c`n");
	if ($cont==0 && $peanuts==0 && $session['user']['gold']<$cost){
		output("`7Much as you'd like to play, your purse doesn't yield the peanuts or the gold to play.");
	}elseif($op=="learn"){
		dreidel_learn();
	}elseif($op==""){
		// set these once when player enters
		set_module_pref("gamereset",1);

		output("`7Several children are grouped in small circles around the peanut stall.");
		output("You watch, perplexed, as they play the spinning top game.");
		output("A child in the group closest to you spins a wooden top, groans, and places a red sugared peanut into a pile while the others laugh in merriment.`n`n");
		output("Another child sees you watching, and invites you to play.`n`n");
	}elseif ($op=="buy" && $session['user']['gold']<$cost){
		output("`7Much as you'd like to gorge yourself on sugary food, your purse doesn't yield enough.");
	}elseif ($op=="buy"){
		$session['user']['gold']-=$cost;
		debuglog("spent $cost gold on peanuts.");
		output("`7You approach the seller and hand over your %s gold, and he places a small bag of 10 peanuts into your outstretched hand.`n`n",$cost);
		output("`&\"Thanks for your business, warrior, and enjoy!\"`n`n");
		set_module_pref("canplay",1);
		$peanuts+=10;
		set_module_pref("peanuts",$peanuts);
	}elseif($op=="eat" && $eattoday>=3){
		output("`7You briefly consider eating another bag of the red sweets, but the thought just makes you ill.`n`n");
	}elseif($op=="eat" && $peanuts>=10){
		output("`7You upend a bag of the red sweets into your mouth, and grin at the flavor.`n`n");
		output("You feel `@energized!`n`n");
		$peanuts-=10;
		set_module_pref("peanuts",$peanuts);
		apply_buff('peanuts',array("name"=>"`\$Sugar Rush!","rounds"=>10,"atkmod"=>1.02));
		$eattoday++;
		set_module_pref("eattoday",$eattoday);
	}elseif($op=="eat" && $peanuts==0){
		output("`7How can you eat peanuts, when you don't have any?`n`n");
	}elseif($op=="eat"){
		output("`7You upend the rest of the red sweets into your mouth, and grin at the flavor.`n`n");
		set_module_pref("peanuts",0);
	}elseif($op=="play"){
		if ($cont==0) {
			set_module_pref("gamereset",1);
			set_module_pref("yourturn",1);
		}
		$gamereset=get_module_pref("gamereset");
		if($gamereset==1 && $peanuts<10){
			$canplay=0;
			set_module_pref("canplay",0);
		}elseif ($gamereset==1 && $peanuts>=10){
			output("`7You take a seat with one of the groups of children, and get ready to play.`n");
			output("Each of you places a peanut into the pot.`n`n");
			$pot=4;
			$peanuts--;
			$gamereset=0;
			$canplay=1;
			set_module_pref("yourturn",0);
			set_module_pref("canplay",1);
			set_module_pref("gamereset",0);
			set_module_pref("peanuts",$peanuts);
			set_module_pref("turn",0);
			set_module_pref("pot",$pot);
			$cont=1;
		}
		$canplay=get_module_pref("canplay");
		$pot = get_module_pref("pot");
		if($cont==1 && $canplay==1){
			$turn=get_module_pref("turn");
			$pot=get_module_pref("pot");
			while($turn<=2 && $cont==1 && $gamereset==0){
				dreidel_playturn();
				$turn++;
				$turn=get_module_pref("turn");
				$peanuts=get_module_pref("peanuts");
				$canplay=get_module_pref("canplay");
				$gamereset=get_module_pref("gamereset");
				$pot=get_module_pref("pot");
			}
			$turn=get_module_pref("turn");
			$pot=get_module_pref("pot");
			if($turn==3 && $cont==1 && $canplay==1){
				// it's your turn
				set_module_pref("yourturn",1);
				dreidel_playturn();
				set_module_pref("yourturn",0);
				set_module_pref("turn",0);
			}
		}
		if($canplay==1) {
			$pot=get_module_pref("pot");
			if ($pot>1) {
				output("`3There are %s peanuts in the pot.`n",$pot);
			}
		}
		$gamereset=get_module_pref("gamereset");
		if ($pot==0 && $gamereset==1){
			output("`3There are no peanuts in the pot, so the game is over.`n");
			set_module_pref("turn",6);
			set_module_pref("gamereset",1);
			set_module_pref("canplay",0);
		}
		if ($canplay==0) {
			set_module_pref("turn",6);
		}
	}
	if($gamereset==0 && $pot!=0 && $peanuts>=1) {
		addnav("Continue","runmodule.php?module=dreidel&op=play&cont=1");
	}elseif($gamereset==0 && $pot!=0) {
		output("`3You don't have any peanuts left, so you'll have to just watch the rest of the game.");
		addnav("Continue","runmodule.php?module=dreidel&op=play&cont=1");
	}
	if($cont==0){
		addnav("Play Dreidel","runmodule.php?module=dreidel&op=play&cont=0");
	}
	if ($pot==0) {
		addnav("How to Play","runmodule.php?module=dreidel&op=learn");
		addnav(array("Buy Peanuts - %s gold",$cost),"runmodule.php?module=dreidel&op=buy");
		addnav("Eat Bag of Peanuts","runmodule.php?module=dreidel&op=eat");
		villagenav();
	}
	$peanuts=get_module_pref("peanuts");
	if ($peanuts<=0) output("`3You don't have any peanuts to play with, at the moment.");
	elseif ($peanuts==1) output("`3You have 1 peanut, which is not enough to play.");
	elseif ($peanuts<10) output("`3You have %s peanuts, which are not enough to play.",$peanuts);
	elseif ($peanuts>=10) output("`3You have %s peanuts.",$peanuts);
	page_footer();
}

?>
