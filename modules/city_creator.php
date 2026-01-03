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
function city_creator_getmoduleinfo()
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
			"cities"=>"1.2|Eric Stevens`2, modified by `@MarcTheSlayer",
		),
		"prefs"=>array(
			"cityinn"=>"City name of the inn player last entered:,text",
		)
	);
	return $info;
}

function city_creator_install()
{
	require_once('modules/city_creator/city_creator_install.php');
	return TRUE;
}

function city_creator_uninstall()
{
	global $session;
	output("`n`c`b`Q'city_creator' Module Uninstalled`0`b`c");

	$city = getsetting('villagename', LOCATION_FIELDS);
	db_query("UPDATE  " . db_prefix('accounts') . " SET location = '$city' WHERE location != '$city'");
	$session['user']['location'] = $city;
	
	$sql = "SELECT cityid, cityname
			FROM " . db_prefix('cities');
	$result = db_query($sql);
	while( $row = db_fetch_assoc($result) )
	{
		modulehook('cityinvalidatecache',array('cityid'=>$row['cityid'],'cityname'=>$row['cityname']));
	}

	db_query("DROP TABLE IF EXISTS " . db_prefix('cities'));
	return TRUE;
}

$citycreator_citydata = array();

function city_creator_getcity($city = FALSE)
{
	global $citycreator_citydata;

	if( isset($citycreator_citydata['cityname']) && ($citycreator_citydata['cityname'] == $city || $citycreator_citydata['cityid'] == $city) )
	{
		return $citycreator_citydata;
	}

	$where = FALSE;
	if( is_numeric($city) && $city > 0 ) $where = "cityid = '$city'";
	elseif( is_string($city) ) $where = "cityname = '$city' LIMIT 1";
	if( $where )
	{
		$sql = "SELECT *
				FROM " . db_prefix('cities') . "
				WHERE $where";
		$result = db_query_cached($sql,'city_cityid-'.$city,86400);
		$citycreator_citydata = db_fetch_assoc($result);
	}
	return $citycreator_citydata;
}

