<?php
// translator ready
// addnews ready
// mail ready

function charrestore_getmoduleinfo(){
	$info = array(
		"name"=>"Character Restorer",
		"category"=>"Administrative",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"download"=>"core_module",
		"settings"=>array(
			"auto_snapshot"=>"Create character snapshots upon character expiration?,bool|1",
			"dk_threshold"=>"&nbsp;&nbsp;+-- Dragon Kill threshold above which snapshots will be taken?,int|5",
			"lvl_threshold"=>"&nbsp;&nbsp;&nbsp;&nbsp;+-- Level within this DK above which snapshots will be taken?,int|0",
			"manual_snapshot"=>"Create a snapshot when a char is manually deleted?,bool|0",
			"suicide_snapshot"=>"Create a snapshot when a user deletes themselves?,bool|0",
			"permadeath_snapshot"=>"Create a snapshot when a user perma-dies?,bool|1",
			"Perma death is not current implemented at the time of writing this module; nor do I have any plans that way; it just made sense to reserve it in case either I or someone else ever introduced this option.,note",
			"snapshot_dir"=>"Location to store snapshots|../logd_snapshots",
		),
	);
	return $info;
}

function charrestore_install(){
	module_addhook("delete_character");
	module_addhook("superuser");
	return true;
}

function charrestore_uninstall(){
	return true;
}

function charrestore_dohook($hookname,$args){
	if ($hookname=="superuser"){
		global $session;
		if ($session['user']['superuser'] & SU_EDIT_USERS){
			addnav("Character Restore");
			addnav("Restore a deleted char",
					"runmodule.php?module=charrestore&op=list&admin=true");
		}
	}elseif ($hookname=="delete_character"){
		if ($args['deltype']==CHAR_DELETE_AUTO &&
				!get_module_setting("auto_snapshot")) return $args;
		if ($args['deltype']==CHAR_DELETE_MANUAL &&
				!get_module_setting("manual_snapshot")) return $args;
		if ($args['deltype']==CHAR_DELETE_SUICIDE &&
				!get_module_setting("suicide_snapshot")) return $args;
		if ($args['deltype']==CHAR_DELETE_PERMADEATH &&
				!get_module_setting("permadeath_snapshot")) return $args;
		//time to create a snapshot.
		$sql = "SELECT * FROM ".db_prefix("accounts")." WHERE acctid='{$args['acctid']}'";
		$result = db_query($sql);
		if (db_num_rows($result) > 0){
			$row = db_fetch_assoc($result);

			//test if the user is below the snapshot threshold
			if ($args['deltype']==CHAR_DELETE_AUTO){
				if ($row['dragonkills']<get_module_setting("dk_threshold") ||
					$row['dragonkills']==get_module_setting("dk_threshold") &&
					$row['level'] < get_module_setting("lvl_threshold")){
					return $args;
				}
			}

			$user = array("account"=>array(),"prefs"=>array());

			//set up the user's account table fields
			//reduces storage footprint.
			$nosavefields = array("output"=>true,"allowednavs"=>true);
			while (list($key,$val)=each($row)){
				if (!isset($nosavefields[$key])){
					$user['account'][$key] = $val;
				}
			}

			//set up the user's module preferences
			$sql = "SELECT * FROM ".db_prefix("module_userprefs")." WHERE userid='{$args['acctid']}'";
			$prefs = db_query($sql);
			while ($row = db_fetch_assoc($prefs)){
				if (!isset($user['prefs'][$row['modulename']])){
					$user['prefs'][$row['modulename']] = array();
				}
				$user['prefs'][$row['modulename']][$row['setting']] = $row['value'];
			}

			//write the file
			$path = charrestore_getstorepath();
			$fp = @fopen($path.str_replace(" ","_",$user['account']['login'])."|".date("Ymd"),"w+");
			if ($fp){
				if (fwrite($fp,
					serialize($user)
					)!==false){
					$failure=false;
				}else{
					$failure=true;
				}
				fclose($fp);
			}
		}
	}
	return $args;
}

function charrestore_getstorepath(){
	//returns a valid path name where snapshots are stored.
	$path = get_module_setting("snapshot_dir");
	if (substr($path,-1)!="/" && substr($path,-1)!="\\"){
		$path = $path."/";
	}
	return $path;
}

