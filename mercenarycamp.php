<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

tlschema("mercenarycamp");

checkday();
$name = stripslashes(rawurldecode(httpget('name')));
if (isset($companions[$name])) {
	$displayname = $companions[$name]['name'];
} else {
	$displayname = translate_inline("your companion");
}

$basetext=array(
	"title"			=>	"A Mercenary Camp",
	"desc"			=>	array(
		"`n`QYou step out of the gates of the village and stand for a moment to take a look around.",
		"A slight breeze in the air stirs the pennants mounted above your head before it touches your skin.",
		"Sounds of dogs barking draws your attention to the makeshift camp which is set slightly apart from the village.",
		"You walk towards the encampment trying to avoid muddy puddles left from the rainfall the prior night.",
		"The odor of cooking fires permeates the air.`n`n",

		"As you approach you notice two men seated on rough hewn logs in front of a tent.",
		"Propped against one of the logs are a pair of long handled battle axes and a bastard sword.",
		"One of the men turns his weatherbeaten face towards you.",
		"You try to suppress a shudder as you recoil from the sight of his face.",
		"A ragged scar marks his face from forehead to jaw, crossing an empty hole where his eye should be.",
		"He spits into the campfire before him.`n`n",

		"\"`4Are you looking for someone?`Q\", he asks in a gravelly voice that comes from deep within.`n`n",

		"At that moment a slender elfin woman with her golden hair pulled back in a warrior's braid brushes past you.",
		"Strapped across her back is a long bow and a leather quiver full of arrows fletched with turkey feathers.",
		"She gives you a smirk as she passes.".

		"You turn as the elfin archer continues on her way.",
		"That is when you notice a large mangy dog in a tug-of-war with a troll.",
		"Clenched in the dog's teeth is a very large bone with bits of flesh still clinging to it.",
		"You can't tell if the troll is growling louder than the dog as it tries to wrest the bone from its jaw.",
		"Hanging from the troll's wide belt a gnarled club hangs against filthy breeches of animal skins.",

		"The sound of the man's voice brings your attention back to the matter at hand.`n`n",
		"\"`PYes. As a matter of fact I am looking for someone.`Q\"  you reply.",
		"\"`PI have gold in my purse to pay for the best fighter willing to join me in ridding this realm of vermin.`Q\"`n`n",
	),
	"buynav"		=> "Hire a mercenary",
	"healnav"		=> "Heal a companion",
	"healtext"        => array(
		"`QA surgeon takes a careful look at the many wounds of your companion.",
		"After murmuring to himself as he makes the evaluation, he turns to you to name the price to care for the wounds.",
	),
	"healnotenough" => array(
		"`QThe surgeon shakes his head then shrugs before turning away.",
		"You are left standing with your empty purse.",
		"No healing for someone who cannot pay.",
	),
	"healpaid" => array(
		array("`QA surgeon is caring for the wounds of %s`Q and bandages them with learned skill.", $displayname),
		"You gladly hand him the money owed for healing your companion and start heading back to the village.",
    ),
    "toomanycompanions"=> array(
    	"It seems no one is willing to follow you.",
    	"You simply lead too many companions at the moment."
    ),
    "manycompanions" => "Several mercenaries offer to join you:`n`n",
    "onecompanion" => "One mercenary offers to join you:`n`n",
    "nocompanions" => "No mercenaries off to join you.",
);

$schemas = array(
	"title"=>"mercenarycamp",
	"desc"=>"mercenarycamp",
	"buynav"=>"mercenarycamp",
	"healnav"=>"mercenarycamp",
	"healtext"=>"mercenarycamp",
	"healnotenough"=>"mercenarycamp",
	"healpaid"=>"mercenarycamp",
	"toomanycompanions"=>"mercenarycamp",
	"manycompanions"=>"mercenarycamp",
	"onecompanion"=>"mercenarycamp",
	"nocompanions"=>"mercenarycamp",
);

$basetext['schemas'] = $schemas;
$texts = modulehook("mercenarycamptext",$basetext);
$schemas = $texts['schemas'];

