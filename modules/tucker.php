<?php
// addnews ready
// mail ready
// translator ready

/* Tucker Prince */
// ver 0.9 - RSP Gnome - Markus Wienhoefer
// ver 1.0 JT Traub
// ver 1.1 Tucker Prince text by Shannon Brown => SaucyWench -at- gmail -dot- com
// 9th Dec 2004


require_once("lib/villagenav.php");
require_once("lib/http.php");

function tucker_getmoduleinfo(){
    $info = array(
		"name"=>"The Tucker Prince",
		"version"=>"1.1",
		"author"=>"Shannon Brown<br>based on code by Markus Wienhoefer",
		"category"=>"Village",
		"download"=>"core_module",
        "settings"=>array(
            "Tucker Prince - Settings,title",
			"tuckerloc"=>"Where does the stand appear,location|".getsetting("villagename", LOCATION_FIELDS)
        ),
		"prefs"=>array(
            "Tucker Prince User Prefs,title",
			"playedtoday"=>"Has the player won today?,bool|0",
			"playerpoints"=>"Player points at start of game,int|0",
			"princepoints"=>"Prince points at start of game,int|0",
        )
    );
    return $info;
}

function tucker_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
    return true;
}

function tucker_uninstall(){
    return true;
}

function tucker_dohook($hookname,$args){
    global $session;
    switch($hookname){
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("tuckerloc")) {
				set_module_setting("tuckerloc", $args['new']);
			}
		}
		break;
   	case "newday":
		set_module_pref("playedtoday",0);
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("tuckerloc")) {
            tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
            tlschema();
			addnav("Tucker Prince","runmodule.php?module=tucker");
		}
		break;
	}
    return $args;
}


function tucker_nav($from){
	addnav("Choose your weapon");
	addnav("Candy Cane",$from."op=candy");
	addnav("Latke",$from."op=latke");
	addnav("Rasgulla",$from."op=rasgulla");
}


function tucker_wingame(){
	global $session;
	output("`n`n`#\"Seems like we have a winner!\" `@the prince says, not quite as happy as before.");
	output("`#\"Now you want a prize, right... oh well...\"`@`n`n");
	output("He reaches into the bag on his belt and hands you a `5gem!`n`n");
	output("`#\"Next time we meet, I won't be this easy to beat.\"");
	output("`@With these words he heads off, depression written all over his face.");
	$session['user']['gems']++;
	debuglog("gained a gem by playing the tucker prince's game");
	$playerpoints=0;
	$princepoints=0;
	set_module_pref("playedtoday",1);
	set_module_pref("playerpoints",$playerpoints);
	set_module_pref("princepoints",$princepoints);
	if ($session['user']['dragonkills']>10) apply_buff('tucker',array("name"=>"`QTucker Triumph!","rounds"=>20,"atkmod"=>1.05, "schema"=>"module-tucker"));
	if ($session['user']['dragonkills']<=10) apply_buff('tucker',array("name"=>"`QTucker Triumph!","rounds"=>30,"atkmod"=>1.05, "schema"=>"module-tucker"));
	villagenav();
}



function tucker_losegame(){
	global $session;
	output("`n`n`#\"See, I told you I'm the best.\"`@`n`n");
	output("He grabs a little stick from his belt which you hadn't noticed, and with it, touches your forehead.");
	output("A second later you are unable to move.`n`n");
	output("The prince looks at you and cackles maniacally.");
	output("`#\"That's compensation for wasting my time!\"`@`n`n");
	$session['user']['hitpoints']=round($session['user']['hitpoints']*0.8);
	output("Hopping frantically, he does a little victory dance and leaves you staring blankly, as you realize you just lost to the infamous Tucker Prince.");
	output("At least you still have the food, which you eat greedily once you regain the feeling in your limbs.");
	if ($session['user']['dragonkills']>10)
		apply_buff('tucker',
				array("name"=>"`qTucker Feast","rounds"=>10,"atkmod"=>1.02, "schema"=>"module-tucker"));
	elseif ($session['user']['dragonkills']<=10)
		apply_buff('tucker',
				array("name"=>"`qTucker Feast","rounds"=>15,"atkmod"=>1.02, "schema"=>"module-tucker"));
	set_module_pref("playerpoints",0);
	set_module_pref("princepoints",0);
	villagenav();
}


