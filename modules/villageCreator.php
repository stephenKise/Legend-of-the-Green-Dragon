<?php
/**
	24/10/2010 - v0.0.1 - Rough draft.
	13/04/2013 - v1.0.0 - Release.
	18/04/2013 - v1.0.1
	+ Added setting to take the cityprefs table data and insert it into the city creator table.
	  This makes sure that the ID's stays correct for each city so the object prefs don't get messed up.
	02/07/2013 - v1.0.2
	+ Added function 'city_creator_location' which processes a new showform.php field (citylocation) which is used in my race_creator module.
	  It basically allows the form to list all locations, even inactive ones. This way races can be assigned to inactive cities. Idea from Rayn D. :)
	+ Capital city can no longer be deactivated or deleted. If you rename it then the game settings 'villagename' will be changed to match.
	10/02/2016 - v1.0.3
	+ Blocking all modules even blocked this module. OOPS! Unblocked it. Thanks Doctor. :)

	Disclaimer:
	The cities that comes supplied are Human, Elf, Dwarf and Troll. All text and code supplied with
	these cities come from the 4 core race modules supplied with LotGD and belong to Eric Stevens.
	I make no claim to the text or code which I simply copied from the module files.
*/
function villageCreator_getmoduleinfo(): array
{
	$info = array(
		"name"=>"City Creator",
		"description"=>"Create all the cities you want.",
		"version"=>"1.0.2",
		"author"=>"`@MarcTheSlayer",
		"category"=>"Cities",
		"download"=>"",
		"settings"=>array(
			"Settings,title",
				"`^Note: Do this first before creating any cities. Select Yes and save..,note",
				"transfer"=>"Transfer the 'cityprefs' cities over?,enum,0,No,1,Yes,2,Done",
				"`^Note: This is so the object prefs that are set for each city will continue to match. These cities will appear in the city creator editor and will require slight editing.`n`n`bDo not do more than once!`b,note",
		),
		"requires"=>array(
			"villages"=>"1.2|Eric Stevens`2, modified by `@MarcTheSlayer",
		),
		"prefs"=>array(
			"inn_at"=>"City name of the inn player last entered:,text",
		)
	);
	return $info;
}

function villageCreator_install(): bool
{
	require_once('modules/VillageCreator/install.php');
	return true;
}

function villageCreator_uninstall()
{
	global $session, $mysqli_resource;
	output("village_creator.headers.uninstall");

	$city = mysqli_real_escape_string(
        $mysqli_resource,
        getsetting('villagename', LOCATION_FIELDS)
    );
    $accountsTable = db_prefix('accounts');
    $villagesTable = db_prefix('villages');
	db_query(
        "UPDATE $accountsTable SET location = '$city' WHERE location != '$city'"
    );
	$session['user']['location'] = $city;
	
	$result = db_query("SELECT id, name FROM $villagesTable");
	while ($row = db_fetch_assoc($result)) {
		modulehook(
            'village-invalidate-cache',
            ['id' => $row['id'], 'name' => $row['name']]
        );
	}

	db_query("DROP TABLE IF EXISTS $villagesTable");
	return true;
}


$villageData = [];
function getVillageData(string|bool $village = false)
{
	global $villageData;
    $villagesTable = db_prefix('villages');

	if (
        isset($villageData['name']) &&
        ($villageData['name'] == $village || $villageData['id'] == $village)
    ) {
		return $villageData;
	}

	$where = FALSE;
	if (is_numeric($village) && $village > 0) $where = "id = '$village'";
	else if(is_string($village)) $where = "name = '$village' LIMIT 1";
	if ($where)	{
		$sql = "SELECT *
				FROM $villagesTable
				WHERE $where";
		$result = db_query_cached($sql, "village_id_$village", 86400);
		$villageData = db_fetch_assoc($result);
	}
	return $villageData;
}

