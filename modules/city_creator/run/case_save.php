<?php
	include_once('lib/gamelog.php');
	require_once('lib/sanitize.php');
    global $mysqli_resource;
	//
	// Save submitted mount data.
	//
	// These fields are the ones we want so there can be no mistake.
	$field_array = array('cityactive','cityid','cityauthor','cityname','citytype','citychat','citytravel','module');
	$field_array2 = array('villtitle','villtext','villclock','villnewest1','villnewest2','villtalk','villsayline','villgatenav','villfightnav','villmarketnav','villtavernnav','villinfonav','villothernav','villinnname','villstablename','villmercenarycamp','villarmorshop','villweaponshop','villpvpstart','villpvpwin','villpvploss');
	$field_array3 = array('stabtitle','stabdesc','stabnosuchbeast','stabfinebeast','stabtoolittle','stabreplacemount','stabnewmount','stabnofeed','stabnothungry','stabhalfhungry','stabhungry','stabmountfull','stabnofeedgold','stabconfirmsale','stabmountsold','staboffer','stablass','stablad');
	$field_array4 = array('armtitle','armdesc','armtradein','armnosuchweapon','armtryagain','armnotenoughgold','armpayarmor');
	$field_array5 = array('weaptitle','weapdesc','weaptradein','weapnosuchweapon','weaptryagain','weapnotenoughgold','weappayweapon');
	$field_array6 = array('merctitle','mercdesc','mercbuynav','merchealnav','merchealtext','merchealnotenough','merchealpaid','merctoomanycompanions','mercmanycompanions','merconecompanion','mercnocompanions');
	$field_array7 = array('modsall','modsother');
	$field_array8 = array('navsother','navsforest','navspvp','navsmercenarycamp','navstrain','navslodge','navsweapons','navsarmor','navsbank','navsgypsy','navsinn','navsstables','navsgardens','navsrock','navsclan','navsnews','navslist','navshof');
	// Arrays 2 to 8 are to be stored in the database as serialised arrays so we need to process each differently.
	$field2 = $field3 = $field4 = $field5 = $field6 = $field7 = $field8 = array();

	if( $_POST['addnew'] == 1 ) $_POST['cityid'] = 0;

	$_POST['cityauthor'] = ( $_POST['cityauthor'] != '' ) ? strip_tags($_POST['cityauthor']) : $session['user']['login'];

	if( $_POST['cityname'] == '' )
	{
		$_POST['cityname'] = getsetting('villagename', LOCATION_FIELDS); 
	}
	else
	{
		$_POST['cityname'] = full_sanitize(str_replace(array('"',"'"), '', strip_tags($_POST['cityname'])));
	}

	if( $_POST['citytype'] == '' )
	{	// Default type if none is entered.
		$_POST['citytype'] = 'City';
	}
	else
	{
		$_POST['citytype'] = full_sanitize(str_replace(array('"',"'"), '', strip_tags($_POST['citytype'])));
	}

	if( isset($_POST['module']) && $_POST['module'] == '' ) $_POST['module'] = 'city_creator';

	$post = httpallpost();
	$cityid = httppost('cityid');

	$sql = "SELECT modulename
			FROM " . db_prefix('modules') . "
			WHERE infokeys
			LIKE '%|prefs-city|%'
			ORDER BY formalname";
	$result = db_query($sql);
	$module_array = array();
	while( $row = db_fetch_assoc($result) )
	{
		$module_array[] = $row['modulename'];
	}

	if( $cityid > 0 )
	{
		//
		// An existing city.
		//
		$oldvalues = @unserialize(stripslashes($post['oldvalues']));
		unset($post['oldvalues'], $post['cityid']);

		//
		// Deal with the city table data first.
		//
		$sql = '';
		reset($post);
		// while( list($key,$val) = each($post) )
        foreach ($post as $key => $val)
		{
			if( in_array($key, $field_array) )
			{
				if( $key == 'cityname' && $val != $oldvalues[$key] ) db_query("UPDATE " . db_prefix('accounts') . " SET location = '$val' WHERE location = '{$oldvalues[$key]}'");
				$sql .= "`$key` = '".mysqli_real_escape_string($mysqli_resource, $val)."', ";
				unset($post[$key], $oldvalues[$key]);
			}
			elseif( in_array($key, $field_array2) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove vill
					$field2[$keyname] = $val;
				}
				unset($post[$key], $oldvalues[$key]);
			}
			elseif( in_array($key, $field_array3) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove stab
					$field3[$keyname] = $val;
				}
				unset($post[$key], $oldvalues[$key]);
			}
			elseif( in_array($key, $field_array4) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,3,strlen($key)); // Remove arm
					$field4[$keyname] = $val;
				}
				unset($post[$key], $oldvalues[$key]);
			}
			elseif( in_array($key, $field_array5) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove weap
					$field5[$keyname] = $val;
				}
				unset($post[$key], $oldvalues[$key]);
			}
			elseif( in_array($key, $field_array6) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove merc
					$field6[$keyname] = $val;
				}
				unset($post[$key], $oldvalues[$key]);
			}
			elseif( in_array($key, $field_array7) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove mods
					$field7[$keyname] = $val;
				}
				unset($post[$key], $oldvalues[$key]);
			}
			elseif( in_array($key, $field_array8) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove navs
					$field8[$keyname] = $val;
				}
				unset($post[$key], $oldvalues[$key]);
			}
		}

		$sql .= ( count($field2) > 0 ) ? "citytext = '".mysqli_real_escape_string($mysqli_resource, serialize($field2))."', " : "citytext = '', ";
		$sql .= ( count($field3) > 0 ) ? "stabletext = '".mysqli_real_escape_string($mysqli_resource, serialize($field3))."', " : "stabletext = '', ";
		$sql .= ( count($field4) > 0 ) ? "armortext = '".mysqli_real_escape_string($mysqli_resource, serialize($field4))."', " : "armortext = '', ";
		$sql .= ( count($field5) > 0 ) ? "weaponstext = '".mysqli_real_escape_string($mysqli_resource, serialize($field5))."', " : "weaponstext = '', ";
		$sql .= ( count($field6) > 0 ) ? "mercenarycamptext = '".mysqli_real_escape_string($mysqli_resource, serialize($field6))."', " : "mercenarycamptext = '', ";
		$sql .= ( count($field7) > 0 ) ? "cityblockmods = '".mysqli_real_escape_string($mysqli_resource, serialize($field7))."', " : "cityblockmods = '', ";
		$sql .= ( count($field8) > 0 ) ? "cityblocknavs = '".mysqli_real_escape_string($mysqli_resource, serialize($field8))."' " : "cityblocknavs = '' ";

		db_query("UPDATE " . db_prefix('cities') . " SET " . $sql . " WHERE cityid = '$cityid'");
		if( db_affected_rows() > 0 )
		{
			output('`@City was successfully updated!`n');
		}
		else
		{
			output('`$City was not updated as nothing was changed!`n');
		}

		//
		// Now deal with the different module data.
		//
		foreach( $module_array as $mkey => $modulename )
		{
			$len = strlen($modulename);
			foreach( $post as $key => $val )
			{
				if( substr($key,0,$len) == $modulename )
				{
					if( isset($oldvalues[$key]) && $oldvalues[$key] != $val )
					{	// Only take data that has been changed.
						$keyname = substr($key,$len+1,strlen($key));
						set_module_objpref('city', $cityid, $keyname, $val, $modulename);
						output('`7Module: `&%s `7Setting: `&%s `7ObjectID: `&%s `7Value changed from "`&%s`7" to "`&%s`7"`n', $modulename, $keyname, $cityid, $oldvalues[$key], $val);
						gamelog("`7Module: `&$modulename `7Setting: `&$keyname `7ObjectID: `&$cityid `7Value changed from '`&{$oldvalues[$key]}`7' to '`&$val`7'`0","cities");
						unset($post[$key], $oldvalues[$key]);
					}
				}
			}
		}
	}
	else
	{
		//
		// A new city has been submitted.
		//
		unset($post['oldvalues'], $post['cityid'], $post['addnew']);

		//
		// Deal with the city table data first.
		//
		$cols = array();
		$vals = array();

		reset($post);
		// while( list($key,$val) = each($post) )
        foreach ($post as $key => $val)
		{
			if( in_array($key, $field_array) )
			{
				array_push($cols,"`$key`");
				array_push($vals,mysqli_real_escape_string($mysqli_resource, $val));
				unset($post[$key]);
			}
			elseif( in_array($key, $field_array2) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove vill
					$field2[$keyname] = $val;
				}
				unset($post[$key]);
			}
			elseif( in_array($key, $field_array3) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove stab
					$field3[$keyname] = $val;
				}
				unset($post[$key]);
			}
			elseif( in_array($key, $field_array4) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,3,strlen($key)); // Remove arm
					$field4[$keyname] = $val;
				}
				unset($post[$key]);
			}
			elseif( in_array($key, $field_array5) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove weap
					$field5[$keyname] = $val;
				}
				unset($post[$key]);
			}
			elseif( in_array($key, $field_array6) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove merc
					$field6[$keyname] = $val;
				}
				unset($post[$key]);
			}
			elseif( in_array($key, $field_array7) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove mods
					$field7[$keyname] = $val;
				}
				unset($post[$key]);
			}
			elseif( in_array($key, $field_array8) )
			{
				if( !empty($val) )
				{
					$keyname = substr($key,4,strlen($key)); // Remove navs
					$field8[$keyname] = $val;
				}
				unset($post[$key]);
			}

		}
		if( count($field2) > 0 )
		{
			array_push($cols, 'citytext');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field2)));
		}
		if( count($field3) > 0 )
		{
			array_push($cols, 'stabletext');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field3)));
		}
		if( count($field4) > 0 )
		{
			array_push($cols, 'armortext');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field4)));
		}
		if( count($field5) > 0 )
		{
			array_push($cols, 'weaponstext');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field5)));
		}
		if( count($field6) > 0 )
		{
			array_push($cols, 'mercenarycamptext');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field6)));
		}
		if( count($field7) > 0 )
		{
			array_push($cols, 'cityblockmods');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field7)));
		}
		if( count($field8) > 0 )
		{
			array_push($cols, 'cityblocknavs');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field8)));
		}

		db_query("INSERT INTO " . db_prefix('cities') . " (" . join(",",$cols) . ") VALUES (\"" . join("\",\"",$vals) . "\")");
		$cityid = db_insert_id();
		if( db_affected_rows() > 0 )
		{
			output('`@City was successfully saved!`n');
		}
		else
		{
			output('`$City was NOT saved!`n');
		}

		//
		// Now deal with the different module data.
		//
		foreach( $module_array as $mkey => $modulename )
		{
			$len = strlen($modulename);
			foreach( $post as $key => $val )
			{
				if( substr($key,0,$len) == $modulename )
				{
					if( $val != '' )
					{
						$len2 = strlen($key);
						$keyname = substr($key,$len+1,$len2);
						set_module_objpref('city', $cityid, $keyname, $val, $modulename);
						output('`7Module: `&%s `7Setting: `&%s `7ObjectID: `&%s `7Value: `&%s`7.`n', $modulename, $keyname, $cityid, $val);
						gamelog("`7Module: `&$modulename `7Setting: `&$keyname `7ObjectID: `&$cityid `7Value: `&$val`7.`0","cities");
						unset($post[$key]);
					}
				}
			}
		}
	}

	// Call this hook to invalidate any cache files.
	modulehook('cityinvalidatecache',array('cityid'=>$cityid,'cityname'=>$_POST['cityname']));

	addnav('Editor');
	addnav('Re-Edit City',$from.'&op=edit&cityid='.$cityid);
	addnav('Add a City',$from.'&op=edit');
	addnav('Main Page',$from);
?>