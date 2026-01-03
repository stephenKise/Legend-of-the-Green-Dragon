<?php
function city_creator_array_check($city=FALSE)
{
	//
	// Make sure that all the variables exist.
	//
	$mods = [
        'all' => 0,
        'other' => '',
    ];
	$navs = [
        'other' => '',
        'forest_php' => 0,
        'pvp_php' => 0,
        'mercenarycamp_php' => 0,
        'train_php' => 0,
        'lodge_php' => 0,
        'weapons_php' => 0,
        'armor_php' => 0,
        'bank_php' => 0,
        'gypsy_php' => 0,
        'inn_php' => 0,
        'stables_php' => 0,
        'gardens_php' => 0,
        'rock_php' => 0,
        'clan_php' => 0,
        'news_php' => 0,
        'list_php' => 0,
        'hof_php' => 0
    ];
	$vill = [
        'title' => 'Degolburg Square',
        'description' => "`@`c`bDegolburg Square`b`c
            The village of Degolburg hustles and bustles.  No one really notices that you're standing there. 
        	You see various shops and businesses along main street.  There is a curious looking rock to one side.
        	On every side the village is surrounded by deep dark forest.`n`n
        ",
        'clock' => 'The clock on the inn reads `^%s`@.`n',
        'newest1' => '`nYou\'re the newest member of the village.  As such, you wander around, gaping at the sights, and generally looking lost.',
        'newest2' => '`n`2Wandering near the inn is `&%s`2, looking completely lost.',
        'talk' => '`n`%`@Nearby some villagers talk:`n',
        'sayline' => 'says',
        'gatenav' => 'City Gates',
        'fightnav' => 'Blades Boulevard',
        'marketnav' => 'Market Street',
    	'tavernnav' => 'Tavern Street',
    	'infonav' => 'Info',
    	'othernav' => 'Other',
    	'section' => 'village',
    	'innname'=> 'The Boar\'s Head Inn',
    	'stablename' => 'Merick\'s Stables',
    	'mercenarycamp' => 'Mercenary Camp',
    	'armorshop' => 'Pegasus Armor',
    	'weaponshop' => 'MightyE\'s Weaponry',
        'pvpstart' => '',
        'pvpwin' => '',
        'pvploss' => '',
    ];
	$stab = [
        'title' => '',
        'desc' => '',
        'nosuchbeast' => '',
        'finebeast' => '',
        'toolittle' => '',
        'replacemount' => '',
        'newmount' => '',
        'nofeed' => '',
        'nothungry' => '',
        'halfhungry' => '',
        'hungry' => '',
        'mountfull' => '',
        'nofeedgold' => '',
        'confirmsale' => '',
        'mountsold' => '',
        'offer' => '',
        'lass' => '',
        'lad' => ''
    ];
	$arm = [
        'title' => '',
        'desc' => '',
        'tradein' => '',
        'nosuchweapon' => '',
        'tryagain' => '',
        'notenoughgold' => '',
        'payarmor' => ''
    ];
	$weap = [
        'title' => "MightyE's Weapons",
        'desc' => "`!MightyE `7stands behind a counter and appears to pay little attention " .
            "to you as you enter, but you know from experience that he has his eye " .
            "on every move you make. He may be a humble weapons merchant, but he " .
            "still carries himself with the grace of a man who has used his " .
            "weapons to kill mightier warriors than you.`n`n " .
            "The massive hilt of a claymore protrudes above his shoulder; its gleam " .
            "in the torch light not much brighter than the gleam off of `!MightyE's " .
            "`7 bald forehead, kept shaved mostly as a strategic advantage, but " .
            "in no small part because nature insisted that some level of baldness " .
            "was necessary.`n`n" .
            "`!MightyE`7 finally nods to you, stroking his goatee and looking like " .
            "he wished he could have an opportunity to use one of these weapons. ",
        'tradein' => "`7You stroll up the counter and try your best to look like you know what most of these contraptions do. " .
            "`!MightyE`7 looks at you and says, \"`#I\'ll give you `^%s`# trade-in value for your `5%s`#. " .
            "Just click on the weapon you wish to buy, what ever 'click' means`7,\" and looks utterly confused. " .
            "He stands there a few seconds, snapping his fingers and wondering if that is what is meant by 'click,' before returning to his work: standing there and looking good.`n`n",
        'nosuchweapon' => "`!MightyE`7 looks at you, confused for a second, then realizes that you've apparently taken one too many bonks on the head, and nods and smiles.",
        'tryagain' => 'Try again?',
        'notenoughgold' => "Waiting until `!MightyE`7 looks away, you reach carefully for the `5%s`7, which you silently remove from the rack upon which it sits. Secure in your theft, you turn around and head for the door, swiftly, quietly, like a ninja, only to discover that upon reaching the door, the ominous `!MightyE`7 stands, blocking your exit. You execute a flying kick. Mid flight, you hear the \"SHING\" of a sword leaving its sheath.... your foot is gone. You land on your stump, and `!MightyE`7 stands in the doorway, claymore once again in its back holster, with no sign that it had been used, his arms folded menacingly across his burly chest.  \"`#Perhaps you'd like to pay for that?`7\" is all he has to say as you collapse at his feet, lifeblood staining the planks under your remaining foot.`n`nYou wake up some time later, having been tossed unconscious into the street.",
        'payweapon' => "`!MightyE`7 takes your `5%s`7 and promptly puts a price on it, setting it out for display with the rest of his weapons.`n`nIn return, he hands you a shiny new `5%s`7 which you swoosh around the room, nearly taking off `!MightyE`7's head, which he deftly ducks; you're not the first person to exuberantly try out a new weapon.",
    ];
	$merc = [
        'title' => '',
        'desc' => '',
        'buynav' => '',
        'healnav' => '',
        'healtext' => '',
        'healnotenough' => '',
        'healpaid' => '',
        'toomanycompanions' => '',
        'manycompanions' => '',
        'onecompanion' => '',
        'nocompanions' => ''
    ];

	if( !isset($city['cityactive']) )						$city['cityactive'] = 0;
	if( !isset($city['cityname']) )							$city['cityname'] = '';
	if( !isset($city['citytype']) )							$city['citytype'] = '';
	if( !isset($city['cityauthor']) )						$city['cityauthor'] = '';
	if( !isset($city['cityid']) )							$city['cityid'] = 0;
	if( !isset($city['citychat']) )							$city['citychat'] = 0;
	if( !isset($city['citytravel']) )						$city['citytravel'] = 0;
	if( !isset($city['module']) )							$city['module'] = '';
	if( !isset($city['mods']) || !is_array($city['mods']) )	$city['mods'] = $mods;
	if( !isset($city['navs']) || !is_array($city['navs']) )	$city['navs'] = $navs;
	if( !isset($city['vill']) || !is_array($city['vill']) )	$city['vill'] = $vill;
	if( !isset($city['stab']) || !is_array($city['stab']) )	$city['stab'] = $stab;
	if( !isset($city['arm']) || !is_array($city['arm']) )	$city['arm'] = $arm;
	if( !isset($city['weap']) || !is_array($city['weap']) )	$city['weap'] = $weap;
	if( !isset($city['merc']) || !is_array($city['merc']) )	$city['merc'] = $merc;

    // Check the arrays for missing fields and add them if not found.
	$names = [
        'mods',
        'navs',
        'vill',
        'stab',
        'arm',
        'weap',
        'merc',
    ];
	foreach( $names as $name )
	{
		foreach( ${$name} as $key  =>  $value )
		{
			if( !array_key_exists($key, $city[$name]) )
			{
				$city[$name][$key] = $value;
			}
		}
	}

	// Go through all the data and stripslashes.
	foreach( $city as $key  =>  $value )
	{
		if( is_array($value) )
		{
			foreach( $value as $key2  =>  $value2 )
			{
				$city[$key.$key2] = ( is_string($value2) ) ? stripslashes($value2) : (int)$value2;
			}
			unset($city[$key]);
		}
		else
		{
			$city[$key] = ( is_string($value) ) ? stripslashes($value) : (int)$value;
		}
	}

	return $city;
}
