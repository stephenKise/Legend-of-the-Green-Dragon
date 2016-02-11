<?php
// translator ready
// addnews ready
// mail ready
//
//functions to pay attention to in this script:
// synctable() ensures that a table in the database matches the
// descriptor it's passed.
// table_create_descriptor() creates a descriptor from an existing table
// in the database.
// table_create_from_descriptor() writes SQL to create the table described
// by the descriptor.
//
// There's no support for foreign keys that INNODB offers.  Sorry.

function synctable($tablename,$descriptor,$nodrop=false){
	//table names should be db_prefix'd before they get in to
	//this function.
	if (!db_table_exists($tablename)){
		//the table doesn't exist, so we create it and are done.
		reset($descriptor);
		$sql = table_create_from_descriptor($tablename,$descriptor);
		debug($sql);
		if(!db_query($sql)) {
			output("`\$Error:`^ %s`n", db_error());
			rawoutput("<pre>".htmlentities($sql, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</pre>");
		} else {
			output("`^Table `#%s`^ created.`n", $tablename);
		}
	}else{
		//the table exists, so we need to compare it against the descriptor.
		$existing = table_create_descriptor($tablename);
		reset($descriptor);
		$changes = array();
		while (list($key,$val)=each($descriptor)){
			if ($key == "RequireMyISAM") continue;
			$val['type'] = descriptor_sanitize_type($val['type']);
			if (!isset($val['name'])) {
				if (($val['type']=="key" ||
							$val['type']=="unique key" ||
							$val['type']=="primary key")){
					if (substr($key,0,4)=="key-"){
						$val['name']=substr($key,4);
					}else{
						debug("<b>Warning</b>: the descriptor for <b>$tablename</b> includes a {$val['type']} which isn't named correctly.  It should be named key-$key. In your code, it should look something like this (the important change is bolded):<br> \"<b>key-$key</b>\"=>array(\"type\"=>\"{$val['type']}\",\"columns\"=>\"{$val['columns']}\")<br> The consequence of this is that your keys will be destroyed and recreated each time the table is synchronized until this is addressed.");
						$val['name']=$key;
					}
				}else{
					$val['name']=$key;
				}
			}else{
				if ($val['type']=="key" ||
						$val['type']=="unique key" ||
						$val['type']=="primary key"){
					$key = "key-".$val['name'];
				}else{
					$key = $val['name'];
				}
			}
			$newsql = descriptor_createsql($val);
			if (!isset($existing[$key])){
				//this is a new column.
				array_push($changes,"ADD $newsql");
			}else{
				//this is an existing column, let's make sure the
				//descriptors match.
				$oldsql = descriptor_createsql($existing[$key]);
				if ($oldsql != $newsql){
					//this descriptor line has changed.  Change the
					//table to suit.
					debug("Old: $oldsql<br>New:$newsql");
					if ($existing[$key]['type']=="key" ||
							$existing[$key]['type']=="unique key"){
						array_push($changes,
								"DROP KEY {$existing[$key]['name']}");
						array_push($changes,"ADD $newsql");
					}elseif ($existing[$key]['type']=="primary key"){
						array_push($changes,"DROP PRIMARY KEY");
						array_push($changes,"ADD $newsql");
					}else{
						array_push($changes,
								"CHANGE {$existing[$key]['name']} $newsql");
					}
				}//end if
			}//end if
			unset($existing[$key]);
		}//end while
		//drop no longer needed columns
		if (!$nodrop){
			reset($existing);
			while (list($key,$val)=each($existing)){
				//This column no longer exists.
				if ($val['type']=="key" || $val['type']=="unique key"){
					$sql = "DROP KEY {$val['name']}";
				}elseif ($val['type']=="primary key"){
					$sql = "DROP PRIMARY KEY";
				}else{
					$sql = "DROP {$val['name']}";
				}
				array_push($changes,$sql);
			}//end while
		}
		if (count($changes)>0) {
			//we have changes to do!  Woohoo!
			$sql = "ALTER TABLE $tablename \n".join(",\n",$changes);
			debug(nl2br($sql));
			db_query($sql);
			return count($changes);
		}
	}//end if
}//end function

function table_create_from_descriptor($tablename,$descriptor){
	$sql = "CREATE TABLE $tablename (\n";
	$type = "INNODB";
	reset($descriptor);
	$i=0;
	while (list($key,$val)=each($descriptor)){
		if ($key === "RequireMyISAM" && $val == 1) {
			// Let's hope that we don't run into badly formatted strings
			// but you know what, if we do, tough
			if (db_get_server_version() < "4.0.14") {
				$type = "MyISAM";
			}
			continue;
		} elseif ($key === "RequireMyISAM") {
			continue;
		}
		if (!isset($val['name'])) {
			if (($val['type']=="key" ||
						$val['type']=="unique key" ||
						$val['type']=="primary key")){
				if (substr($key,0,4)=="key-"){
					$val['name']=substr($key,4);
				}else{
					debug("<b>Warning</b>: the descriptor for <b>$tablename</b> includes a {$val['type']} which isn't named correctly.  It should be named key-$key.  In your code, it should look something like this (the important change is bolded):<br> \"<b>key-$key</b>\"=>array(\"type\"=>\"{$val['type']}\",\"columns\"=>\"{$val['columns']}\")<br> The consequence of this is that your keys will be destroyed and recreated each time the table is synchronized until this is addressed.");
					$val['name']=$key;
				}
			}else{
				$val['name']=$key;
			}
		}else{
			if ($val['type']=="key" ||
					$val['type']=="unique key" ||
					$val['type']=="primary key"){
				$key = "key-".$val['name'];
			}else{
				$key = $val['name'];
			}
		}
		if ($i>0) $sql.=",\n";
		$sql .= descriptor_createsql($val);
		$i++;
	}
	$sql .= ") Type=$type";
	return $sql;
}

function table_create_descriptor($tablename){
	//this function assumes that $tablename is already passed
	//through db_prefix.
	$descriptor = array();

	//fetch column desc's
	$sql = "DESCRIBE $tablename";
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		$item = array();
		$item['name']=$row['Field'];
		$item['type']=$row['Type'];
		if ($row['Null']) $item['null'] = true;
		if (trim($row['Default'])!="") $item['default']=$row['Default'];
		if (trim($row['Extra'])!=="") $item['extra']=$row['Extra'];
		$descriptor[$item['name']] = $item;
	}

	$sql = "SHOW KEYS FROM $tablename";
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		if ($row['Seq_in_index']>1){
			//this is a secondary+ column on some previous key;
			//add this to that column's keys.
			$str = $row['Column_name'];
			if ($row['Sub_part'])
				$str .= "(" . $row['Sub_part'] . ")";
			$descriptor['key-'.$row['Key_name']]['columns'] .=
				",".$str;
		}else{
			$item = array();
			$item['name'] = $row['Key_name'];
			if ($row['Key_name']=="PRIMARY")
				$item['type'] = "primary key";
			else
				$item['type'] = "key";
			if ($row['Non_unique']==0)
				$item['unique'] = true;
			$str = $row['Column_name'];
			if ($row['Sub_part'])
				$str .= "(" . $row['Sub_part'] . ")";
			$item['columns'] = $str;
			$descriptor['key-'.$item['name']] = $item;
		}//end if
	}//end while

	return $descriptor;
}