function charrestore_run(){
	check_su_access(SU_EDIT_USERS);
	require_once("lib/superusernav.php");
	page_header("Character Restore");
	superusernav();
	addnav("Functions");
	addnav("Search","runmodule.php?module=charrestore&op=list");
	if (httpget("op")=="list"){
		output("Please note that only characters who have reached at least level %s in DK %s will have been saved!`n`n", get_module_setting("lvl_threshold"), get_module_setting("dk_threshold"));

		rawoutput("<form action='runmodule.php?module=charrestore&op=list' method='POST'>");
		addnav("","runmodule.php?module=charrestore&op=list");
		output("Character Login: ");
		rawoutput("<input name='login' value=\"".htmlentities(stripslashes(httppost("login")), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"><br>");
		output("After date: ");
		rawoutput("<input name='start' value=\"".htmlentities(stripslashes(httppost("start")), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"><br>");
		output("Before date: ");
		rawoutput("<input name='end' value=\"".htmlentities(stripslashes(httppost("end")), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"><br>");
		$submit = translate_inline("Submit");
		rawoutput("<input type='submit' value='$submit' class='button'>");
		rawoutput("</form>");
		//do the search.
		$login = httppost("login");
		$start = httppost("start");
		$end = httppost("end");
		if ($start > "") $start = strtotime($start);
		if ($end > "") $end = strtotime($end);
		if ($login.$start.$end > ""){
			$path = charrestore_getstorepath();
			debug($path);
			$d = dir($path);
			$count = 0;
			while (($entry = $d->read())!==false){
				$e = explode("|",$entry);
				if (count($e)<2) continue;
				$name = str_replace("_"," ",$e[0]);
				$date = strtotime($e[1]);
				if ($login > "") {
					if (strpos(strtolower($name),strtolower($login))===false)
						continue;
				}
				if ($start > ""){
					if ($date < $start) continue;
				}
				if ($end > ""){
					if ($date > $end) continue;
				}
				$count++;
				rawoutput("<a href='runmodule.php?module=charrestore&op=beginrestore&file=".rawurlencode($entry)."'>$name</a> (".date("M d, Y",$date).")<br>");
				addnav("","runmodule.php?module=charrestore&op=beginrestore&file=".rawurlencode($entry));
			}
			if ($count == 0) {
				output("No characters matching the specified criteria were found.");
			}
		}
	}elseif (httpget("op")=="beginrestore"){
		$user = unserialize(join("",file(charrestore_getstorepath().httpget("file"))));
		$sql = "SELECT count(*) AS c FROM ".db_prefix("accounts")." WHERE login='{$user['account']['login']}'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		rawoutput("<form action='runmodule.php?module=charrestore&op=finishrestore&file=".rawurlencode(stripslashes(httpget("file")))."' method='POST'>");
		addnav("","runmodule.php?module=charrestore&op=finishrestore&file=".rawurlencode(stripslashes(httpget("file"))));
		if ($row['c'] > 0){
			output("`\$The user's login conflicts with an existing login in the system.");
			output("You will have to provide a new one, and you should probably think about giving them a new name after the restore.`n");
			output("`^New Login: ");
			rawoutput("<input name='newlogin'><br>");
		}
		$yes = translate_inline("Do the restore");
		rawoutput("<input type='submit' value='$yes' class='button'>");

		output("`n`#Some user info:`0`n");
		$vars = array(
			"login"=>"Login",
			"name"=>"Name",
			"laston"=>"Last On",
			"email"=>"Email",
			"dragonkills"=>"DKs",
			"level"=>"Level",
			"gentimecount"=>"Total hits",
		);
		while (list($key,$val)=each($vars)){
			output("`^$val: `#%s`n",$user['account'][$key]);
		}
		rawoutput("<input type='submit' value='$yes' class='button'>");
		rawoutput("</form>");

	}elseif (httpget("op")=="finishrestore"){
		$user = unserialize(join("",file(charrestore_getstorepath().httpget("file"))));
		$sql = "SELECT count(*) AS c FROM ".db_prefix("accounts")." WHERE login='".(httppost('newlogin')>''?httppost('newlogin'):$user['account']['login'])."'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		if ($row['c'] > 0){
			redirect("runmodule.php?module=charrestore&op=beginrestore&file=".rawurlencode(stripslashes(httpget("file"))));
		}else{
			if (httppost("newlogin") > "") $user['account']['login'] = httppost('newlogin');
			$sql = "DESCRIBE ".db_prefix("accounts");
			$result = db_query($sql);
			$known_columns = array();
			while ($row = db_fetch_assoc($result)){
				$known_columns[$row['Field']] = true;
			}

			$keys = array();
			$vals = array();

			while (list($key,$val)=each($user['account'])){
				if ($key=="laston"){
					array_push($keys,$key);
					array_push($vals,"'".date("Y-m-d H:i:s",strtotime("-1 day"))."'");
				}elseif (! isset($known_columns[$key])){
					output("`2Dropping the column `^%s`n",$key);
				}else{
					array_push($keys,$key);
					array_push($vals,"'".addslashes($val)."'");
				}
			}
			$sql = "INSERT INTO ".db_prefix("accounts")." (\n".join("\t,\n",$keys).") VALUES (\n".join("\t,\n",$vals).")";
			db_query($sql);
			$id = db_insert_id();
			if ($id > 0){
				addnav("Edit the restored user","user.php?op=edit&userid=$id");
				if ($id != $user['account']['acctid']){
					output("`^The account was restored, though the account ID was not preserved; things such as news, mail, comments, debuglog, and other items associated with this account that were not stored as part of the snapshot have lost their association.");
					output("The original ID was `&%s`^, and the new ID is `&%s`^.",$user['account']['acctid'],$id);
					output("The most common cause of this problem is another account already present with the same ID.");
					output("Did you do a restore of an already existing account?  If so, the existing account was not overwritten.`n");
				}else{
					output("`#The account was restored.`n");
				}
				output("`#Now working on module preferences.`n");
				while (list($modulename,$values)=each($user['prefs'])){
					output("`3Module: `2%s`3...`n",$modulename);
					if (is_module_installed($modulename)){
						while (list($prefname,$value)=each($values)){
							set_module_pref($prefname,$value,$modulename,$id);
						}
					}else{
						output("`\$Skipping prefs for module `^%s`\$ because this module is not currently installed.`n",$modulename);
					}
				}
				output("`#The preferences were restored.`n");
			}else{
				output("`\$Something funky has happened, preventing this account from correctly being created.");
				output("I'm sorry, you may have to recreate this account by hand.");
				output("The SQL I tried was:`n");
				rawoutput("<pre>".htmlentities($sql, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</pre>");
			}
		}
	}
	page_footer();
}
?>
