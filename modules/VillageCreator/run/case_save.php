<?php
	include_once('lib/gamelog.php');
	use Symfony\Component\Yaml\Yaml;
	require_once('lib/sanitize.php');
    global $mysqli_resource;
    $villagesTable = db_prefix('villages');
	//
	// Save submitted mount data.
	//
	// These fields are the ones we want so there can be no mistake.
	$field_array = array('active','id','author','name','type','chat','travel','module');
	$field_array2 = array('villtitle','villdescription','villclock','villnewest1','villnewest2','villtalk','villsayline','villgatenav','villfightnav','villmarketnav','villtavernnav','villinfonav','villothernav','villinnname','villstablename','villmercenarycamp','villarmorshop','villweaponshop','villpvpstart','villpvpwin','villpvploss');
	$field_array3 = array('stabtitle','stabdesc','stabnosuchbeast','stabfinebeast','stabtoolittle','stabreplacemount','stabnewmount','stabnofeed','stabnothungry','stabhalfhungry','stabhungry','stabmountfull','stabnofeedgold','stabconfirmsale','stabmountsold','staboffer','stablass','stablad');
	$field_array4 = array('armtitle','armdesc','armtradein','armnosuchweapon','armtryagain','armnotenoughgold','armpayarmor');
	$field_array5 = array('weaptitle','weapdesc','weaptradein','weapnosuchweapon','weaptryagain','weapnotenoughgold','weappayweapon');
	$field_array6 = array('merctitle','mercdesc','mercbuynav','merchealnav','merchealtext','merchealnotenough','merchealpaid','merctoomanycompanions','mercmanycompanions','merconecompanion','mercnocompanions');
	$field_array7 = array('modsall','modsother');
	$field_array8 = array('navsother','navsforest','navspvp','navsmercenarycamp','navstrain','navslodge','navsweapons','navsarmor','navsbank','navsgypsy','navsinn','navsstables','navsgardens','navsrock','navsclan','navsnews','navslist','navshof');
	// Arrays 2 to 8 are to be stored in the database as serialised arrays so we need to process each differently.
	$field2 = $field3 = $field4 = $field5 = $field6 = $field7 = $field8 = array();

	if( $_POST['addnew'] == 1 ) $_POST['id'] = 0;

	$_POST['author'] = ( $_POST['author'] != '' ) ? strip_tags($_POST['author']) : $session['user']['login'];

	if( $_POST['name'] == '' )
	{
		$_POST['name'] = getsetting('villagename', LOCATION_FIELDS); 
        $s = sanitize($_POST['name']);
        $s = str_replace(' ', '', $s);
        $_POST['sanitized_name'] = preg_replace('/[^a-zA-Z0-9]/', '', $s);
	}
	else
	{
		$_POST['name'] = strip_tags($_POST['name']);
        $s = sanitize($_POST['name']);
        $s = str_replace(' ', '', $s);
        $_POST['sanitized_name'] = preg_replace('/[^a-zA-Z0-9]/', '', $s);
	}

	if( $_POST['type'] == '' )
	{	// Default type if none is entered.
		$_POST['type'] = '';
	}
	else
	{
		$_POST['type'] = full_sanitize(str_replace(array('"',"'"), '', strip_tags($_POST['type'])));
	}

	if( isset($_POST['module']) && $_POST['module'] == '' ) $_POST['module'] = '_creator';

	$post = httpallpost();
    debug($post);
	$cityId = httppost('id');

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

	if( $cityId > 0 )
	{
		//
		// An existing city.
		//
		$oldvalues = @unserialize(stripslashes($post['oldvalues']));
		unset($post['oldvalues'], $post['id']);

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
				if( $key == 'name' && $val != $oldvalues[$key] ) {
                    db_query("UPDATE " . db_prefix('accounts') . " SET location = '$val' WHERE location = '{$oldvalues[$key]}'");
                    
                    // Handle YAML File Rename
                    global $language;
                    $lang = isset($language) ? $language : 'en';
                    
                    // Helper to get sanitized name same as generator
                    $get_sanitized = function($n) {
                        $s = sanitize($n);
                        $s = str_replace(' ', '', $s);
                        return preg_replace('/[^a-zA-Z0-9]/', '', $s);
                    };
                    
                    $old_sanitized = $get_sanitized($oldvalues[$key]);
                    $new_sanitized = $get_sanitized($val);
                    
                    $old_file = "translations/$lang/modules/village_{$old_sanitized}.yaml";
                    $new_file = "translations/$lang/modules/village_{$new_sanitized}.yaml";
                    
                    if (file_exists($old_file)) {
                        try {
                            // Rename file
                            rename($old_file, $new_file);
                            
                            // Update content
                            if (file_exists($new_file)) {
                                $yaml_data = Yaml::parseFile($new_file);
                                
                                $yaml_data['location'] = $val;
                                $yaml_data['location_clean'] = "village_{$new_sanitized}";
                                
                                if (isset($yaml_data['commentary']) && is_array($yaml_data['commentary'])) {
                                     $yaml_data['commentary']['section'] = "village-{$new_sanitized}";
                                }
                                
                                $dump = Yaml::dump($yaml_data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
                                file_put_contents($new_file, $dump);
                                output("`^Updated configuration file from '`@%s`^' to '`@%s`^'.`n", basename($old_file), basename($new_file));
                            }
                        } catch (Exception $e) {
                            output("`\$Error updating configuration file: %s`0`n", $e->getMessage());
                        }
                    }
                }
				$sql .= "`$key` = '".mysqli_real_escape_string($mysqli_resource, $val)."', ";
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


		$sql .= ( count($field7) > 0 ) ? "block_mods = '".mysqli_real_escape_string($mysqli_resource, serialize(array_values($field7)))."', " : "block_mods = '', ";
        
        // Process navs to match generator format (indexed array of strings)
        $processed_navs = [];
        foreach ($field8 as $k => $v) {
            if ($k == 'other') {
                // 'other' contains a comma-separated list or newlines
                $others = preg_split('/[,\n\r]+/', $v);
                foreach ($others as $other) {
                    $other = trim($other);
                    if ($other) $processed_navs[] = $other;
                }
            } elseif ($v == 1) {
                // Checkbox was checked
                // Convert back from key to filename if needed, usually just append key if it matches filename structure
                // But wait, the form keys are like 'navsforest', so $k is 'forest'.
                // The form definition in case_edit.php maps 'navsforest'=>'forest.php,bool'
                // Case Save logic earlier did: $keyname = substr($key,4) -> 'forest'
                // But we need 'forest.php'.
                
                // Let's check typical mapping.
                // In city_creator_functions: 'forest_php' => 0. Form key: 'navsforest_php'.
                // So $k here would be 'forest_php'.
                // We should replace '_' with '.' to get 'forest.php'
                $processed_navs[] = str_replace('_', '.', $k);
            }
        }
        $processed_navs = array_unique($processed_navs);
		$sql .= ( count($processed_navs) > 0 ) ? "block_navs = '".mysqli_real_escape_string($mysqli_resource, serialize(array_values($processed_navs)))."', " : "block_navs = '', ";
        $sql .= "sanitized_name = '" . mysqli_real_escape_string($mysqli_resource, $_POST['sanitized_name']) . "' ";
        
		db_query("UPDATE $villagesTable SET " . $sql . " WHERE id = '$cityId'");
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
						set_module_objpref('', $cityId, $keyname, $val, $modulename);
						output('`7Module: `&%s `7Setting: `&%s `7ObjectID: `&%s `7Value changed from "`&%s`7" to "`&%s`7"`n', $modulename, $keyname, $cityId, $oldvalues[$key], $val);
						gamelog("`7Module: `&$modulename `7Setting: `&$keyname `7ObjectID: `&$cityId `7Value changed from '`&{$oldvalues[$key]}`7' to '`&$val`7'`0","cities");
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
		unset($post['oldvalues'], $post['id'], $post['addnew']);

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
			array_push($cols, 'text');
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
			array_push($cols, 'blockmods');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field7)));
		}
		if( count($field8) > 0 )
		{
			array_push($cols, 'blocknavs');
			array_push($vals, mysqli_real_escape_string($mysqli_resource, serialize($field8)));
		}

        array_push($cols, 'sanitized_name');
        array_push(
            $vals, 
            mysqli_escape_string($mysqli_resource, sanitize($_POST['name']))
        );

		db_query("INSERT INTO $villagesTable (" . join(",",$cols) . ") VALUES (\"" . join("\",\"",$vals) . "\")");
		$cityId = db_insert_id();
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
						set_module_objpref('', $cityId, $keyname, $val, $modulename);
						output('`7Module: `&%s `7Setting: `&%s `7ObjectID: `&%s `7Value: `&%s`7.`n', $modulename, $keyname, $cityId, $val);
						gamelog("`7Module: `&$modulename `7Setting: `&$keyname `7ObjectID: `&$cityId `7Value: `&$val`7.`0","cities");
						unset($post[$key]);
					}
				}
			}
		}
	}

	// Call this hook to invalidate any cache files.
	modulehook('invalidatecache',array('id'=>$cityId,'name'=>$_POST['name']));

	addnav('Editor');
	addnav('Re-Edit City',$from.'&op=edit&id='.$cityId);
	addnav('Add a City',$from.'&op=edit');
	addnav('Main Page',$from);
?>