function city_creator_dohook($hookname,$args)
{
	global $session;

	switch( $hookname )
	{
		case 'header-superuser':
			if( $session['user']['superuser'] & SU_EDIT_USERS )
			{
				addnav('Actions');
				addnav('Creators');
				addnav('City Creator','runmodule.php?module=city_creator');
				addnav('Editors');
			}
		break;

		case 'changesetting':
			if( $args['setting'] == 'villagename' )
			{
				db_query("UPDATE " . db_prefix('cities') . " SET cityname = '{$args['new']}' WHERE cityname = '{$args['old']}'");
				if( $session['user']['location'] == $args['old'] ) $session['user']['location'] = $args['new'];
			}
			if( $args['module'] == 'city_creator' )
			{
				if( $args['setting'] == 'transfer' && $args['new'] == 1 )
				{
					output('`n`Q`bTransfering....`b`n`n');
	
					$sql = "SELECT cityid, cityname, module
							FROM " . db_prefix('cityprefs') . "
							ORDER BY cityid";
					$result = db_query($sql);
					$author_array = array('none'=>'Eric Stevens','raceangel'=>'Jigain To`lerean','racedarkelf'=>'Kevin Hatfield','racearchangel_city'=>'RPGSL','racearchdemon_city'=>'RPGSL','racedraconis'=>'T. J. Brumfield','villagetokyo'=>'Haku','racezombie'=>'Dan Hall','racevulcan'=>'Lewis Little','racevamp'=>'Chris Vorndran','racevampire'=>'`4Thanatos','raceurgal'=>'Jonathan Newton','racetroll'=>'Eric Stevens','racespecialtyreptile'=>'`@CortalUX','racespecialtylion'=>'Peter Corcoran','raceSchwertjaeger'=>'Haku','racesaur'=>'anpera','racerobot'=>'Chris Thomas','racepirate'=>'John McNally','racepaladin'=>'Eternal','raceninja'=>'Gordon McCallum','racemutant'=>'Dan Hall','racemorph'=>'`QBasilius `qSauter','racemidget'=>'Dan Hall','racemermaid'=>'Adam Churchill','racelupe'=>'Rowne Mastaile','racelilty'=>'`QBasilius `qSauter','racekronos'=>'Jigain To`lerean','raceklingon'=>'Daniel Cannon','racekittymorph'=>'Dan Hall','racejoker'=>'Dan Hall','racehuman'=>'Eric Stevens','racehalf'=>'Jonathan Newton','racegreedy_barbarian'=>'Magic','racegoth'=>'Justin Holland','racegefallenerEngel'=>'Haku','racegeek'=>'Gordon McCallum','racefiend'=>'Jonathan Newton','raceferengi'=>'Daniel Cannon','raceelf'=>'Eric Stevens','raceelemental'=>'shadowblack','racedwarf'=>'Eric Stevens','racederyni'=>'Jonathan Newton','racedarkfelyne'=>'`!Akutozo','racecelestial'=>'Jonathan Newton','racecanadian'=>'Soj Services','racebyrdc'=>'Enhas','racebunny'=>'Billie Kennedy','racebirdy'=>'Victor Clarke','newbieisland'=>'Eric Stevens','icetown'=>'Shannon Brown','ghosttown'=>'Shannon Brown','eastertown'=>'`@CortalUX','citygeneric3'=>'Billie Kennedy','citygeneric2'=>'Billie Kennedy','citygeneric1'=>'Billie Kennedy','cityamwayr'=>'Billie Kennedy','racetitan'=>'T. J. Brumfield','racehorse'=>'Rowne Mastaile','racegnome'=>'T. J. Brumfield');
					while( $row = db_fetch_assoc($result) )
					{
						$author = ( isset($author_array[$row['module']]) ) ? $author_array[$row['module']] : '';
						db_query("INSERT INTO " . db_prefix('cities') . " (`cityid`,`cityname`,`cityauthor`,`citytype`,`cityactive`,`module`) VALUES ('{$row['cityid']}','{$row['cityname']}','$author','Village',1,'cityprefs')");
						if( db_affected_rows() > 0 ) output('`2City ID: `3%s `2Cityname: `3%s `2Module: `3%s `2Author: `3%s`n`n', $row['cityid'], $row['cityname'], $row['module'], $author);
						else output('`$Error: Couldn\'t insert city "`6%s`$" into the table. Are you sure no cities existed before doing this?', $row['cityname']);
					}

					output('`2The cityprefs table data (cityids and citynames) have been copied into the city creator table.`0`n`n');

					set_module_setting('transfer',2);
				}
			}
		break;

		case 'cityinvalidatecache':
			invalidatedatacache('city_cityid-'.$args['cityid']);
			invalidatedatacache('city_cityid-'.$args['cityname']);
			invalidatedatacache('city_locations');
			invalidatedatacache('city_travel');
		break;

		case 'everyhit-loggedin':
			global $SCRIPT_NAME;
			$script = substr($SCRIPT_NAME,0,strrpos($SCRIPT_NAME,"."));
			if( $script != 'village' ) break;
			// Block/unblock modules
			$row = city_creator_getcity($session['user']['location']);
			$row['cityblockmods'] = @unserialize($row['cityblockmods']);
			if( !is_array($row['cityblockmods']) ) break;
			if( isset($row['cityblockmods']['all']) && $row['cityblockmods']['all'] == 1 )
			{	// Block all modules.
				blockmodule(TRUE);
				debug('Blocking ALL modules!');
				unset($row['cityblockmods']['all']);
				if( isset($row['cityblockmods']['other']) && !empty($row['cityblockmods']['other']) )
				{	// Now unblock the modules you want.
					unblockmodule('city_creator'); // Better unblock this. =)
					$others = explode(',', $row['cityblockmods']['other']);
					foreach( $others as $module )
					{
						unblockmodule($module);
						debug('Unblocking module: '.$module);
					}
				}
			}
			else
			{	// Don't mass block any modules, only the ones you want.
				unset($row['cityblocks']['all']);
				if( isset($row['cityblocks']['other']) && !empty($row['cityblocks']['other']) )
				{
					$others = explode(',', $row['cityblocks']['other']);
					foreach( $others as $module )
					{
						blockmodule($module);
						debug('Blocking module: '.$module);
					}
				}
			}
		break;

		case 'header-village':
			$row = city_creator_getcity($session['user']['location']);
			// Block navs.
			$row['cityblocknavs'] = @unserialize($row['cityblocknavs']);
			if( is_array($row['cityblocknavs']) )
			{
				if( isset($row['cityblocknavs']['other']) && !empty($row['cityblocknavs']['other']) )
				{	// Block other navs first.
					$othernavs = explode(',', $row['cityblocknavs']['other']);
					$count = count($othernavs);
					if( $count > 0 )
					{
						for( $i=0; $i<$count; $i++ )
						{
							if( $othernavs[$i+1] == 'true' )
							{
								blocknav($othernavs[$i],TRUE);
								debug('Blocking partial nav: '.$othernavs[$i]);
								$i++;
								continue;
							}
							blocknav($othernavs[$i]);
							debug('Blocking nav: '.$othernavs[$i]);
						}
					}
				}
				unset($row['cityblocknavs']['other']);
				// Now block original village navs.
				foreach( $row['cityblocknavs'] as $nav => $value )
				{
					if( $value == 1 )
					{
						blocknav($nav.'.php');
						debug('Blocking core nav: '.$nav.'.php');
					}
				}
			}
		break;

		case 'villagetext':
			$row = city_creator_getcity($session['user']['location']);
			// Change to custom text.
			if( $row['citytext'] != '' )
			{
                debug($row['citytext']);
				$row['citytext'] = @unserialize($row['citytext']);
				if( !is_array($row['citytext']) ) break;

				$new = 0;
				if( is_module_active('cities') )
				{	// If 'cities' module is installed then get newest player in this city.
					$new = get_module_setting("newest-{$row['cityname']}",'cities');
					if( $new != 0 )
					{
						$sql = "SELECT name
								FROM " . db_prefix('accounts') . "
								WHERE acctid = '$new'";
						$result = db_query_cached($sql, "newest-{$row['cityname']}");
						$row2 = db_fetch_assoc($result);
						$args['newestplayer'] = $row2['name'];
						$args['newestid'] = $new;
					}
					else
					{
						$args['newestplayer'] = $new;
						$args['newestid'] = '';
					}
				}
				if( $new == $session['user']['acctid'] )
				{
					$row['citytext']['newest'] = $row['citytext']['newest1'];
				}
				elseif( $new != $session['user']['acctid'] )
				{
					$row['citytext']['newest'] = $row['citytext']['newest2'];
				}

				$names = array('title','text','clock','newest','talk','sayline','section','gatenav','fightnav','marketnav','tavernnav','infonav','othernav','innname','stablename','mercenarycamp','armorshop','weaponshop');

				foreach( $names as $name )
				{	// If the field has text then process it.
					if( isset($row['citytext'][$name]) && !empty($row['citytext'][$name]) )
					{
						$args[$name] = stripslashes($row['citytext'][$name]);
						$args['schemas'][$name] = 'module-'.$row['module'];
					}
				}
				// Change the chat section to that of the cityname.
				$args['commentary']['section'] = 'village-' . strtolower(str_replace(' ','',$row['cityname']));
				$args['schemas']['section'] = 'module-'.$row['module'];
			}
		break;

		case 'stabletext':
			$row = city_creator_getcity($session['user']['location']);
			// Change to custom text.
			if( $row['stabletext'] != '' )
			{
				$row['stabletext'] = @unserialize($row['stabletext']);
				if( !is_array($row['stabletext']) ) break;

				$names = array('title','desc','nosuchbeast','finebeast','toolittle','replacemount','newmount','nofeed','nothungry','halfhungry','hungry','mountfull','nofeedgold','confirmsale','mountsold','offer','lass','lad');

				$search = array('%N','%L');
				$replace = array($session['user']['name'],($session['user']['sex']?$row['stabletext']['stablass']:$row['stabletext']['stablad']));

				foreach( $names as $name )
				{	// If the field has text then process it.
					if( isset($row['stabletext'][$name]) && !empty($row['stabletext'][$name]) )
					{
						if( $name == 'desc' ) $row['stabletext'][$name] = str_replace($search, $replace, $row['stabletext'][$name]);
						if( $name == 'finebeast' )
						{	// Put each line into an array.
							$row['stabletext'][$name] = explode("\r\n", $row['stabletext'][$name]);
							foreach( $row['stabletext'][$name] as $key => $value ) $row['stabletext'][$name][$key] = stripslashes($value);
						}
						$args[$name] = ( is_array($row['stabletext'][$name]) ) ? $row['stabletext'][$name] : stripslashes($row['stabletext'][$name]);
						$args['schemas'][$name] = 'module-'.$row['module'];
					}
				}
			}
		break;

		case 'armortext':
			$row = city_creator_getcity($session['user']['location']);
			// Change to custom text.
			if( $row['armortext'] != '' )
			{
				$row['armortext'] = @unserialize($row['armortext']);
				if( !is_array($row['armortext']) ) break;

				$names = array('title','desc','tradein','nosuchweapon','tryagain','notenoughgold','payarmor');

				$search = array('%N','%A','%T');
				$replace = array($session['user']['name'],$session['user']['armor'],round(($session['user']['armorvalue']*.75),0));

				foreach( $names as $name )
				{	// If the field has text then process it.
					if( isset($row['armortext'][$name]) && !empty($row['armortext'][$name]) )
					{
						if( $name == 'desc' || $name == 'tradein' ) $row['armortext'][$name] = str_replace($search, $replace, $row['armortext'][$name]);
						$args[$name] = stripslashes($row['armortext'][$name]);
						$args['schemas'][$name] = 'module-'.$row['module'];
					}
				}
			}
		break;

		case 'weaponstext':
			$row = city_creator_getcity($session['user']['location']);
			// Change to custom text.
			if( $row['weaponstext'] != '' )
			{
				$row['weaponstext'] = @unserialize($row['weaponstext']);
				if( !is_array($row['weaponstext']) ) break;

				$names = array('title','desc','tradein','nosuchweapon','tryagain','notenoughgold','payweapon');

				$search = array('%N','%W','%T');
				$replace = array($session['user']['name'],$session['user']['weapon'],round(($session['user']['weaponvalue']*.75),0));

				foreach( $names as $name )
				{	// If the field has text then process it.
					if( isset($row['weaponstext'][$name]) && !empty($row['weaponstext'][$name]) )
					{
						if( $name == 'desc' || $name == 'tradein' ) $row['weaponstext'][$name] = str_replace($search, $replace, $row['weaponstext'][$name]);
						$args[$name] = stripslashes($row['weaponstext'][$name]);
						$args['schemas'][$name] = 'module-'.$row['module'];
					}
				}
			}
		break;

		case 'mercenarycamptext':
			$row = city_creator_getcity($session['user']['location']);
			// Change to custom text.
			if( $row['mercenarycamptext'] != '' )
			{
				$row['mercenarycamptext'] = @unserialize($row['mercenarycamptext']);
				if( !is_array($row['mercenarycamptext']) ) break;

				$names = array('title','desc','buynav','healnav','healtext','healnotenough','healpaid','toomanycompanions','manycompanions','onecompanion','nocompanions');
				
				foreach( $names as $name )
				{	// If the field has text then process it.
					if( isset($row['mercenarycamptext'][$name]) && !empty($row['mercenarycamptext'][$name]) )
					{
						$args[$name] = stripslashes($row['mercenarycamptext'][$name]);
						$args['schemas'][$name] = 'module-'.$row['module'];
					}
				}
			}
		break;

		case 'travel':
			$city = city_creator_getcity($session['user']['location']);

			addnav('Safer Travel');
			addnav('More Dangerous Travel');
			addnav('Superuser Travel');
			tlschema('module-cities');
			
			$sql = "SELECT cityid, cityname, citytravel
					FROM " . db_prefix('cities') . "
					WHERE cityactive = 1";
			$result = db_query_cached($sql,'city_travel',86400);
			while( $row = db_fetch_assoc($result) )
			{
				if( $session['user']['location'] == $row['cityname'] ) continue; // Allows us to use the 'city_travel' cache.

				$cityname = translate_inline($row['cityname']);
				$hotkey = substr($cityname, 0, 1);
				// Modulehook to see if any modules object, or give access on rare occasions.
				$prereq = modulehook('cityprerequisite',array('currentcityid'=>$city['cityid'],'currentcityname'=>$city['cityname'],'cityid'=>$row['cityid'],'cityname'=>$row['cityname'],'citytravel'=>$row['citytravel'],'blocked'=>0));

				if( $prereq['blocked'] == 0 )
				{
					if( $prereq['citytravel'] == 0 )
					{
						addnav('Safer Travel');
						addnav(array("%s?Go to %s", $hotkey, $cityname),"runmodule.php?module=cities&op=travel&city=".urlencode($row['cityname']));
					}
					elseif( $prereq['citytravel'] == 1 )
					{
						addnav('More Dangerous Travel');
						addnav(array("%s?Go to %s", $hotkey, $cityname),"runmodule.php?module=cities&op=travel&city=".urlencode($row['cityname'])."&d=1");
					}
					else
					{
						debug("Travel to $cityname is Off.");
					}
				}
				if( $session['user']['superuser'] & SU_DEVELOPER )
				{
					addnav('Superuser Travel');
					addnav(array("%s?Go to %s", $hotkey, $cityname),"runmodule.php?module=cities&op=travel&city=".urlencode($row['cityname'])."&su=1");
				}
			}
			tlschema();
		break;

		case 'validlocation':
			$sql = "SELECT cityid, cityname, citytravel
					FROM " . db_prefix('cities') . "
					WHERE cityactive = 1";
			$result = db_query_cached($sql,'city_travel',86400);
			while( $row = db_fetch_assoc($result) )
			{
				if( isset($args['all']) && $args['all'] == 1 )
				{
					$args[$row['cityname']] = 'village-'.strtolower(str_replace(' ','',$row['cityname']));
				}
				else
				{
					// Never block the default village, no point as you'll get sent there anyway. :)
					$prereq = modulehook('cityvalidlocations',array('cityid'=>$row['cityid'],'cityname'=>$row['cityname'],'citytravel'=>$row['citytravel'],'blocked'=>0));
					if( $prereq['blocked'] == 1 ) continue;
					$args[$row['cityname']] = 'village-'.strtolower(str_replace(' ','',$row['cityname']));
				}
			}
		break;

		case 'validforestloc':
			$sql = "SELECT cityname, citytype, cityblocknavs
					FROM " . db_prefix('cities') . "
					WHERE cityactive = 1";
			$result = db_query_cached($sql,'city_locations',86400);
			while( $row = db_fetch_assoc($result) )
			{	// If the forest link has been blocked then there is no forest in this city.
				$row['cityblocknavs'] = @unserialize(stripslashes($row['cityblocknavs']));
				if( !is_array($row['cityblocknavs']) ) $row['cityblocknavs'] = array('all'=>0,'forest.php'=>0);
				if (
                    (isset($row['cityblocknavs']['all']) && $row['cityblocknavs']['all'] != 1) ||
                    (isset($row['cityblocknavs']['fores.php']) && $row['cityblocknavs']['forest.php'] != 1 )
                )
				{
					$args[$row['cityname']] = sprintf_translate("The %s of %s", translate_inline($row['citytype']), translate_inline($row['cityname']));
				}
			}
		break;

		case 'stablelocs':
			tlschema('mounts');
			$sql = "SELECT cityname, citytype, cityblocknavs
					FROM " . db_prefix('cities') . "
					WHERE cityactive = 1";
			$result = db_query_cached($sql,'city_locations',86400);
			while( $row = db_fetch_assoc($result) )
			{	// If the stables link has been blocked then there are no stables in this city.
				$row['cityblocknavs'] = @unserialize(stripslashes($row['cityblocknavs']));
				if( !is_array($row['cityblocknavs']) ) $row['cityblocknavs'] = array('all'=>0,'stables.php'=>0);
				if( $row['cityblocknavs']['all'] != 1 || $row['cityblocknavs']['stables.php'] != 1 )
				{
					$args[$row['cityname']] = sprintf_translate("The %s of %s", translate_inline($row['citytype']), translate_inline($row['cityname']));
				}
			}
			tlschema();
		break;

		case 'camplocs':
			$sql = "SELECT cityname, citytype, cityblocknavs
					FROM " . db_prefix('cities') . "
					WHERE cityactive = 1";
			$result = db_query_cached($sql,'city_locations',86400);
			while( $row = db_fetch_assoc($result) )
			{	// If the camp link has been blocked then there is no camp in this city.
				$row['cityblocknavs'] = @unserialize(stripslashes($row['cityblocknavs']));
				if( !is_array($row['cityblocknavs']) ) $row['cityblocknavs'] = array('all'=>0,'mercenarycamp.php'=>0);
				if( $row['cityblocknavs']['all'] != 1 || $row['cityblocknavs']['mercenarycamp.php'] != 1 )
				{
					$args[$row['cityname']] = sprintf_translate("The %s of %s", translate_inline($row['citytype']), translate_inline($row['cityname']));
				}
			}
		break;

		case 'moderate':
			tlschema('commentary');
			$sql = "SELECT cityname, citytype
					FROM " . db_prefix('cities') . "
					WHERE cityactive = 1
					ORDER BY cityname";
			$result = db_query($sql);
			while( $row = db_fetch_assoc($result) )
			{
				$city = strtolower(str_replace(' ','',$row['cityname']));
				$args["village-$city"] = sprintf_translate('%s %s', translate_inline($row['citytype'],'city_creator'), translate_inline($row['cityname'],'city_creator'));
			}
			tlschema();
		break;

		case 'blockcommentarea':
			$sql = "SELECT cityname
					FROM " . db_prefix('cities') . "
					WHERE citychat = 1";
			$result = db_query($sql);
			while( $row = db_fetch_assoc($result) )
			{
				$city = strtolower(str_replace(' ','',$row['cityname']));
				if( "village-$city" == $args['section'] )
				{
					debug('Section: '.$args['section'].'<br />Cityname: '.$city.'<br />Chat disabled.');
					$args['block'] = 'yes';
				}
			}
		break;

		case 'pvpstart':
			$row = city_creator_getcity($session['user']['location']);
			if( isset($row['citytext']['pvpstart']) && !empty($row['citytext']['pvpstart']) )
			{
				$args['atkmsg'] = $row['citytext']['pvpstart'];
				$args['schemas']['atkmsg'] = 'module-city_creator';
			}
		break;

		case 'pvpwin':
			if( $args['handled'] != TRUE )
			{
				$row = city_creator_getcity($session['user']['location']);
				if( isset($row['citytext']['pvpwin']) && !empty($row['citytext']['pvpwin']) )
				{
					$args['handled'] = TRUE;
					addnews($row['citytext']['pvpwin'], $session['user']['name'], $args['badguy']['creaturename'], $args['badguy']['location']);
				}
			}
		break;

		case 'pvploss':
			if( $args['handled'] != TRUE )
			{
				$row = city_creator_getcity($session['user']['location']);
				if( isset($row['citytext']['pvploss']) && !empty($row['citytext']['pvploss']) )
				{
					$args['handled'] = TRUE;
					addnews($row['citytext']['pvploss'], $session['user']['name'], $args['badguy']['creaturename'], $args['badguy']['location'], $args['taunt']);
				}
			}
		break;

		case 'player-login':
			// If you have more than one inn then you'll find yourself always stepping out into the main city
			// no matter where the inn was that you logged out in. This tries to fix that.
			if( $session['user']['location'] == getsetting('innname', LOCATION_INN) )
			{
				$session['user']['location'] = get_module_pref('cityinn','city_creator',$session['user']['acctid']);
			}
		break;

		case 'innrooms':
			// See comments above.
			set_module_pref('cityinn',$session['user']['location']);
			debug('Location saved as: '.$session['user']['location']);
		break;

		case 'showformextensions':
			$args['citytextarea'] = 'city_creator_textarea'; // <-- Name of the function.
			$args['citylocation'] = 'city_creator_location';
		break;
	}

	return $args;
}

