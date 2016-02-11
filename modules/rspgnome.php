<?php
// translator ready
// addnews ready
// mail ready

function rspgnome_getmoduleinfo(){
	$info = array(
		"name"=>"The RSP gnome",
		"version"=>"0.9",
		"author"=>"Markus Wienhoefer",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"prefs"=>array(
			"playerpoints"=>"Player points at start of game,int|0",
			"gnomepoints"=>"Gnome points at start of game,int|0",
		)
	);
	return $info;
}

function rspgnome_install(){
	module_addeventhook("forest", "return 100;");
	return true;
}

function rspgnome_uninstall(){
	return true;
}

function rspgnome_dohook($hookname,$args){
	return $args;
}

function rspgnome_nav($from)
{
	addnav("Choose your weapon");
	addnav("Rock",$from."op=rock");
	addnav("Paper",$from."op=paper");
	addnav("Scissors",$from."op=scissors");
}

function rspgnome_round($from, $pchoice)
{
	global $session;
	$items = array(1=>"rock", 2=>"paper", 3=>"scissors");
	$items = translate_inline($items);

	$playerpoints=get_module_pref("playerpoints");
	$gnomepoints=get_module_pref("gnomepoints");

	output("The gnome hides all his arms behind his back and starts to count, emphasizing each number by jumping from one leg to  the other.`n`n");
	output("`&Three...`^Two...`4One...`n");
	$choice = e_rand(1,3);
	output("`@Reaching one, he pulls the arm holding the `&%s`@ from behind his back.", $items[$choice]);

	if ($choice == 1 && $pchoice == 1) { // rock ties rock
		output("`@Banging your rocks together, you soon realize that it is of no use.`n");
		output("`#\"Hmm... ok... let's try that again, shall we?\"`@`n`n");
	} elseif ($choice == 2 && $pchoice == 1) { // paper beats rock
		output("`@Faster than your eyes can follow, the gnome slaps the stone out of your hand.`n`n");
		output("`#\"I WIN!\" `@the gnome shouts.");
		output("You rub your hand, which is still red from the slapping...`n`n");
		$gnomepoints=$gnomepoints+1;
	} elseif ($choice == 3 && $pchoice == 1) { // scissors beaten by rock
		output("`@With all your strength, you bang your rock against his hand and manage to make small sparks, that nearly enflame the hide he is wearing.`n`n");
		output("`#\"Well, I guess that's your point then...\" `@the gnome admits.`n`n");
		$playerpoints=$playerpoints+1;
	} elseif ($choice == 1 && $pchoice == 2) { // rock beaten by paper
		output("`@You quickly block the incoming blow with the paper, which is much stronger than you expected.");
		output("`#\"It's magic paper! ...if you wondered,\" `@the gnome explains.`n`n");
		output("`#\"Well, I guess that's your point then...\" `@the gnome admits.`n`n");
		$playerpoints=$playerpoints+1;
	} elseif ($choice == 2 && $pchoice == 2) { // paper ties paper
		output("`@Slapping each other with the paper, you soon realize that it is of no use. `n");
		output("`#\"Hmm... ok... let's try that again, shall we?`@`n`n");
	} elseif ($choice == 3 && $pchoice == 2) { // scissors beats paper
		output("`@The gnome can move his scissors incredibly fast, and before you know it, your sheet of paper is cut to little pieces.`n`n");
		output("`#\"I WIN!\" `@the gnome shouts.");
		output("Seeing that your sheet is completely ruined, he gives you another one.`n`n");
		$gnomepoints=$gnomepoints+1;
	} elseif ($choice == 1 && $pchoice == 3) { // rock beats scissors
		output("`@With one sure stroke he slams the scissors out of your hand.`n`n");
		output("`#\"I WIN!\" `@the gnome shouts.");
		output("Sad from the lost point, you go to retrieve your \"weapon\".`n`n");
		$gnomepoints=$gnomepoints+1;
	} elseif ($choice == 2 && $pchoice == 3) { // paper beaten by scissors
		output("`@You are happy to have made the right choice and start cutting his hand.");
		output("`#\"Stop it, it's a game, remember... heroes...!`@`n`n");
		output("`#\"Well, I guess that's your point then...\" `@the gnome admits.`n`n");
		$playerpoints=$playerpoints+1;
	} elseif ($choice == 3 && $pchoice == 3) { // scissors ties scissors
		output("`@Striking at each other with the scissors, you soon realize that it is of no use.`n");
		output("`#\"Hmm... ok... let's try that again, shall we?`@`n`n");
	}

	output("You now have %s %s while the gnome has %s %s.",
			$playerpoints, translate_inline($playerpoints==1?"point":"points"),
			$gnomepoints, translate_inline($gnomepoints==1?"point":"points"));
	set_module_pref("playerpoints",$playerpoints);
	set_module_pref("gnomepoints",$gnomepoints);

	if ($playerpoints==2||$gnomepoints==2){
		if ($playerpoints==2) {
			rspgnome_wingame();
		}else{
			rspgnome_loosegame();
		}
	} else{
		output("`n`n`@The gnome returns his hands behind his back. `#\"Well, come on! Next round!\"");
		rspgnome_nav($from);
	}
}