tlschema($schemas['title']);
page_header($texts['title']);
output("`c`b`&".$texts['title']."`0`b`c");
tlschema();

$op = httpget("op");

if ($op==""){
  	if (httpget('skip') != 1) {
		tlschema($schemas['desc']);
	  	if (is_array($texts['desc'])) {
	  		foreach ($texts['desc'] as $description) {
	  			output_notl(sprintf_translate($description));
	  		}
	  	} else {
	  		output($texts['desc']);
	  	}
	  	tlschema();
  	}

	$sql = "SELECT * FROM " .  db_prefix("companions") . "
				WHERE companioncostdks<={$session['user']['dragonkills']}
				AND (companionlocation = '{$session['user']['location']}' OR companionlocation = 'all')
				AND companionactive = 1";
	$result = db_query($sql);
  	tlschema($schemas['buynav']);
	addnav($texts['buynav']);
	tlschema();
	switch (db_num_rows($result)) {
		case 0:
			if (is_array($texts['nocompanions'])) {
				foreach ($texts['nocompanions'] as $description) {
					output_notl(sprintf_translate($description));
				}
			} else {
				output($texts['nocompanions']);
			}
			break;
		case 1:
			if (is_array($texts['onecompanion'])) {
				foreach ($texts['onecompanion'] as $description) {
					output_notl(sprintf_translate($description));
				}
			} else {
				output($texts['onecompanion']);
			}
			break;
		default:
			if (is_array($texts['manycompanions'])) {
				foreach ($texts['manycompanions'] as $description) {
					output_notl(sprintf_translate($description));
				}
			} else {
				output($texts['manycompanions']);
			}
			break;
	}
	while ($row = db_fetch_assoc($result)) {
		$row = modulehook("alter-companion", $row);
		if ($row['companioncostgold'] && $row['companioncostgems']) {
			if ($session['user']['gold'] >= $row['companioncostgold'] && $session['user']['gems'] >= $row['companioncostgems'] && !isset($companions[$row['name']])) {
				addnav(array("%s`n`^%s Gold, `%%%s Gems`0",$row['name'], $row['companioncostgold'], $row['companioncostgems']), "mercenarycamp.php?op=buy&id={$row['companionid']}");
			} else {
				addnav(array("%s`n`^%s Gold, `%%%s Gems`0",$row['name'], $row['companioncostgold'], $row['companioncostgems']), "");
			}
		} else if ($row['companioncostgold']) {
			if ($session['user']['gold'] >= $row['companioncostgold'] && !isset($companions[$row['name']])) {
				addnav(array("%s`n`^%s Gold`0",$row['name'], $row['companioncostgold']), "mercenarycamp.php?op=buy&id={$row['companionid']}");
			} else {
				addnav(array("%s`n`^%s Gold`0",$row['name'], $row['companioncostgold']), "");
			}
		} else if ($row['companioncostgems']) {
			if ($session['user']['gems'] >= $row['companioncostgems'] && !isset($companions[$row['name']])) {
				addnav(array("%s`n`%%%s Gems`0",$row['name'], $row['companioncostgems']), "mercenarycamp.php?op=buy&id={$row['companionid']}");
			} else {
				addnav(array("%s`n`%%%s Gems`0",$row['name'], $row['companioncostgems']), "");
			}
		} else if (!isset($companions[$row['name']])) {
			addnav(array("%s",$row['name']), "mercenarycamp.php?op=buy&id={$row['companionid']}");
		}
		output("`#%s`n`7%s`n`n",$row['name'], $row['description']);
	}
	healnav($companions, $texts, $schemas);
} else if ($op == "heal") {
	$cost = httpget('cost');
	if ($cost == 'notenough') {
		tlschema($schemas['healpaid']);
	  	if (is_array($texts['healnotenough'])) {
	  		foreach ($texts['healnotenough'] as $healnotenough) {
	  			output_notl(sprintf_translate($healnotenough));
	  		}
	  	} else {
	  		output($texts['healnotenough']);
	  	}
		tlschema();
	} else {
		$companions[$name]['hitpoints'] = $companions[$name]['maxhitpoints'];
		$session['user']['gold'] -= $cost;
		debuglog("spent $cost gold on healing a companion", false, false, "healcompanion", $cost);
		tlschema($schemas['healpaid']);
	  	if (is_array($texts['healpaid'])) {
	  		foreach ($texts['healpaid'] as $healpaid) {
	  			output_notl(sprintf_translate($healpaid));
	  		}
	  	} else {
	  		output($texts['healpaid']);
	  	}
		tlschema();
	}
	healnav($companions, $texts, $schemas);
	addnav("Navigation");
	addnav("Return to the camp", "mercenarycamp.php?skip=1");
} else if ($op == "buy") {
	$id = httpget('id');
	$sql = "SELECT * FROM ".db_prefix("companions")." WHERE companionid = $id";
	$result = db_query($sql);
	if ($row = db_fetch_assoc($result)) {
		$row['attack'] = $row['attack'] + $row['attackperlevel'] * $session['user']['level'];
		$row['defense'] = $row['defense'] + $row['defenseperlevel'] * $session['user']['level'];
		$row['maxhitpoints'] = $row['maxhitpoints'] + $row['maxhitpointsperlevel'] * $session['user']['level'];
		$row['hitpoints'] = $row['maxhitpoints'];
		$row = modulehook("alter-companion", $row);
		$row['abilities'] = @unserialize($row['abilities']);
		require_once("lib/buffs.php");
		if (apply_companion($row['name'], $row)) {
			output("`QYou hand over `^%s gold`Q and `%%s %s`Q.`n`n", (int)$row['companioncostgold'], (int)$row['companioncostgems'],translate_inline($row['companioncostgems'] == 1?"gem":"gems"));
			if (isset($row['jointext']) && $row['jointext'] > "") {
				output($row['jointext']);
			}
			$session['user']['gold'] -= $row['companioncostgold'];
			$session['user']['gems'] -= $row['companioncostgems'];
			debuglog("has spent {$row['companioncostgold']} gold and {$row['companioncostgems']} gems on hiring a mercenary ({$row['name']}).");
		} else {
			// applying the companion failed. Most likely they already have more than enough companions...
			tlschema($schemas['toomanycompanions']);
			if (is_array($texts['toomanycompanions'])) {
				foreach ($texts['toomanycompanions'] as $toomanycompanions) {
					output_notl(sprintf_translate($toomanycompanions));
				}
			} else {
				output($texts['toomanycompanions']);
			}
			tlschema();
		}
	}
	addnav("Navigation");
	addnav("Return to the camp", "mercenarycamp.php?skip=1");
}
addnav("Navigation");
villagenav();
page_footer();


