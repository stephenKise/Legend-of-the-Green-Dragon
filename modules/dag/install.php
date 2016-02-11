<?php
function dag_install_private(){
	global $session;
	module_addhook("inn-desc");
	module_addhook("inn");
	module_addhook("superuser");
	module_addhook("newday");
	module_addhook("pvpwin");
	module_addhook("dragonkill");
	module_addhook("showsettings");
	module_addhook("delete_character");
	debug("Creating Bounty Table");
	$sql = "SHOW TABLES";
	$result = db_query($sql);
	$bountytableisthere=false;
	while ($row = db_fetch_assoc($result)){
		list($key,$val)=each($row);
		if ($val==db_prefix("bounty")){
			$bountytableisthere=true;
			break;
		}
	}
	if ($bountytableisthere){
		debug("The bounty table already exists on your server, not overwriting it.`n");
	}else{
		debug("Creating the bounty table.`n");
		$sql="CREATE TABLE " . db_prefix("bounty") . " (
			bountyid int(11) unsigned NOT NULL auto_increment,
			amount int(11) unsigned NOT NULL default '0',
			target int(11) unsigned NOT NULL default '0',
			setter int(11) unsigned NOT NULL default '0',
			setdate datetime NOT NULL default '0000-00-00 00:00:00',
			status int(11) unsigned NOT NULL default '0',
			winner int(11) unsigned NOT NULL default '0',
			windate datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (bountyid),
			INDEX(status),
			INDEX(target),
			INDEX(status,target)
		) Type=INNODB";
		db_query($sql);
	}
	//look to see if we're migrating bounties from the old system.
	$sql = "DESCRIBE " . db_prefix("accounts");
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		if ($row['Field']=="bounty"){
			$sql = "INSERT INTO " . db_prefix("bounty") . " (amount,target,setdate) SELECT bounty,acctid,'".date("Y-m-d H:i:s")."' FROM " . db_prefix("accounts") . " WHERE " . db_prefix("accounts") . ".bounty > 0";
			debug("The bounty column was found in your accounts table, migrating its values to the bounty table.`n");
			db_query($sql);
			debug("Dropping accounts column from the user table.`n");
			$sql = "ALTER TABLE " . db_prefix("accounts") . " DROP bounty";
			db_query($sql);
			//drop it from the user's session too.
			unset($session['user']['bounty']);
		}elseif ($row['Field']=="bounties"){
			$sql = "SELECT bounties,acctid FROM " . db_prefix("accounts") . " WHERE bounties>0";
			$result1 = db_query($sql);
			debug("Migrating bounty counts.`n");
			while ($row1 = db_fetch_assoc($result1)){
				$sql = "INSERT INTO " . db_prefix("module_userprefs") . " (modulename,setting,userid,value) VALUES ('dag','bounties',{$row1['acctid']},{$row1['bounties']})";
				db_query($sql);
			}//end while
			debug("Dropping bounty count from the user table.`n");
			$sql = "ALTER TABLE " . db_prefix("accounts") . " DROP bounties";
			db_query($sql);
			//drop it from the user's session too.
			unset($session['user']['bounties']);
		}//end if
	}//end while
	return true;
}