function tucker_round($from, $pchoice){
	global $session;
	$items = array(1=>"candy cane", 2=>"latke", 3=>"rasgulla ball");
	$items = translate_inline($items);

	$playerpoints=get_module_pref("playerpoints");
	$princepoints=get_module_pref("princepoints");

	output("The prince hides his arms behind his back and starts to count, emphasizing each number by jumping from one leg to  the other.`n`n");
	output("`&Three...`^Two...`4One...`n");
	$choice = e_rand(1,3);
	output("`@Reaching one, he pulls out one hand, and you see that he is holding the `&%s`@.", $items[$choice]);

	if ($choice == 1 && $pchoice == 1) { // candy ties candy
		output("`@Hooking your candy canes at each other, you both soon realize that it is of no use.`n");
		output("`#\"Hmm... ok... let's try that again, shall we?`@`n`n");
	} elseif ($choice == 2 && $pchoice == 1) { // latke beats candy
		output("`@Faster than your eyes can follow, the prince slaps the candy cane from your hand.`n`n");
		output("`#\"I WIN!\" `@he shouts.");
		output("You rub your hand, which is still red from the slapping...`n`n");
		$princepoints=$princepoints+1;
	} elseif ($choice == 3 && $pchoice == 1) { // candy beats rasgulla ball
		output("`@With all your strength, you bang your candy cane against his hand.");
		output("He barely manages to avoid your getting sticky candy flakes on his coat.`n`n");;
		output("`#\"Well, I guess that's your point then...\" `@the prince admits.`n`n");
		$playerpoints=$playerpoints+1;
	} elseif ($choice == 1 && $pchoice == 2) { // candy beaten by latke
		output("`@You quickly block his incoming blow with your latke, which is much stronger than you expected.");
		output("`#\"It's magic latke! ...if you wondered,\" `@the prince explains.`n`n");
		output("`#\"Well, I guess that's your point then...\" `@the prince admits.`n`n");
		$playerpoints=$playerpoints+1;
	} elseif ($choice == 2 && $pchoice == 2) { // latke ties latke
		output("`@Slapping each other with the latkes, you both soon realize that it is of no use. `n");
		output("`#\"Hmm... ok... let's try that again, shall we?`@`n`n");
	} elseif ($choice == 3 && $pchoice == 2) { // rasgulla beats latke
		output("`@The prince can roll his rasgulla incredibly fast, and before you know it, your latke has crumbled into little pieces.`n`n");
		output("`#\"I WIN!\" `@he shouts.");
		output("Seeing that your latke is completely ruined, he gives you another one.`n`n");
		$princepoints=$princepoints+1;
	} elseif ($choice == 1 && $pchoice == 3) { // candy beats rasgulla
		output("`@With one sure stroke he slams the rasgulla out of your hand.`n`n");
		output("`#\"I WIN!\" `@he shouts.");
		output("Sad from the lost point, you go to retrieve your \"weapon\".`n`n");
		$princepoints=$princepoints+1;
	} elseif ($choice == 2 && $pchoice == 3) { // latke beaten by rasgulla
		output("`@You are happy to have made the right choice, and you begin rolling the rasgulla at him.");
		output("`#\"Stop it, it's a game, remember... heroes...!`@`n`n");
		output("`#\"Well, I guess that's your point then...\" `@the prince admits.`n`n");
		$playerpoints=$playerpoints+1;
	} elseif ($choice == 3 && $pchoice == 3) { // rasgulla ties rasgulla
		output("`@Rolling the rasgullas at each other, you both soon realize that it is of no use.`n");
		output("`#\"Hmm... ok... let's try that again, shall we?`@`n`n");
	}

	output("You now have %s %s while the prince has %s %s.",
			$playerpoints, translate_inline($playerpoints==1?"point":"points"),
			$princepoints, translate_inline($princepoints==1?"point":"points"));
	set_module_pref("playerpoints",$playerpoints);
	set_module_pref("princepoints",$princepoints);

	if ($playerpoints==2 || $princepoints==2){
		if ($playerpoints==2) {
			tucker_wingame();
		}else{
			tucker_losegame();
		}
	} else{
		output("`n`n`@The prince returns his hands behind his back. `#\"Well, come on! Next round!\"");
		tucker_nav($from);
	}
}