function villageCreator_dohook(string $hookName, array $args)
{
	global $session;
    $villagesTable = db_prefix('villages');
    $accountsTable = db_prefix('accounts');

	switch ($hookName) {
		case 'header-superuser':
			if ($session['user']['superuser'] & SU_EDIT_USERS) {
				addnav('village_creator.nav_headers.creators');
				addnav(
                    '{{village_creator.navs.creator}}',
                    'runmodule.php?module=villageCreator'
                );
				addnav('village_creator.nav_headers.editors');
			}
		break;

		case 'changesetting':
            // global $mysqli_resource;
			if ($args['setting'] == 'villagename') {
				db_query(
                    "UPDATE $villagesTable
                    SET name = '{$args['new']}'
                    WHERE name = '{$args['old']}'"
                );
				if ($session['user']['location'] == $args['old']) {
                    $session['user']['location'] = $args['new'];
                }
			}
			// if ($args['module'] == 'villageCreator') {
			// 	if ($args['setting'] == 'transfer' && $args['new'] == 1){
			// 		output('`n`Q`bTransfering....`b`n`n');
	
			// 		$sql = "SELECT id, name, module
			// 				FROM " . db_prefix('cityprefs') . "
			// 				ORDER BY id";
			// 		$result = db_query($sql);
            //         $author = mysqli_escape_string($mysqli_resource, $session['user']['name']);
			// 		// $author_array = array('none'=>'Eric Stevens','raceangel'=>'Jigain To`lerean','racedarkelf'=>'Kevin Hatfield','racearchangel_city'=>'RPGSL','racearchdemon_city'=>'RPGSL','racedraconis'=>'T. J. Brumfield','villagetokyo'=>'Haku','racezombie'=>'Dan Hall','racevulcan'=>'Lewis Little','racevamp'=>'Chris Vorndran','racevampire'=>'`4Thanatos','raceurgal'=>'Jonathan Newton','racetroll'=>'Eric Stevens','racespecialtyreptile'=>'`@CortalUX','racespecialtylion'=>'Peter Corcoran','raceSchwertjaeger'=>'Haku','racesaur'=>'anpera','racerobot'=>'Chris Thomas','racepirate'=>'John McNally','racepaladin'=>'Eternal','raceninja'=>'Gordon McCallum','racemutant'=>'Dan Hall','racemorph'=>'`QBasilius `qSauter','racemidget'=>'Dan Hall','racemermaid'=>'Adam Churchill','racelupe'=>'Rowne Mastaile','racelilty'=>'`QBasilius `qSauter','racekronos'=>'Jigain To`lerean','raceklingon'=>'Daniel Cannon','racekittymorph'=>'Dan Hall','racejoker'=>'Dan Hall','racehuman'=>'Eric Stevens','racehalf'=>'Jonathan Newton','racegreedy_barbarian'=>'Magic','racegoth'=>'Justin Holland','racegefallenerEngel'=>'Haku','racegeek'=>'Gordon McCallum','racefiend'=>'Jonathan Newton','raceferengi'=>'Daniel Cannon','raceelf'=>'Eric Stevens','raceelemental'=>'shadowblack','racedwarf'=>'Eric Stevens','racederyni'=>'Jonathan Newton','racedarkfelyne'=>'`!Akutozo','racecelestial'=>'Jonathan Newton','racecanadian'=>'Soj Services','racebyrdc'=>'Enhas','racebunny'=>'Billie Kennedy','racebirdy'=>'Victor Clarke','newbieisland'=>'Eric Stevens','icetown'=>'Shannon Brown','ghosttown'=>'Shannon Brown','eastertown'=>'`@CortalUX','citygeneric3'=>'Billie Kennedy','citygeneric2'=>'Billie Kennedy','citygeneric1'=>'Billie Kennedy','cityamwayr'=>'Billie Kennedy','racetitan'=>'T. J. Brumfield','racehorse'=>'Rowne Mastaile','racegnome'=>'T. J. Brumfield');
			// 		while( $row = db_fetch_assoc($result) )
			// 		{
			// 			// $author = ( isset($author_array[$row['module']]) ) ? $author_array[$row['module']] : '';
			// 			db_query(
            //                 "INSERT INTO $villagesTable (`id`, `name`, `author`,
            //                     `type`, `active`, `module`) 
            //                 VALUES ('{$row['id']}', '{$row['name']}', '$author',
            //                     'Village', 1, 'prefs')"
            //             );
			// 			if (db_affected_rows() > 0) output('`2City ID: `3%s `2Cityname: `3%s `2Module: `3%s `2Author: `3%s`n`n', $row['id'], $row['name'], $row['module'], $author);
			// 			else output('`$Error: Couldn\'t insert city "`6%s`$" into the table. Are you sure no cities existed before doing this?', $row['name']);
			// 		}

			// 		output('`2The cityprefs table data (cityids and citynames) have been copied into the city creator table.`0`n`n');

			// 		set_module_setting('transfer',2);
			// 	}
			// }
		break;

		// case 'cityinvalidatecache':
		// 	invalidatedatacache("village_id_{$args['id']}");
		// 	invalidatedatacache("village_id_{$args['name']}");
		// 	invalidatedatacache('village_locations');
		// 	invalidatedatacache('village_travel');
		// break;

		// case 'everyhit-loggedin':
		// 	global $SCRIPT_NAME;
		// 	$script = substr($SCRIPT_NAME, 0, strrpos($SCRIPT_NAME, '.'));
		// 	if ($script != 'village') break;
		// 	// Block/unblock modules
		// 	$row = getVillageData($session['user']['location']);
		// 	$row['cityblockmods'] = @unserialize($row['cityblockmods']);
		// 	if (!is_array($row['block_mods'])) break;
		// 	if (
        //         isset($row['block_mods']['all']) &&
        //         $row['block_mods']['all'] == 1
        //     ) {	// Block all modules.
		// 		blockmodule(true);
		// 		unset($row['block_mods']['all']);
		// 		if (
        //             isset($row['block_mods']['other']) &&
        //             !empty($row['block_mods']['other'])
        //         ) {	// Now unblock the modules you want.
		// 			unblockmodule('villageCreator'); // Better unblock this. =)
		// 			$others = explode(',', $row['block_mods']['other']);
		// 			foreach( $others as $module )
		// 			{
		// 				unblockmodule($module);
		// 				debug(loadTranslation('village_creator.debug.unblock_modules', [$module]));
		// 			}
		// 		}
		// 	}
		// 	else {
		// 		unset($row['block_mods']['all']);
		// 		if (
        //             isset($row['block_mods']['other']) &&
        //             !empty($row['block_mods']['other'])
        //         ) {
		// 			$others = explode(',', $row['block_mods']['other']);
		// 			foreach ($others as $module) {
		// 				blockmodule($module);
		// 				debug(
        //                     loadTranslation(
        //                         'village_creator.debug.block_modules ',
        //                         [$module]
        //                     )
        //                 );
		// 			}
		// 		}
		// 	}
		// break;

		case 'header-village':
			$row = getVillageData($session['user']['location']);
			// Block navs.

			$row['block_navs'] = is_serialized($row['block_navs']) ? @unserialize($row['block_navs']) : [];
			if (is_array($row['block_navs'])) {
				if (
                    isset($row['block_navs']['other']) &&
                    !empty($row['block_navs']['other'])
                ) {	// Block other navs first.
					$otherNavs = explode(',', $row['block_navs']['other']);
					$count = count($otherNavs);
					if( $count > 0 )
					{
						for( $i=0; $i<$count; $i++ )
						{
							if( $otherNavs[$i+1] == 'true' )
							{
								blocknav($otherNavs[$i],TRUE);
								debug(
                                loadTranslation(
                                    'village_creator.debug.block_nav_partial', 
                                    [$otherNavs[$i]]
                                ));
								$i++;
								continue;
							}
							blocknav($otherNavs[$i]);
							debug(
                                loadTranslation(
                                    'village_creator.debug.block_nav', 
                                    [$otherNavs[$i]]
                                )
                            );
						}
					}
				}
				unset($row['block_navs']['other']);
				// Now block original village navs.
				foreach ($row['block_navs'] as $nav => $value) {
					if ($value == 1) {
						blocknav("{$nav}.php");
						debug(
                            loadTranslation(
                                'village_creator.debug.block_nav_core', 
                                [$nav]
                            )
                        );
					}
				}
			}
		break;

		case 'villagetext':
            global $TRANSLATION_CACHE, $language;
            if ($session['user']['location'] == getsetting('villagename', LOCATION_FIELDS)) return $args;
            $currentLocation = sanitize($session['user']['location']);
            $currentLocation = str_replace(' ', '', $currentLocation);
            if (!file_exists("translations/{$language}/modules/village_{$currentLocation}.yaml")) return $args;
            loadNamespace("village_{$currentLocation}");
            $newCharacter = $args['new_character_data'];
            unset($args);
            $i18n = $TRANSLATION_CACHE["{$language}.village_{$currentLocation}"];
            $i18n['title'] = sprintf(
                $i18n['title'],
                $session['user']['location']
            );
			$i18n['description'] = str_replace(
                '`%',
                '`%%',
                $i18n['description']
            );
            $i18n['description'] = sprintf(
                $i18n['description'],
                $session['user']['location'],
                $session['user']['location']
            );
            $i18n['new_character'] = sprintf(
                $i18n['new_character'],
                $newCharacter['name']
            );
            $args = $i18n;
			// $row = getVillageData($session['user']['location']);
			// // Change to custom text.
            // $args[]
			// if ($row['text'] != '') {
            //     debug($row['text']);
			// 	$row['text'] = @unserialize($row['text']);
			// 	if (!is_array($row['text']) ) brea;

			// 	$new = 0;
			// 	if (is_module_active('cities')) {	// If 'cities' module is installed then get newest player in this city.
			// 		$new = get_module_setting("newest-{$row['name']}",'cities');
			// 		if ($new != 0)
			// 		{
			// 			$sql = "SELECT name
			// 					FROM $accountsTable
			// 					WHERE acctid = '$new'";
			// 			$result = db_query_cached($sql, "newest-{$row['name']}");
			// 			$row2 = db_fetch_assoc($result);
			// 			$args['newestplayer'] = $row2['name'];
			// 			$args['newestid'] = $new;
			// 		}
			// 		else
			// 		{
			// 			$args['newestplayer'] = $new;
			// 			$args['newestid'] = '';
			// 		}
			// 	}
			// 	if ($new == $session['user']['acctid']) {
			// 		$row['text']['newest'] = $row['text']['newest1'];
			// 	}
			// 	else if ($new != $session['user']['acctid']) {
			// 		$row['text']['newest'] = $row['text']['newest2'];
			// 	}

			// 	$names = array('title','text','clock','newest','talk','sayline','section','gatenav','fightnav','marketnav','tavernnav','infonav','othernav','innname','stablename','mercenarycamp','armorshop','weaponshop');

			// 	foreach ($names as $name )
			// 	{	// If the field has text then process it.
			// 		if (isset($row['text'][$name]) && !empty($row['text'][$name]))
			// 		{
			// 			$args[$name] = stripslashes($row['text'][$name]);
			// 			$args['schemas'][$name] = 'module-'.$row['module'];
			// 		}
			// 	}
			// 	// Change the chat section to that of the name.
			// 	$args['commentary']['section'] = 'village-' . strtolower(str_replace(' ','',$row['name']));
			// 	$args['schemas']['section'] = 'module-'.$row['module'];
			// }
		break;

		// case 'stabletext':
		// 	$row = getVillageData($session['user']['location']);
		// 	// Change to custom text.
		// 	if ($row['stabletext'] != '')
		// 	{
		// 		$row['stabletext'] = @unserialize($row['stabletext']);
		// 		if (!is_array($row['stabletext']) ) brea;

		// 		$names = array('title','desc','nosuchbeast','finebeast','toolittle','replacemount','newmount','nofeed','nothungry','halfhungry','hungry','mountfull','nofeedgold','confirmsale','mountsold','offer','lass','lad');

		// 		$search = array('%N','%L');
		// 		$replace = array($session['user']['name'],($session['user']['sex']?$row['stabletext']['stablass']:$row['stabletext']['stablad']));

		// 		foreach ($names as $name )
		// 		{	// If the field has text then process it.
		// 			if (isset($row['stabletext'][$name]) && !empty($row['stabletext'][$name]))
		// 			{
		// 				if ($name == 'desc' ) $row['stabletext'][$name] = str_replace($search, $replace, $row['stabletext'][$name];
		// 				if ($name == 'finebeast')
		// 				{	// Put each line into an array.
		// 					$row['stabletext'][$name] = explode("\r\n", $row['stabletext'][$name]);
		// 					foreach ($row['stabletext'][$name] as $key => $value ) $row['stabletext'][$name][$key] = stripslashes($value);
		// 				}
		// 				$args[$name] = ( is_array($row['stabletext'][$name]) ) ? $row['stabletext'][$name] : stripslashes($row['stabletext'][$name]);
		// 				$args['schemas'][$name] = 'module-'.$row['module'];
		// 			}
		// 		}
		// 	}
		// break;

		// case 'armortext':
		// 	$row = getVillageData($session['user']['location']);
		// 	// Change to custom text.
		// 	if ($row['armortext'] != '')
		// 	{
		// 		$row['armortext'] = @unserialize($row['armortext']);
		// 		if (!is_array($row['armortext']) ) brea;

		// 		$names = array('title','desc','tradein','nosuchweapon','tryagain','notenoughgold','payarmor');

		// 		$search = array('%N','%A','%T');
		// 		$replace = array($session['user']['name'],$session['user']['armor'],round(($session['user']['armorvalue']*.75),0));

		// 		foreach ($names as $name )
		// 		{	// If the field has text then process it.
		// 			if (isset($row['armortext'][$name]) && !empty($row['armortext'][$name]))
		// 			{
		// 				if ($name == 'desc' || $name == 'tradein' ) $row['armortext'][$name] = str_replace($search, $replace, $rw['armortext'][$name]);
		// 				$args[$name] = stripslashes($row['armortext'][$name]);
		// 				$args['schemas'][$name] = 'module-'.$row['module'];
		// 			}
		// 		}
		// 	}
		// break;

		// case 'weaponstext':
		// 	$row = getVillageData($session['user']['location']);
		// 	// Change to custom text.
		// 	if ($row['weaponstext'] != '')
		// 	{
		// 		$row['weaponstext'] = @unserialize($row['weaponstext']);
		// 		if (!is_array($row['weaponstext']) ) brea;

		// 		$names = array('title','desc','tradein','nosuchweapon','tryagain','notenoughgold','payweapon');

		// 		$search = array('%N','%W','%T');
		// 		$replace = array($session['user']['name'],$session['user']['weapon'],round(($session['user']['weaponvalue']*.75),0));

		// 		foreach ($names as $name )
		// 		{	// If the field has text then process it.
		// 			if (isset($row['weaponstext'][$name]) && !empty($row['weaponstext'][$name]))
		// 			{
		// 				if ($name == 'desc' || $name == 'tradein' ) $row['weaponstext'][$name] = str_replace($search, $replace, $rw['weaponstext'][$name]);
		// 				$args[$name] = stripslashes($row['weaponstext'][$name]);
		// 				$args['schemas'][$name] = 'module-'.$row['module'];
		// 			}
		// 		}
		// 	}
		// break;

		// case 'mercenarycamptext':
		// 	$row = getVillageData($session['user']['location']);
		// 	// Change to custom text.
		// 	if ($row['mercenarycamptext'] != '')
		// 	{
		// 		$row['mercenarycamptext'] = @unserialize($row['mercenarycamptext']);
		// 		if (!is_array($row['mercenarycamptext']) ) brea;

		// 		$names = array('title','desc','buynav','healnav','healtext','healnotenough','healpaid','toomanycompanions','manycompanions','onecompanion','nocompanions');
				
		// 		foreach ($names as $name )
		// 		{	// If the field has text then process it.
		// 			if (isset($row['mercenarycamptext'][$name]) && !empty($row['mercenarycamptext'][$name]))
		// 			{
		// 				$args[$name] = stripslashes($row['mercenarycamptext'][$name]);
		// 				$args['schemas'][$name] = 'module-'.$row['module'];
		// 			}
		// 		}
		// 	}
		// break;

		case 'travel':
			$city = getVillageData($session['user']['location']);

			addnav('Safer Travel');
			addnav('More Dangerous Travel');
			addnav('Superuser Travel');
			
			$sql = "";
			$result = db_query_cached(
				"SELECT id, name, travel
				FROM $villagesTable
				WHERE active = 1",
				'village_travel',
				86400
			);
			while ($row = db_fetch_assoc($result)) {
				if ($session['user']['location'] == $row['name']) {
					continue;
				}

				$name = translate_inline($row['name']);
				$hotkey = substr(sanitize($name), 0, 1);
				// Modulehook to see if any modules object, or give access on rare occasions.
				$prereq = modulehook('cityprerequisite',array('currentcityid'=>$city['id'],'currentcityname'=>$city['name'],'id'=>$row['id'],'name'=>$row['name'],'travel'=>$row['travel'],'blocked'=>0));
				if ($prereq['blocked'] == 0) {
					if ($prereq['travel'] == 0) {
						addnav('village_creator.nav_headers.travel_safe');
						addnav(array("%s?Go to %s", $hotkey, $name),"runmodule.php?module=villages&op=travel&village=".urlencode($row['name']));
					}
					else if ($prereq['travel'] == 1) {
						addnav('village_creator.nav_headers.travel_dangerous');
						addnav(array("%s?Go to %s", $hotkey, $name),"runmodule.php?module=villages&op=travel&village=".urlencode($row['name'])."&d=1");
					}
					else {
						debug("Travel to $name is Off.");
					}
				}
				if ($session['user']['superuser'] & SU_DEVELOPER) {
					addnav('village_creator.nav_headers.travel_superuser');
					addnav(loadTranslation('village_creator.navs.go_to', [$hotkey, $name]),"runmodule.php?module=villages&op=travel&village=".urlencode($row['name'])."&su=1");
				}
			}
		break;

		case 'validlocation':
			$sql = "SELECT id, name, sanitized_name, travel
					FROM $villagesTable
					WHERE active = 1";
			$result = db_query_cached($sql,'village_travel', 10);
			while( $row = db_fetch_assoc($result) )
			{
				if (isset($args['all']) && $args['all'] == 1)
				{
					$args[$row['name']] = 'village-'.strtolower(str_replace(' ','',$row['sanitized_name']));
				}
				else
				{
					// Never block the default village, no point as you'll get sent there anyway. :)
					$prereq = modulehook('cityvalidlocations',array('id'=>$row['id'],'name'=>$row['name'],'travel'=>$row['travel'],'blocked'=>0));
					if ($prereq['blocked'] == 1 ) continue;
					$args[$row['name']] = "village-{$row['sanitized_name']}";
				}
			}
		break;

		case 'validforestloc':
			$sql = "SELECT name, sanitized_name, type, block_navs
					FROM $villagesTable
					WHERE active = 1";
			$result = db_query_cached($sql, 'village_locations', 86400);
			while( $row = db_fetch_assoc($result) )
			{	// If the forest link has been blocked then there is no forest in this city.
				$row['block_navs'] = @unserialize(stripslashes($row['block_navs']));
				if (!is_array($row['block_navs']) ) $row['block_navs'] = array('all'=>0,'forest.php'=>0);
				if (
                    (isset($row['block_navs']['all']) && $row['block_navs']['all'] != 1) ||
                    (isset($row['block_navs']['fores.php']) && $row['block_navs']['forest.php'] != 1 )
                )
				{
					$args[$row['name']] = sprintf("The %s of %s", $row['type'], $row['name']);
				}
			}
		break;

		// case 'stablelocs':
		// 	tlschema('mounts');
		// 	$sql = "SELECT name, type, block_navs
		// 			FROM $villagesTable
		// 			WHERE active = 1";
		// 	$result = db_query_cached($sql,'village_locations',86400);
		// 	while( $row = db_fetch_assoc($result) )
		// 	{	// If the stables link has been blocked then there are no stables in this city.
		// 		$row['block_navs'] = @unserialize(stripslashes($row['block_navs']));
		// 		if( !is_array($row['block_navs']) ) $row['block_navs'] = array('all'=>0,'stables.php'=>0);
		// 		if( $row['block_navs']['all'] != 1 || $row['block_navs']['stables.php'] != 1 )
		// 		{
		// 			$args[$row['name']] = sprintf_translate("The %s of %s", translate_inline($row['type']), translate_inline($row['name']));
		// 		}
		// 	}
		// 	tlschema();
		// break;

		// case 'camplocs':
		// 	$sql = "SELECT name, type, block_navs
		// 			FROM $villagesTable
		// 			WHERE active = 1";
		// 	$result = db_query_cached($sql,'village_locations',86400);
		// 	while( $row = db_fetch_assoc($result) )
		// 	{	// If the camp link has been blocked then there is no camp in this city.
		// 		$row['block_navs'] = @unserialize(stripslashes($row['block_navs']));
		// 		if( !is_array($row['block_navs']) ) $row['block_navs'] = array('all'=>0,'mercenarycamp.php'=>0);
		// 		if( $row['block_navs']['all'] != 1 || $row['block_navs']['mercenarycamp.php'] != 1 )
		// 		{
		// 			$args[$row['name']] = sprintf_translate("The %s of %s", translate_inline($row['type']), translate_inline($row['name']));
		// 		}
		// 	}
		// break;

		case 'moderate':
			$result = db_query(
                "SELECT name, type
				FROM $villagesTable
				WHERE active = 1
				ORDER BY name"
            );
			while ($row = db_fetch_assoc($result)) {
				$city = strtolower(str_replace(' ', '', $row['name']));
				$args["village-$city"] = sprintf('%s %s', $row['type'], $row['name']);
			}
		break;

		case 'blockcommentarea':
			$result = db_query(
                "SELECT name
				FROM $villagesTable
				WHERE chat = 1"
            );
			while ($row = db_fetch_assoc($result)) {
				$city = strtolower(str_replace(' ', '', $row['name']));
				if ("village-$city" == $args['section']) {
					debug("Section: {$args['section']}<br />Village: {$city}<br />Chat disabled.");
					$args['block'] = 'yes';
				}
			}
		break;

		// case 'pvpstart':
		// 	$row = getVillageData($session['user']['location']);
		// 	if( isset($row['text']['pvpstart']) && !empty($row['text']['pvpstart']) )
		// 	{
		// 		$args['atkmsg'] = $row['text']['pvpstart'];
		// 		$args['schemas']['atkmsg'] = 'module-villageCreator';
		// 	}
		// break;

		// case 'pvpwin':
		// 	if( $args['handled'] != TRUE )
		// 	{
		// 		$row = getVillageData($session['user']['location']);
		// 		if( isset($row['text']['pvpwin']) && !empty($row['text']['pvpwin']) )
		// 		{
		// 			$args['handled'] = TRUE;
		// 			addnews(
        //                 $row['text']['pvpwin'],
        //                 [$session['user']['name'], $args['badguy']['creaturename'], $args['badguy']['location']]
        //             );
		// 		}
		// 	}
		// break;

		// case 'pvploss':
		// 	if( $args['handled'] != TRUE )
		// 	{
		// 		$row = getVillageData($session['user']['location']);
		// 		if( isset($row['text']['pvploss']) && !empty($row['text']['pvploss']) )
		// 		{
		// 			$args['handled'] = TRUE;
		// 			addnews($row['text']['pvploss'], $session['user']['name'], $args['badguy']['creaturename'], $args['badguy']['location'], $args['taunt']);
		// 		}
		// 	}
		// break;

		case 'player-login':
			// If you have more than one inn then you'll find yourself always stepping out into the main city
			// no matter where the inn was that you logged out in. This tries to fix that.
			if ($session['user']['location'] == getsetting('innname', LOCATION_INN)) {
				$session['user']['location'] = get_module_pref(
                    'inn_at',
                    'villageCreator',
                    $session['user']['acctid']
                );
			}
		break;

		case 'innrooms':
			// See comments above.
			set_module_pref('inn_at',$session['user']['location']);
			debug('Location saved as: '.$session['user']['location']);
		break;

		case 'showformextensions':
			$args['citytextarea'] = 'villageCreator_textarea'; // <-- Name of the function.
			$args['citylocation'] = 'villageCreator_location';
		break;
	}

	return $args;
}