function rspgnome_runevent($type)
{
	global $session;
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:rspgnome";
	$op = httpget('op');
	if ($op=="" || $op=="search"){
		output("`@As you head into the forest, searching for prey, you suddenly see a strange sight in a clearing not far from where you stand.`n`n");
		output("The creature looks like a little gnome, but instead of two it has three arms, which end in what seem to be a little stone, a pair of scissors and a sheet of paper.`n`n");
		output("Very puzzled, you remember a child's story your grandma used to tell you, about the infamous RSP gnome.");
		output("Noticing the big leather bag strung to his belt, you think about talking to him...`n`n");
		addnav("RSP gnome");
		addnav("Talk to him",$from."op=talk");
		addnav("Better not",$from."op=back");
	} elseif ($op == "back") {
		$session['user']['specialinc'] = "";
		output("`@You are sure that it's always best to listen to what granny says, and you head back to deeper parts of the forest...`n`n");
	} elseif ($op == "talk") {
		output("`@You decide to enter the clearing.`n`n");
		output("Seeing you, the gnome instantly hobbles in your direction. `#\"YES a new contender, nice, nice, nice, you surely want to play with me, right?");
		output("Or else, you wouldn't have come here!");
		output("You should know that I'm the master of my game. Let's play!\"`n`n");
		output("`@Waiting in anticipation, the RSP gnome jumps up and down.");
		output("Not knowing what this strange creature wants, you think about giving in...`n`n");
		addnav("Playing with the gnome");
		addnav("Play his game",$from."op=rspgame");
		addnav("Flee",$from."op=dontplay");
	} elseif ($op == "rspgame") {
		output("`#\"OK, let's play your game then,\" `@you hear yourself say.");
		output("The gnome gets even more excited, and hobbles to a little tree stump nearby that you didn't even notice before.");
		output("When he comes back, he has a little sack strung over his \"stone hand\", from which he hands you a little stone, a pair of scissors and a sheet of paper.`n`n");
		output("`#\"It's very easy,\" `@ he continues.");
		output("`#\"Just choose one of the weapons I have given you, and on the count of three we begin to fight.");
		output(" Time to choose!\"");
		rspgnome_nav($from);
		//paranoia reset of points if coming from timeout
		set_module_pref("playerpoints",0);
		set_module_pref("gnomepoints",0);
	} elseif ($op == "rock") {
		output("`@Planning on just knocking this strange guy out in one sure stroke, you choose the rock.`n`n");
		rspgnome_round($from, 1);
	} elseif ($op == "paper") {
		output("`@The thought comes to your mind that it might be a good idea to wrap that little creature in the sheet of paper.`n`n");
		rspgnome_round($from, 2);
	} elseif ($op == "scissors") {
		output("`@Cutting is never a bad idea, you think, and prepare to show the pair of scissors.`n`n");
		rspgnome_round($from, 3);
	} elseif ($op == "dontplay") {
		$session['user']['specialinc'] = "";
		output("`@You are sure that it's always best to listen to what granny says, and you head back to deeper parts of the forest...`n`n");
	}
	output_notl("`0");
}

function rspgnome_wingame(){
	global $session;
	output("`n`n\"Seems like we have a winner!\" the gnome says, not quite as happy as before.");
	output("`#\"Now you want a prize, right... oh well...\"`@`n`n");
	$goldwon=e_rand(1,3)*$session['user']['level']*10;
	output("He reaches into the bag on his belt and hands you %s gold.`n`n", $goldwon);
	debuglog("won $goldwon from the RSP gnome");
	output("`#\"Next time we meet, I won't be this easy to beat.\"`@");
	output("With these words he heads into the forest, depression written all over his face.");
	$session['user']['gold']+=$goldwon;
	set_module_pref("playerpoints",0);
	set_module_pref("gnomepoints",0);
}

function rspgnome_loosegame(){
	global $session;
	output("`n`n`#\"See, I told you I'm the best.\"`@`n`n");
	output("He grabs a little stick from his belt which you didn't even notice before, and with it, touches your forehead.");
	output("A second later you are unable to move.`n`n");
	$goldlost=e_rand(1,3)*$session['user']['level']*10;
	if ($session['user']['gold']==0) {
		$goldlost = 0;
		output("The gnome looks into your bags, seemingly searching for something.");
		output("`#\"You should have told me you have nothing to give...\"`@ he adds after a while.`n`n");
	}elseif ($goldlost>$session['user']['gold']){
		$goldlost = $session['user']['gold'];
	}
	if ($goldlost) {
		output("Seeing that his magic worked he reaches into your purse and takes out `&%s `@gold.",$goldlost);
		output("`#\"That's compensation for wasting my time!\"`@`n`n");
		$session['user']['gold'] -= $goldlost;
		debuglog("lost $goldlost gold to the RSP gnome");
	}
	output("Hopping frantically, he does a little victory dance and leaves you staring blankly, as you realize you just lost to the infamous RSP gnome.");
	set_module_pref("playerpoints",0);
	set_module_pref("gnomepoints",0);
}

function rspgnome_run(){
}

?>
