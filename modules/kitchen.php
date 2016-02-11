 <?php
// addnews ready
// mail ready
// translator ready

/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 13 Aug 2004 */
/* ver 1.1 mop and bucket by JT Traub */
/* ver 1.2 cleanup performed by Randy Yates aka Deimos the Haberdasher */
/* ver 1.3 Saucy cuts and polishes */
/* ver 1.4 addition of kitchen locator options PRE RELEASE 4 UP ONLY! */

// 25th August ver 1.41 - small mod to set up interaction with the tattooist.
// Lauri's feather is now picked up by the player when they buy the Crow Cake.
// This ties in with the Petra the Tattoo Artist Module, however you do not
// need Petra installed for the Kitchen to function.

// 3rd Sept ver 1.42 - now interfaces with Matthias the Astute

require_once("lib/http.php");
require_once("lib/villagenav.php");

function kitchen_getmoduleinfo(){
	$info = array(
		"name"=>"Kitchen of DOOM",
		"version"=>"1.42",
		"author"=>"Shannon Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"eatsallowed"=>"Times players are allowed to eat per day,int|1",
			"tippercent"=>"Percent to tip Saucy,range,1,25,1|20",
			"kitchenloc"=>"Where does the kitchen appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
		"prefs"=>array(
			"eatentoday"=>"Has player eaten today,int|0",
			"feather"=>"Does the player have a feather,bool|0",
		)
	);
	return $info;
}

function kitchen_install(){
	module_addhook("changesetting");
	module_addhook("village");
	module_addhook("newday");
	return true;
}

function kitchen_uninstall(){
	return true;
}

function kitchen_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("kitchenloc")) {
				set_module_setting("kitchenloc", $args['new']);
			}
		}
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("kitchenloc")) {
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("K?Saucy's Kitchen","runmodule.php?module=kitchen&op=food");
		}
		break;
	case "newday":
		set_module_pref("eatentoday",0);
		break;
	}
	return $args;
}