function healnav($companions, $texts, $schemas) {
	global $session;
	tlschema($schemas['healnav']);
	addnav($texts['healnav']);
	tlschema();
	$healable = false;
	foreach ($companions as $name => $companion) {
		if (isset($companion['cannotbehealed']) && $companion['cannotbehealed'] == true) {
		} else {
			$pointstoheal = $companion['maxhitpoints'] - $companion['hitpoints'];
			if ($pointstoheal > 0) {
				$healable = true;
				$costtoheal = round(log($session['user']['level']+1) * ($pointstoheal + 10)*1.33);
				if ($session['user']['gold'] >= $costtoheal) {
					addnav(array("%s`0 (`^%s Gold`0)", $companion['name'], $costtoheal), "mercenarycamp.php?op=heal&name=".rawurlencode($name)."&cost=$costtoheal");
				} else {
					addnav(array("%s`0 (`\$Not enough gold`0)", $companion['name']), "mercenarycamp.php?op=heal&name=".rawurlencode($name)."&cost=notenough");
				}
			}
		}
	}
	if ($healable == true) {
	  	tlschema($schemas['healtext']);
	  	if (is_array($texts['healtext'])) {
	  		foreach ($texts['healtext'] as $healtext) {
	  			output_notl(sprintf_translate($healtext));
	  		}
	  	} else {
	  		output($texts['healtext']);
	  	}
		tlschema();
	}
}
?>