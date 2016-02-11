<?php
// addnews ready
// mail ready
// translator ready

require_once("lib/villagenav.php");
require_once("lib/http.php");

function oldchurch_getmoduleinfo() {
    $info = array(
        "name"=>"The Old Church",
        "version"=>"1.01",
        "author"=>"Sneakabout",
        "category"=>"Village",
        "download"=>"core_module",
        "settings"=>array(
            "Old Church - Settings,title",
			"oldchurchplace"=>"Where does the old church appear,location|".getsetting("villagename", LOCATION_FIELDS),
			"belltoll"=>"Does the bell toll for you?,int|100",
			"ritualenergy"=>"How many people have given energy?,int|0",
        ),
        "prefs"=>array(
            "Old Church - User Preferences,title",
			"bloodgift"=>"Has the user performed a ritual today?,bool|0",
			"donated"=>"Has the user donated gold today?,bool|0",
        )
    );
    return $info;
}

function oldchurch_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
    return true;
}

function oldchurch_uninstall(){
    return true;
}

function oldchurch_dohook($hookname,$args){
    global $session;
    switch($hookname){
   	case "newday":
		set_module_pref("bloodgift",0);
		set_module_pref("donated",0);
		break;
   	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("oldchurchplace")) {
				set_module_setting("oldchurchplace", $args['new']);
			}
		}
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("oldchurchplace")) {
            tlschema($args['schemas']['tavernnav']);
			addnav($args['tavernnav']);
            tlschema();
			addnav("O?The Old Church","runmodule.php?module=oldchurch");
			$belltoll=get_module_setting("belltoll");
			if ($belltoll>=1) {
				output("`nAs if from a long way away you hear a bell toll. A shiver runs down your spine.`n");
				$belltoll--;
				set_module_setting("belltoll",$belltoll);
			}
		}
		break;
	}
    return $args;
}