function kitchen_run(){
	global $session;

	$eatentoday = get_module_pref("eatentoday");
	$eatsallowed = get_module_setting("eatsallowed");
	$tippercent = get_module_setting("tippercent");
	$tipfactor = $tippercent/100+1;
	$mealcost = 20*$session['user']['level'];
	$mealtip = round($mealcost*$tipfactor,0);
	$snackcost = 10*$session['user']['level'];
	$snacktip = round($snackcost*$tipfactor,0);
	$foodcost = 0;
	$op = httpget("op");
	if ($mealcost==$mealtip) $mealtip++;
	if ($snackcost==$snacktip) $snacktip++;

	page_header("Saucy's Kitchen");

	villagenav();
	output("`&`c`bSaucy's Kitchen`b`c");

	if ($eatentoday>=$eatsallowed) {
		// you're not eating more than once today.
		output("`QSaucy`\$Wench `7stares at you.`n`n");
		output("\"`&Didn't I see you here already? I seen what you did to the place last time!`7\"`n`n");
		output("You aren't sure what she thinks you did to the place last time, but you really aren't hungry anyway.");
	} elseif ($op=="" || $op=="food"){

		addnav(array("C?Chef's Special (`^%s gold`0)",$mealcost), "runmodule.php?module=kitchen&op=meal");
		addnav(array("S?Chef's Special + Tip (`^%s gold`0)",$mealtip),"runmodule.php?module=kitchen&op=mealtip");
		addnav(array("L?Light Snack (`^%s gold`0)",$snackcost),"runmodule.php?module=kitchen&op=snack");
		addnav(array("T?Light Snack + Tip (`^%s gold`0)",$snacktip),"runmodule.php?module=kitchen&op=snacktip");

		output("`7You step into Saucy's Kitchen and are greeted by the pungent aroma of unidentifiable hot food.");
		output("`QSaucy`\$Wench `7folds her arms and eyes you with impatience.`n`n");
		output("\"`&Well, I don't got all day, ya know. Are ye havin' the special, or ain't ye?`7\"`n`n");
		output("`7You glance at the meals on the other tables, but you're unable to work out what the other guests are eating.");
		output("`QSaucy`\$Wench `7taps her foot and glares at you.");
		output("Judging by the look on her face, you'd best give her an answer, and quickly.`n`n");
	} else {
		// this group of four will check to see which choice they made.
		if ($op=="meal") {
			$foodcost=$mealcost;
		}
		elseif ($op=="mealtip") {
			$foodcost=$mealtip;
		}
		elseif ($op=="snack") {
			$foodcost=$snackcost;
		}
		else {
			$foodcost=$snacktip;
		}
		// now we have the choice and price established.
		// we can go on with the ordinary checks
		if ($session['user']['gold']<$foodcost){
			// you don't have enough money.
			output("`QSaucy`\$Wench `7eyes you angrily.`n`n");
			output("\"`&I don't see enough gold in them there hands o' yours!`7\"`n`n");
			output("You think you'd better get out of there.");
		} else {
			$eatentimes=get_module_pref("eatentoday")+1;
			set_module_pref("eatentoday",$eatentimes);
			$session['user']['gold']-=$foodcost;
			debuglog("spent $foodcost gold on a meal");

			$sarcasm = translate_inline("`7You can't help but note the sarcasm in her voice.`n`n");
			// check which meal again to decide risk
			if ($op=="meal" || $op=="mealtip") {
				if ($op=="meal") {
					$foodchoice=(e_rand(1,15));
				} else {
					$foodchoice=(e_rand(1,20));
				}
				output("`7You order the Chef's Special of the day, and `QSaucy`\$Wench `7returns a few minutes later with a plate, which she dumps in front of you unceremoniously.`n`n");

				if ($foodchoice==1 || $foodchoice==2){
					output("\"`&Brain Stew. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7She walks away, and you sniff the dish with disgust. You quietly tip it onto the floor at your feet.`n`n");
					output("As you lean over, you spot a `5gem `7under the table.");
					$session['user']['gems']++;
					debuglog("found a gem in Saucy's Kitchen");
				} elseif ($foodchoice==3 || $foodchoice==4){
					output("\"`&MightyEscargot. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You sample the snails.`n`n");
					output("They slow you down, and you may `\$not`7 have time for as many forest fights today.");
					if ($session['user']['turns'] > 0) {
						$session['user']['turns']--;
					}
				} elseif ($foodchoice==5 || $foodchoice==6){
					output("\"`&Spaghetti Bolognaise. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You dig in with delight, spilling garlic sauce all down the front of your %s.`n`n", $session['user']['armor']);
					output("You `\$lose`7 charm, but you feel `@healthy`7! ");
					$session['user']['hitpoints']=($session['user']['hitpoints']*1.15);
					if ($session['user']['charm'] > 0){
						$session['user']['charm']--;
					}
				} elseif ($foodchoice==7 || $foodchoice==8 ){
					output("\"`&Kendaerian Oysters. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You sample them with gusto.`n`n");
					output("You feel `%charming`7!");
					$session['user']['charm']++;
				} elseif ($foodchoice==9){
					output("\"`&Salmonella Surprise. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You sample the dish with gusto, but soon begin feeling violently ill.`n`n");
					output("`2You have food poisoning!");
					output("`7 You `\$lose`7 some of your hitpoints.");
					$session['user']['hitpoints'] =
						$session['user']['hitpoints']*0.5;
					if ($session['user']['hitpoints'] < 1)
						$session['user']['hitpoints'] = 1;
				} elseif ($foodchoice==10 || $foodchoice==17){
					output("\"`&Kangaroo Casserole. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7The meal, however,  is delicious.`n`n");
					$session['user']['turns']+=5;
					output("`7You feel BOUNCY!`n");
					output("`7You feel like you could face a few `@extra`7 monsters in the forest!`n`n");
					output("`7You `@gain`7 FIVE turns!");

					$args = array(
						'soberval'=>0.5,
						'sobermsg'=>"`7Your hyper state burns off some of your alcoholic stupor!",
						'schema'=>"module-kitchen",
					);
					modulehook("soberup", $args);
				} else {
					output("\"`&A meat pie an' sauce. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You sample the dish with gusto.`n`n");
					output("You feel satisfied!");
					output("You feel you could face another `@monster`7 in the forest.`n`n");
					output("You `@gain`7 a turn!");
					$session['user']['turns']++;
				}
			} else {
				// the option with a tip has more chance of positive outcomes.
				if ($op=="snack") {
					$foodchoice=(e_rand(1,12));
				} else {
					// op is snack tip
					$foodchoice=(e_rand(1,18));
				}
				output("`7You order the Dessert of the Day, and `QSaucy`\$Wench `7returns a few minutes later with your snack, which she dumps in front of you unceremoniously.`n`n");

				if ($foodchoice==1 || $foodchoice==2){
					output("\"`&Pavlova. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You stuff yourself with meringue, whipped cream and fruit.`n`n");
					output("You spend so long enjoying your dessert that you may `\$not`7 have time for as many forest fights today.");
					if ($session['user']['turns'] > 0) {
						$session['user']['turns']--;
					}
				} elseif ($foodchoice==3 || $foodchoice==4 || $foodchoice==5){
					output("\"`&Lamingtons. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You bite into the cakes with delight, spilling chocolate and coconut all down the front of your %s.`n`n",$session['user']['armor']);
					output("You `\$lose`7 charm, but you feel `@healthy`7!");
					$session['user']['hitpoints']=($session['user']['hitpoints']*1.15);
					if ($session['user']['charm'] > 0){
						$session['user']['charm']--;
					}
				} elseif ($foodchoice==6 || $foodchoice==7 || $foodchoice==8){
					output("\"`&Fresh Picked Booger Berries. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You sample them with gusto.`n`n");
					output("You feel `%charming`7!");
					$session['user']['charm']++;
				} elseif ($foodchoice==9){
					output("\"`&Prune Pudding. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You sample the dish with gusto, but soon begin feeling violently ill.`n`n");
					output("`2You have food poisoning!");
					output("`7 You `\$lose`7 some of your hitpoints.");
					$session['user']['hitpoints'] =
						$session['user']['hitpoints']*0.5;
					if ($session['user']['hitpoints'] < 1)
						$session['user']['hitpoints'] = 1;
				} elseif ($foodchoice==10 || $foodchoice==17){
					output("\"`&He's callin it Crow Cake. Blow me down if I knows what that is. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You briefly wonder who \"he\" might be, but the cake is dark brown and is garnished with a single black feather.");
					if (get_module_pref("feather")==0) {
						set_module_pref("feather",1);
						// need to check if module exists on this server
						if (is_module_active("matthias")){
							$astute=get_module_pref("astuteness","matthias");
							$astute++;
							set_module_pref("astuteness",$astute,"matthias");
						}
						output("You take the pretty feather and place it in your hair.");
					}
					output("`n`nYou wince at the strong coffee flavor, but manage to finish it all.");
					$session['user']['turns']+=5;
					output("`7You feel BOUNCY!`n");
					output("`7You feel like you could face a few `@extra`7 monsters in the forest!`n`n");
					output("`7You `@gain`7 FIVE turns!");

					$args = array(
						'soberval'=>0.5,
						'sobermsg'=>"`7Your hyper state burns off some of your alcoholic stupor!",
						'schema'=>"module-kitchen",
					);
					modulehook("soberup", $args);

				} else {
					output("\"`&Vegemite Toast. Enjoy.`7\" `n`n");
					output_notl("%s", $sarcasm);
					output("`7You sample the dish with gusto.`n`n");
					output("You feel satisfied!");
					output("You feel you could face `@another`7 monster in the forest.`n`n");
					output("You `@gain`7 a turn!");
					$session['user']['turns']++;
				}
			}
		}
	}
	page_footer();
}

?>