function tucker_run() {
    global $session;
	page_header("The Tucker Prince");
	$op = httpget('op');
	$playedtoday=get_module_pref("playedtoday");
	output("`&`c`bThe Tucker Prince`7`b`c`n");

  	global $session;
	$from = "runmodule.php?module=tucker&";
	$op = httpget('op');
	if ($op=="" || $op=="search"){
		output("`@As you head into the village, searching for adventure, you suddenly see a strange little man on a pedestal not far from where you stand.`n`n");
		output("The man is dressed like a regal prince, but his attire is fit for a human, and he is a dwarf..`n`n");
		output("Very puzzled, you remember a child's story your grandma used to tell you, about the infamous Tucker Prince.");
		output("Noticing the big leather bag strung to his belt, you think about talking to him...`n`n");
		addnav("Tucker prince");
		addnav("Talk to him",$from."op=talk");
		addnav("Better not",$from."op=back");
	} elseif ($op == "back") {
		output("`@You are sure that it's always best to listen to what grandma says, and you head back to the other villagers...`n`n");
        villagenav();
	} elseif ($op == "talk") {
		output("`@You decide to talk to the dwarf.`n`n");
		output("Seeing you, the prince instantly hobbles in your direction. `#\"YES! A new contender, nice, nice, nice, you surely want to play the game with me, right?");
		output("Or else, you wouldn't have come here!");
		output("You should know that I'm the master of my game. Let's play!\"`n`n");
		output("`@Waiting in anticipation, the Tucker Prince bobs up and down.");
		output("Not knowing what this strange little man wants, you think about giving in...`n`n");
		addnav("Playing the game");
		addnav("Play his game",$from."op=rspgame");
		addnav("Flee",$from."op=dontplay");
	} elseif ($op == "rspgame" && $playedtoday==0) {
		output("`#\"OK, let's play your game then,\" `@you hear yourself say.");
		output("The prince gets even more excited, and hastens back to his nearby pedestal.");
		output("When he comes back, he has a little sack strung over one hand, from which he produces a candy cane, a rasgulla and a latke.`n`n");
		output("`#\"It's very easy,\" `@ he continues.");
		output("`#\"Just choose one of the party foods I have given you, and on the count of three we begin to fight.\"`n`n");
		output("`@You're a bit confused as to how sweets can fight, exactly, but it's the holiday season after all, so you suppose you'll play.`n`n");
		output("`#\"Time to choose!\"");
		tucker_nav($from);
		//paranoia reset of points if coming from timeout
		set_module_pref("playerpoints",0);
		set_module_pref("princepoints",0);
	} elseif ($op == "candy") {
		output("`@Planning on hooking this strange guy by the neck, you choose the candy cane.`n`n");
		tucker_round($from, 1);
	} elseif ($op == "latke") {
		output("`@You decide that it might be a good idea to slap that little man away with some delicious latke power.`n`n");
		tucker_round($from, 2);
	} elseif ($op == "rasgulla") {
		output("`@Steamrolling is never a bad idea, you think, and prepare to roll the rasgulla at him.`n`n");
		tucker_round($from, 3);
	} elseif ($op == "dontplay" || $op == "endgame") {
		if ($op == "dontplay") output("`@You are sure that it's always best to listen to what granny says, and you head back from whence you came...`n`n");
		if ($op == "endgame") output("`@You head back towards the village, searching for new adventures...");
		villagenav();
	}else  {
		output("`@He eyes you for a minute, before reminding you that you've already wasted his time today.`n`n");
	villagenav();
	}
	output_notl("`0");
	page_footer();
}

?>