function oldchurch_run() {
    global $session;
	page_header("Old Church");
	$op = httpget('op');
	$bloodgift=get_module_pref("bloodgift");
	if ($bloodgift==1) {
		output("`7As you approach the old church, you are beset with troubling flashes of what happened last time you were here.");
		output("`n`nYou decide to come back another day.");
		villagenav();
	} elseif ($op=="") {
		output("`7An ancient church stands alone, apart from the other buildings, centuries-old scorchmarks marring the once grand walls and curling round the belltower.");
		output("`n`nA thin path winds its way up the hill between sinister-looking memorials covered in mist.");
		output("While the church has obviously been abandoned for a long time, the windows flicker with light from the inside and shadows can be seen dancing on the tall broken shards of glass which fill the windows.");
		addnav("Enter the Church","runmodule.php?module=oldchurch&op=enter");
		addnav("Return to the Village","village.php");
	} elseif($op=="enter"){
		output("`3You make your way up the hill and edge through the sundered doors, taking in the wrecked interior.");
		output("A flickering torch inhabits an iron holder to one side, above a dusty font.");
		output("Beyond the shattered pews lies an altar which has fresh cloths on it and six candles burning dimly.`n`n");
		output("From out of a small door to one side of the altar walks a curiously hearty figure dressed in grey robes who, ignoring the desolate surroundings, opens his arms wide and bids you welcome to the church, \"`^Greetings, greetings, welcome to this little sanctuary. I am `5Capelthwaite`^, and it is good to see people once again visiting this holy place, feel free to look around. My apologies for the current... disarray... but the world is as the Gods decree it, and we must merely live with such things.`3\"");
		output("He casts an appraising eye over you and smiles even more broadly before continuing,");
		output("\"`^Naturally we are always in need of funds for various charitable projects, and if you wish to donate you will of course gain an appropriate reward.");
		output("For those as hardy an adventurer as you there are obviously other ways to earn a blessing if you are short of funds.`3\"");
		output("He smiles to himself and waits at the altar for your decision.");
		addnav("Climb to the Belfry","runmodule.php?module=oldchurch&op=belfry");
		addnav("Examine the Font","runmodule.php?module=oldchurch&op=font");
		addnav("Donate Gold","runmodule.php?module=oldchurch&op=donate");
		addnav("Ask About \"Other Ways\"","runmodule.php?module=oldchurch&op=ritual");
		addnav("Leave this Place","village.php");
	} elseif ($op=="belfry") {
		output("`7You walk to the back of the church and climb the unsteady steps up to the belfry, where a large rusted bell hangs from rotting timbers.");
		output("Though you'd expect to get a great view from up here, even the nearby buildings seem shrouded in fog.");
		$random=e_rand(1,3);
		if ($random>=2) {
			output("`n`nAs you hurry down from the belfry, you accidentally knock into the bell, which makes an eerily resonant sound as it tolls.");
		}
		output("Spooked slightly, you hurry down the stairs and back into the church.");
		addnav("Examine the Font","runmodule.php?module=oldchurch&op=font");
		addnav("Donate Gold","runmodule.php?module=oldchurch&op=donate");
		addnav("Ask About \"Other Ways\"","runmodule.php?module=oldchurch&op=ritual");
		addnav("Leave this Place","village.php");
	} elseif ($op=="font") {
		output("`\$As you wander over to the alcove by the door and look inside, there seem to be dark stains inside the font, as though it was not usually used for water.");
		output("`n`n`^Slightly troubled by this, you return to the main part of the church.");
		addnav("Climb to the Belfry","runmodule.php?module=oldchurch&op=belfry");
		addnav("Donate Gold","runmodule.php?module=oldchurch&op=donate");
		addnav("Ask About \"Other Ways\"","runmodule.php?module=oldchurch&op=ritual");
		addnav("Leave this Place","village.php");
	} elseif ($op=="donate") {
		output("`5Capelthwaite `^grins as you walk over to the donation plate, waiting to see how much you will put in before making any preparations.");
		$donate = array("donation"=>"Donation,int");
		require_once("lib/showform.php");
        rawoutput("<form action='runmodule.php?module=oldchurch&op=afterdonate' method='POST'>");
		showform($donate,array(),true);
        addnav("","runmodule.php?module=oldchurch&op=afterdonate");
        $give = translate_inline("Give Money");
        rawoutput("<input type='submit' class='button' value='$give'>");
        rawoutput("</form><br>");
		addnav("Change your mind", "runmodule.php?module=oldchurch&op=enter");
		addnav("Leave this Place", "village.php");
	} elseif ($op=="afterdonate") {
		$donation = httppost('donation');
		if ($donation>$session['user']['gold'] || $donation < 0) {
			output("`5Capelthwaite `^looks somewhat grim as you try to persuade him that it is the spirit of giving which counts.`n`n");
			output("You are hastily ejected from the church.");
		} elseif ($donation>=($session['user']['level']*103)) {
			output("`^As you put a hefty amount of gold in the bowl, `5Capelthwaite`^'s grin widens and he beckons you over to the altar for a blessing.");
			output("`n`nAfter muttering something a little too fast for you to catch, he places his hand on your head and blesses you.");
			output("`n`n`@You feel energy flowing through you!");
			$session['user']['gold']-=$donation;
			debuglog("donated $donation gold at the old church");
			$donated = get_module_pref("donated");
			apply_buff('capelthwaite_blessing',
				array("name"=>"`5Capelthwaite's Blessing",
					"rounds"=>15,
					"wearoff"=>"The burst of energy passes.",
					"atkmod"=>($donated?1.05:1.2),
					"defmod"=>($donated?1.01:1.1),
					"roundmsg"=>"Energy flows through you!",
					"schema"=>"module-oldchurch",
					)
				);
			output("Filled with energy you stumble out of the church.");
			set_module_pref("donated", 1);
		} else {
			output("`^As the few coins you decided to spare rattle into the bowl, `5Capelthwaite`^'s grin turns sickening as he beckons you over to the altar.`n`n");
			output("After muttering something too low for you to catch, he places his hand on your head and places the enchantment on you.`n`n");
			output("`4Dark energy flows through you!");
			apply_buff('capelthwaite_curse',
				array("name"=>"`5Capelthwaite's \"Blessing\"",
					"rounds"=>10,
					"wearoff"=>"The burst of energy passes.",
					"atkmod"=>0.8,
					"defmod"=>0.9,
					"roundmsg"=>"Dark Energy flows through you!",
					"schema"=>"module-oldchurch",
					)
				);
			output("Filled with energy you stumble out of the church.");
			$session['user']['gold']-=$donation;
			debuglog("donated $donation gold at the old church");
			set_module_pref("donated", 1);
		}
		villagenav();
	} elseif ($op=="ritual") {
		output("`3You nervously inquire about another way that you could earn a blessing as `5Capelthwaite`3 looks on you magnanimously.`n`n");
		output("\"`^No need to be so nervous my friend, simply a short rite to honour the master, you'll feel fine the next day. Just take this potion and I'll take care of everything.`3\".`n`n");
		output("He holds out a small black potion he produced from somewhere for you to drink and smiles encouragingly.");
		addnav("Take the Potion","runmodule.php?module=oldchurch&op=darkritual");
		addnav("Leave this Place","village.php");
	} elseif ($op=="darkritual") {
		output("`^Trusting in `5Capelthwaite`^'s friendly smile, you drink down the potion. You feel funny for a moment, then the world begins to swim before your eyes, and you black out.`n`n");
		$ritual=e_rand(1,100);
		if ($ritual>=100) {
			output("`7Though the potion sapped all will from your body, you remain conscious as you are dragged somewhere, hooded, by several people.");
			output("When the hood is removed you are in a cavern deep underground, trapped in what looks like some artist's rendition of the underworld.");
			output("You are dressed in a grey robe and placed on the edge of a smoke-filled circle with many others similarly outfitted.");
			output("For what seems like hours you are surrounded by chanting, smoke and the sensation of life being drained from your very soul.");
			output("Eventually you are dragged back up through some tunnels like a sack of grain before being dumped in front of the altar, where `5Capelthwaite`7 feeds you another potion.`n`n");
		} else {
			output("You dream of smoke and a glowing light.`n`n");
		}
		output("`^You regain your faculties with `5Capelthwaite`^ leaning over you, looking red-faced.");
		output("He quickly blesses you before helping you out of the church.`n`n");
		output("`@You feel energy flowing through you!`n`n");
		apply_buff('capelthwaite_blessing',
			array("name"=>"`5Capelthwaite's Blessing",
				"rounds"=>15,
				"wearoff"=>"The burst of energy passes.",
				"atkmod"=>1.2,
				"defmod"=>1.1,
				"roundmsg"=>"Energy flows through you!",
				"schema"=>"module-oldchurch",
				)
			);
		output("You hurry away from this place as fast as your unsteady legs can take you.");
		$ritualenergy=get_module_setting("ritualenergy");
		$ritualenergy++;
		set_module_setting("ritualenergy",$ritualenergy);
		set_module_pref("bloodgift",1);
		if ($session['user']['turns']>=5)
			$session['user']['turns']--;
		$session['user']['hitpoints']*=0.5;
		if ($session['user']['hitpoints'] < 1)
			$sesson['user']['hitpoints'] = 1;
		villagenav();
    }
	page_footer();
}
?>
