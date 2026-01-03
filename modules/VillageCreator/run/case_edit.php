<?php

include('modules/VillageCreator/city_creator_functions.php');

$cityId = (int) httpget('id');
$villagesTable = db_prefix('villages');

if( $cityId > 0 )
{
	//
	// Get city data and send it for checking.
	//
	$sql = "SELECT *
			FROM $villagesTable
			WHERE id = '$cityId'";
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	if( db_num_rows($result) <> 1 )
	{
		output('`n`$Error: That city was not found!`0`n`n');
		$row = city_creator_array_check();
	}
	else
	{
		$row['mods'] = is_serialized($row['block_mods']) ? @unserialize($row['block_mods']) : '';
		$row['navs'] = is_serialized($row['block_navs']) ? @unserialize($row['block_navs']) : '';
		$row['vill'] = is_serialized($row['text']) ? @unserialize($row['text']) : '';
		$row['stab'] = is_serialized($row['stable_text']) ? @unserialize($row['stable_text']) : '';
		$row['arm'] = is_serialized($row['armor_text']) ? @unserialize($row['armor_text']) : '';
		$row['weap'] = is_serialized($row['weapons_text']) ? @unserialize($row['weapons_text']) : '';
		$row['merc'] = is_serialized($row['mercenary_camp_text']) ? @unserialize($row['mercenary_camp_text']) : '';
		unset($row['block_mods'],$row['block_navs'],$row['text'],$row['stable_text'],$row['armor_text'],$row['weapon_text'],$row['mercenary_camp_text']);
		$row = city_creator_array_check($row);
	}
	$addnew = 'bool';
}
else
{
	$row = city_creator_array_check();
	$addnew = 'invisible';
}

// debug($row);
$form = array(
	'City Details,title',
		'addnew'=>'Add as new?,'.$addnew,
		'active'=>'City is Active?,bool',
		'id'=>'City ID:,hidden',
		'module'=>'Module:,viewonly',
		'author'=>' Author:,string,30',
		'name'=>'Name:,string,30',
		'`^No colours or any sort of tags in the city name.,note',
		'type'=>'Type (City&#44; Town&#44; village&#44; etc):,string,30',
		'`^No colours or any sort of tags in the type name.,note',
		'chat'=>'Chat disabled in this City?,bool',
		'travel'=>'Travel:,enum,0,Safe,1,Dangerous,2,Off',
		'`^Note: Set to Off to disable normal travel to this city.,note',
	'City Text,title',
		'`^The texts for this village should be edited using the `QTranslation ' .
		'Editor`^ (requires superuser access) or by manually editing the YAML ' .
		'file in: ' .
		'`&translations/en/modules/village_' . $row['sanitized_name'] . '.yml`^.`n`n' .
		'`QThe previous specific text sections have been removed in favor of the ' .
		'unified YAML configuration.,note',
	'Block Modules,title',
		'modsall'=>'Block all modules:,bool',
		'modsother'=>'List of modules to block or unblock:,textarearesizeable,30',
		'`^If you\'re blocking all modules then enter the module names above which you want to unblock. For example unblock the \'cities\' module if you want travel to work.`n`n' .
		'If you\'re *not* blocking all modules then enter the module names above which you do want to block.`n`n' .
		'`#Separate the names with a comma!,note',
	'Block Navs,title',
		'`^Select Yes to block the basic core nav links.,note',
		'navsforest'=>'forest.php,bool',
		'navspvp'=>'pvp.php,bool',
		'navsmercenarycamp'=>'mercenarycamp.php,bool',
		'navstrain'=>'train.php,bool',
		'navslodge'=>'lodge.php,bool',
		'navsweapons'=>'weapons.php,bool',
		'navsarmor'=>'armor.php,bool',
		'navsbank'=>'bank.php,bool',
		'navsgypsy'=>'gypsy.php,bool',
		'navsinn'=>'inn.php,bool',
		'navsstables'=>'stables.php,bool',
		'navsgardens'=>'gardens.php,bool',
		'navsrock'=>'rock.php,bool',
		'navsclan'=>'clan.php,bool',
		'navsnews'=>'news.php,bool',
		'navslist'=>'list.php,bool',
		'navshof'=>'hof.php,bool',
		'navsother'=>'List of navs to block:,textarearesizeable,30',
		'`^Any other nav links that you wish to block can be entered in the box above. To block partial urls place \'`itrue`i\' after it.`n`#Separate each url and \'`itrue`i\' with a comma!`n`n
		`&Examples:`n`7bananas.php`@&#44;`7oranges.php`@&#44;`7apples.php`nbananas.php&op=enter`@&#44;`7oranges.php`@&#44;`7true`@&#44;`7apples.php,note'
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
					AND objid = '$cityId'";
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
addnav('Add a City',$from.'&op=edit');
addnav('Main Page',$from);
