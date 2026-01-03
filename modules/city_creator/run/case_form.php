<?php
	include('modules/city_creator/city_creator_functions.php');

    debug('hello');
	if( !empty($cityid) )
	{
        debug('CityID is not empty');
		//
		// Get city data and send it for checking.
		//
		$sql = "SELECT *
				FROM " . db_prefix('cities') . "
				WHERE cityid = '$cityid'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		if( db_num_rows($result) <> 1 )
		{
			output('`n`$Error: That city was not found!`0`n`n');
			$row = city_creator_array_check();
		}
		else
		{
			$row['mods'] = @unserialize($row['cityblockmods']);
			$row['navs'] = @unserialize($row['cityblocknavs']);
			$row['vill'] = @unserialize($row['citytext']);
			$row['stab'] = @unserialize($row['stabletext']);
			$row['arm'] = @unserialize($row['armortext']);
			$row['weap'] = @unserialize($row['weapontext']);
			$row['merc'] = @unserialize($row['mercenarycamptext']);
			unset($row['cityblockmods'],$row['cityblocknavs'],$row['citytext'],$row['stabletext'],$row['armortext'],$row['weapontext'],$row['mercenarycamptext']);
			$row = city_creator_array_check($row);
		}
		$addnew = 'bool';
	}
	else
	{
		$row = city_creator_array_check();
        debug($row);
		$addnew = 'invisible';
	}

	$form = array(
		'City Details,title',
			'addnew'=>'Add as new?,'.$addnew,
			'module'=>'Module:,viewonly',
			'cityactive'=>'City is Active?,bool',
			'cityid'=>'City ID:,hidden',
			'cityauthor'=>'City Author:,string,30',
			'cityname'=>'Name:,string,30',
			'`^No colours or any sort of tags in the city name.,note',
			'citytype'=>'Type (City, Town, village, etc):,string,30',
			'`^No colours or any sort of tags in the type name.,note',
			'citychat'=>'Chat disabled in this City?,bool',
			'citytravel'=>'Travel:,enum,0,Safe,1,Dangerous',
			'cityroutes'=>'Routes:,citytextarea,30',
			'`^Leave empty to travel from this city to anywhere&#44; or enter city names to restrict routes.`n
			To stop all travel you can either block the travel link or block the \'cities\' module.`n
			`#Separate each city name with a comma!,note',
		'City Text,title',
			'villtitle'=>'Title:,string,60',
			'villdescription'=>'Main Description:,citytextarea,30',
			'villclock'=>'Clock Line:,text',
			'`2Requires one `b%s`b`n1st `b%s`b - current time.,note',
			'villnewest1'=>'Newest Player (you):,text',
			'villnewest2'=>'Newest Player (them):,text',
			'`2Requires one `b%s`b`n1st `b%s`b - newest player\'s name.,note',
			'villtalk'=>'Chat area line:,text',
			'villsayline'=>'Say Line:,string,60',
			'villgatenav'=>'Gate Nav:,string,60',
			'villfightnav'=>'Fight Nav:,string,60',
			'villmarketnav'=>'Market Nav:,string,60',
			'villtavernnav'=>'Tavern Nav:,string,60',
			'villinfonav'=>'Info Nav:,string,60',
			'villothernav'=>'Other Nav:,string,60',
			'villinnname'=>'Inn Name:,string,60',
			'villstablename'=>'Stable Name:,string,60',
			'villmercenarycamp'=>'Mercenarycamp Name:,string,60',
			'villarmorshop'=>'Armour shop Name:,string,60',
			'villweaponshop'=>'Weapon shop Name:,string,60',
			'villpvpstart'=>'PVP start message:,text',
			'villpvpwin'=>'PVP win message:,text',
			'`2Requires three `b%s`b`n1st `b%s`b - attacker\'s name.`n2nd `b%s`b - offline player\'s name.`n3rd `b%s`b - offline player\'s location.,note',
			'villpvploss'=>'PVP loss message:,text',
			'`2Requires four `b%s`b`n1st `b%s`b - attacker\'s name.`n2nd `b%s`b - offline player\'s name.`n3rd `b%s`b - offline player\'s location.`n4th `b%s`b - taunt.,note',
		'Stables Text,title',
			'stabtitle'=>'Title:,string,60',
			'stabdesc'=>'Main Description:,citytextarea,30',
			"`#Supported in the description (case matters):`n%N = The players's name.`n%L = Gender name (Lass, Lad),note",
			'stabnosuchbeast'=>'No Mounts:,citytextarea,30',
			'stabfinebeast'=>'Fine Mount:,citytextarea,30',
			'`2Multiple lines. Place each on a new line.,note',
			'stabtoolittle'=>'Too Little:,citytextarea,30',
			'`2Requires three `b%s`b`n1st `b%s`b - new mount name.`n2nd `b%s`b - cost gold.`n3rd `b%s`b - cost gems.,note',
			'stabreplacemount'=>'Replaced your mount:,citytextarea,30',
			'`2Requires two `b%s`b`n1st `b%s`b - current mount name.`n2nd `b%s`b - new mount name.,note',
			'stabnewmount'=>'Bought a mount:,citytextarea,30',
			'`2Requires one `b%s`b`n1st `b%s`b - new mount name.,note',
			'stabnofeed'=>'No feed:,citytextarea,30',
			'`2Requires one `b%s`b`n1st `b%s`b - lass/lad name.,note',
			'stabnothungry'=>'Mount isn\'t hungry:,citytextarea,30',
			'`2Requires one `b%s`b`n1st `b%s`b - current mount name.,note',
			'stabhalfhungry'=>'Mount is half hungry:,citytextarea,30',
			'`2Requires three `b%s`b`n1st `b%s`b - current mount name.`n2nd `b%s`b - feed cost.`n3rd `b%s`b - feed cost.,note',
			'stabhungry'=>'Mount is hungry:,citytextarea,30',
			'`2Requires three `b%s`b`n1st `b%s`b - current mount name.`n2nd `b%s`b - feed cost.`n3rd `b%s`b - feed cost.,note',
			'stabmountfull'=>'Fed mount:,citytextarea,30',
			'`2Requires two `b%s`b`n1st `b%s`b - lass/lad name.`n2nd `b%s`b - current mount name.,note',
			'stabnofeedgold'=>'No gold for feed:,citytextarea,30',
			'`2Requires one `b%s`b`n1st `b%s`b - current mount name.,note',
			'stabconfirmsale'=>'Confirm sale:,citytextarea,30',
			'`2Requires one `b%s`b`n1st `b%s`b - lass/lad name.,note',
			'stabmountsold'=>'Sold your mount:,citytextarea,30',
			'`2Requires two `b%s`b`n1st `b%s`b - sold mount name.`n2nd `b%s`b - repaid gold and gems.,note',
			'staboffer'=>'Offer for mount:,citytextarea,30',
			'`2Requires three `b%s`b`n1st `b%s`b - repaid gold.`n2nd `b%s`b - repaid gems.`n3rd `b%s`b - current mount name.,note',
			'stablass'=>'Female name (lass):,string,30',
			'stablad'=>'Male name (lad):,string,30',
		'Armour Shop Text,title',
			'armtitle'=>'Title:,string,60',
			'armdesc'=>'Main Description:,citytextarea,30',
			"`#Supported in the description (case matters):`n%N = The players's name.`n%A = The players's armor.`n%T = Trade in value,note",
			'armtradein'=>'Trade in:,citytextarea,30',
			'armnosuchweapon'=>'No such armour:,citytextarea,30',
			'armtryagain'=>'Try Again:,string,60',
			'armnotenoughgold'=>'Not enough gold:,citytextarea,30',
			'`2Requires one `b%s`b`n1st `b%s`b - armour name.,note',
			'armpayarmor'=>'Pay for armour:,citytextarea,30',
			'`2Requires three `b%s`b`n1st `b%s`b - old armour name.`n2nd `b%s`b - new armour name.`n3rd `b%s`b - new armour name.,note',
		'Weapon Shop Text,title',
			'weaptitle'=>'Title:,string,60',
			'weapdesc'=>'Main Description:,citytextarea,30',
			"`#Supported in the description (case matters):`n%N = The players's name.`n%W = The players's weapon.`n%T = Trade in value,note",
			'weaptradein'=>'Trade in:,citytextarea,30',
			'weapnosuchweapon'=>'No such weapon:,citytextarea,30',
			'weaptryagain'=>'Try Again:,string,60',
			'weapnotenoughgold'=>'Not enough gold:,citytextarea,30',
			'`2Requires one `b%s`b`n1st `b%s`b - weapon name.,note',
			'weappayweapon'=>'Pay for weapon:,citytextarea,30',
			'`2Requires two `b%s`b`n1st `b%s`b - old weapon name.`n2nd `b%s`b - new weapon name.,note',
		'Mercenary Camp Text,title',
			'merctitle'=>'Title:,string,60',
			'mercdesc'=>'Main Description:,citytextarea,30',
			'mercbuynav'=>'Buy Nav:,string,60',
			'merchealnav'=>'Heal Nav:,string,60',
			'merchealtext'=>'Healed Text:,citytextarea,30',
			'merchealnotenough'=>'Not Healed Text:,citytextarea,30',
			'merchealpaid'=>'Healed Paid:,citytextarea,30',
			'merctoomanycompanions'=>'Too many companions:,citytextarea,30',
			'mercmanycompanions'=>'Many offers to join you:,text',
			'merconecompanion'=>'One offers Tto join you:,text',
			'mercnocompanions'=>'No one offers to join you:,text',
		'Block Modules,title',
			'modsall'=>'Block all modules:,bool',
			'modsother'=>'List of modules to block or unblock:,textarearesizeable,30',
			'`^If you\'re blocking all modules then enter the module names above which you want to unblock. For example unblock the \'cities\' module if you want travel to work.`n`n
			If you\'re *not* blocking all modules then enter the module names above which you do want to block.`n`n
			`#Separate the names with a comma!,note',
		'Block Navs,title',
			'`^Select Yes to block the basic core nav links.,note',
			'navsforest_php'=>'forest.php,bool',
			'navspvp_php'=>'pvp.php,bool',
			'navsmercenarycamp_php'=>'mercenarycamp.php,bool',
			'navstrain_php'=>'train.php,bool',
			'navslodge_php'=>'lodge.php,bool',
			'navsweapons_php'=>'weapons.php,bool',
			'navsarmor_php'=>'armor.php,bool',
			'navsbank_php'=>'bank.php,bool',
			'navsgypsy_php'=>'gypsy.php,bool',
			'navsinn_php'=>'inn.php,bool',
			'navsstables_php'=>'stables.php,bool',
			'navsgardens_php'=>'gardens.php,bool',
			'navsrock_php'=>'rock.php,bool',
			'navsclan_php'=>'clan.php,bool',
			'navsnews_php'=>'news.php,bool',
			'navslist_php'=>'list.php,bool',
			'navshof_php'=>'hof.php,bool',
			'navsother'=>'List of navs to block:,textarearesizeable,30',
			'`^Any other nav links that you wish to block can be entered in the box above.`n`n`#Separate each url with a comma!,note'
	);

	//
	// Get the names of the modules that have 'prefs-cities' setting.
	//
	$sql = "SELECT formalname, modulename
			FROM " . db_prefix('modules') . "
			WHERE infokeys
			LIKE '%|prefs-city|%'
			ORDER BY formalname";
	$result = db_query($sql);
	while( $row2 = db_fetch_assoc($result) )
	{
		$formalname = $row2['formalname'];
		$modulename = modulename_sanitize($row2['modulename']);
		$info = get_module_info($modulename);
		if( count($info['prefs-city']) > 0 )
		{
			//
			// Get all the settings for each module and add to the array.
			//
			$form[] = $formalname.',title'; // Each module gets its own title.
			while( list($key, $val) = each($info['prefs-city']) )
			{
				if( ($pos = strpos($val, ',title')) !== FALSE )
				{	// Any titles get converted to notes.
					$val = '`^`i'.str_replace(',title', '`i,note', $val);
				}
				if( is_array($val) )
				{
					$v = $val[0];
					$x = explode("|", $v);
					$val[0] = $x[0];
					$x[0] = $val;
				}
				else
				{
					$x = explode("|", $val);
				}
				$form[$modulename.'-'.$key] = $x[0];
				// Set up default values.
				$row[$modulename.'-'.$key] = ( isset($x[1]) ) ? $x[1] : '';
			}

			//
			// Now get any data for the settings.
			//
			$sql = "SELECT setting, value
					FROM " . db_prefix('module_objprefs') . "
					WHERE modulename = '$modulename'
						AND objtype = 'city'
						AND objid = '$cityid'";
			$result2 = db_query($sql);
			while( $row3 = db_fetch_assoc($result2) )
			{
				$row[$modulename.'-'.$row3['setting']] = stripslashes($row3['value']);
			}
		}
	}

	//
	// Display form.
	//
	rawoutput('<form action="'.$from.'&op=save" method="POST">');
	addnav('',$from.'&op=save');
	require_once('lib/showform.php');
	showform($form, $row);
	rawoutput('<input type="hidden" name="oldvalues" value="'.htmlentities(serialize($row), ENT_COMPAT, getsetting("charset", "ISO-8859-1")).'" /></form>');

	addnav('Editor');
	addnav('Add a City',$from.'&op=form');
	addnav('Main Page',$from);
?>