function descriptor_createsql($input){
	$input['type'] = descriptor_sanitize_type($input['type']);
	if ($input['type']=="key" || $input['type']=='unique key'){
		//this is a standard index
		if (is_array($input['columns']))
			$input['columns'] = join(",",$input['columns']);
		if (!isset($input['name'])) {
			//if the user didn't define a name we should give it one
			if (strpos($input['columns'],",")!==false){
				//if there are multiple columns, the name is just the
				//first column
				$input['name'] =
					substr($input['columns'],strpos($input['columns'],","));
			}else{
				//if there is only one column, the key name is the same
				//as the column name.
				$input['name'] = $input['columns'];
			}
		}
		if (substr($input['type'],0,7)=="unique ") $input['unique'] = true;
		$return = (isset($input['unique']) && $input['unique']?"UNIQUE ":"")
			."KEY {$input['name']} "
			."({$input['columns']})";
	}elseif ($input['type']=="primary key"){
		//this is a primary key
		if (is_array($input['columns']))
			$input['columns'] = join(",",$input['columns']);
		$return = "PRIMARY KEY ({$input['columns']})";
	}else{
		//this is a standard column
		if (!array_key_exists('extra', $input)) $input['extra']="";
		$return = $input['name']." "
			.$input['type']
			.(isset($input['null']) && $input['null']?"":" NOT NULL")
			.(isset($input['default']) &&
					$input['default']>""?" default '{$input['default']}'":"")
			." ".$input['extra'];
	}
	return $return;
}

function descriptor_sanitize_type($type){
	$type = strtolower($type);
	$changes = array(
		"primary index"=>"primary key",
		"primary"=>"primary key",
		"index"=>"key",
		"unique index"=>"unique key",
	);
	if (isset($changes[$type]))
		return $changes[$type];
	else
		return $type;
}
?>