function villageCreator_run()
{
	global $session;

	page_header('City Creator');

	$op = httpget('op');
	$sop = httpget('sop');
	$id = httpget('id');

	$from = 'runmodule.php?module=villageCreator';

	include("modules/VillageCreator/run/case_$op.php");

	if( $session['user']['superuser'] & SU_DEVELOPER )
	{
		addnav('Developer');
		addnav('Refresh',$from.'&op='.$op.'&id='.$id);
		// addnav('Newday','newday.php');
	}

	require_once('lib/superusernav.php');
	superusernav();

	page_footer();
}

function villageCreator_textarea($name, $val, $info)
{
	// The LoGD textarea code replaces `n with \n which is no good.
	$cols = 0;
	if( isset($info[2]) ) $cols = $info[2];
	if( !$cols ) $cols = 70;
	rawoutput("<script type=\"text/javascript\">function increase(target, value){  if (target.rows + value > 3 && target.rows + value < 50) target.rows = target.rows + value;}</script>");
	rawoutput("<textarea id='textarea$name' class='input' name='$name' cols='$cols' rows='5'>".htmlentities($val, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea>");
	rawoutput("<input type='button' onClick=\"increase(textarea$name,1);\" value='+' accesskey='+'><input type='button' onClick=\"increase(textarea$name,-1);\" value='-' accesskey='-'>");
}

function villageCreator_location($name, $val, $info)
{
    $villagesTable = db_prefix('villages'); 
	$inactive = translate_inline(' (Inactive)');
	$vloc = array();
	$vname = getsetting('villagename', LOCATION_FIELDS);
	$vloc[$vname] = 'village';
	$vloc['all'] = 1;
	$vloc = modulehook('validlocation', $vloc);
	$sql = "SELECT name
			FROM $villagesTable
			WHERE active = 0";
	$result = db_query($sql);
	$inactive_array = array();
	while( $row = db_fetch_assoc($result) )
	{
		$vloc[$row['name']] = 'village-'.strtolower(str_replace(' ','',$row['name']));
		$inactive_array[$row['name']] = 1;
	}
	unset($vloc['all']);
	reset($vloc);
	rawoutput("<select name='$name'>");
	foreach ($vloc as $loc => $val2 ) {
		rawoutput("<option value='$loc'".($loc == $val?' selected':'').">".htmlentities($loc.(isset($inactive_array[$loc])?$inactive:''), ENT_COMPAT, getsetting('charset', 'ISO-8859-1'))."</option>");
	}
	rawoutput('</select>');
}
?>