function city_creator_run()
{
	global $session;

	page_header('City Creator');

	$op = httpget('op');
	$sop = httpget('sop');
	$cityid = httpget('cityid');

	$from = 'runmodule.php?module=city_creator';

	include("modules/city_creator/run/case_$op.php");

	if( $session['user']['superuser'] & SU_DEVELOPER )
	{
		addnav('Developer');
		addnav('Refresh',$from.'&op='.$op.'&cityid='.$cityid);
		addnav('Newday','newday.php');
	}

	require_once('lib/superusernav.php');
	superusernav();

	page_footer();
}

function city_creator_textarea($name, $val, $info)
{
	// The LoGD textarea code replaces `n with \n which is no good.
	$cols = 0;
	if( isset($info[2]) ) $cols = $info[2];
	if( !$cols ) $cols = 70;
	rawoutput("<script type=\"text/javascript\">function increase(target, value){  if (target.rows + value > 3 && target.rows + value < 50) target.rows = target.rows + value;}</script>");
	rawoutput("<textarea id='textarea$name' class='input' name='$name' cols='$cols' rows='5'>".htmlentities($val, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea>");
	rawoutput("<input type='button' onClick=\"increase(textarea$name,1);\" value='+' accesskey='+'><input type='button' onClick=\"increase(textarea$name,-1);\" value='-' accesskey='-'>");
}

function city_creator_location($name, $val, $info)
{
	$inactive = translate_inline(' (Inactive)');
	$vloc = array();
	$vname = getsetting('villagename', LOCATION_FIELDS);
	$vloc[$vname] = 'village';
	$vloc['all'] = 1;
	$vloc = modulehook('validlocation', $vloc);
	$sql = "SELECT cityname
			FROM " . db_prefix('cities') . "
			WHERE cityactive = 0";
	$result = db_query($sql);
	$inactive_array = array();
	while( $row = db_fetch_assoc($result) )
	{
		$vloc[$row['cityname']] = 'village-'.strtolower(str_replace(' ','',$row['cityname']));
		$inactive_array[$row['cityname']] = 1;
	}
	unset($vloc['all']);
	reset($vloc);
	rawoutput("<select name='$name'>");
	foreach( $vloc as $loc => $val2 )
	{
		rawoutput("<option value='$loc'".($loc == $val?' selected':'').">".htmlentities($loc.(isset($inactive_array[$loc])?$inactive:''), ENT_COMPAT, getsetting('charset', 'ISO-8859-1'))."</option>");
	}
	rawoutput('</select>');
}
?>