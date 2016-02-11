<?php
function drinks_install_private(){
	if (db_table_exists(db_prefix("drinks"))) {
		debug("Drinks table already exists");
	}else{
		debug("Creating drinks table");
		$sqls = array(
			"CREATE TABLE " . db_prefix("drinks") . " (
				drinkid smallint(6) NOT NULL auto_increment,
				name varchar(25) NOT NULL default '',
				active tinyint(4) NOT NULL default '0',
				costperlevel int(11) NOT NULL default '0',
				hpchance tinyint(4) NOT NULL default '0',
				turnchance tinyint(4) NOT NULL default '0',
				alwayshp tinyint(4) NOT NULL default '0',
				alwaysturn tinyint(4) NOT NULL default '0',
				drunkeness tinyint(4) NOT NULL default '0',
				harddrink tinyint(4) NOT NULL default '0',
				hpmin int(11) NOT NULL default '0',
				hpmax int(11) NOT NULL default '0',
				hppercent int(11) NOT NULL default '0',
				turnmin int(11) NOT NULL default '0',
				turnmax int(11) NOT NULL default '0',
				remarks text NOT NULL default '',
				buffname varchar(50) NOT NULL default '',
				buffrounds tinyint(4) NOT NULL default '0',
				buffroundmsg varchar(75) NOT NULL default '',
				buffwearoff varchar(75) NOT NULL default '',
				buffatkmod text NOT NULL,
				buffdefmod text NOT NULL,
				buffdmgmod text  NOT NULL,
				buffdmgshield text NOT NULL,
				buffeffectfailmsg varchar(255) NOT NULL default '',
				buffeffectnodmgmsg varchar(255) NOT NULL default '',
				buffeffectmsg varchar(255) NOT NULL default '',
				PRIMARY KEY  (drinkid)) TYPE=MyISAM",
			"INSERT INTO " . db_prefix("drinks") . " VALUES (0, 'Ale', 1, 10, 2, 1, 0, 0, 33, 0, 0, 0, 10, 1, 1, 'Cedrik pulls out a glass, and pours a foamy ale from a tapped barrel behind him.  He slides it down the bar, and you catch it with your warrior-like reflexes.`n`nTurning around, you take a big chug of the hearty draught, and give {lover} an ale-foam mustache smile.`n`n', '`#Buzz', 10, 'You\\'ve got a nice buzz going.', 'Your buzz fades.', '1.25', '0', '0', '0', '', '', '')",
			"INSERT INTO " . db_prefix("drinks") . " VALUES (0, 'Habanero Martini', 1, 15, 0, 0, 1, 1, 50, 1, -5, 15, 0.0, -1, 1, 'Cedrik pulls out a bottle labeled with 3 X\\'s and a chile pepper and pours a miniscule shot into your glass.  You toss it back and grimace as smoke floods out of your ears.', '`\$Hot Hands', 12, 'You feel like your hands are about to burn off.', 'Finally, your hands are no longer burning.', '1.1', '.9', '1.5', '0', '', '', '')",
			"INSERT INTO " . db_prefix("drinks") . " VALUES (0, 'Mule Daniels', 1, 25, 2, 3, 0, 0, 50, 1, -10, -1, 0.0, 1, 3, 'Cedrik drags a large pony-keg out from behind the bar and pours a slug into a cast iron cup which rattles as the thick liquid is poured into it.  You toss it back in a gulp and make a face like a mule kicked you hard in the gut.  From across the room, you hear {lover} laugh at you.', '`#Mulekick', 15, 'You hear a donkey braying in the distance', 'That donkey finally shuts up.', '0', '0', '1.3', '1.3', 'Your head rings as the donkey kicks you instead.', 'That mule would have kicked {badguy} to the moon, but it missed!', '{badguy} sees`$ {damage}`) stars as the mule kicks him over the moon.')"
		);
		while (list($key,$sql)=each($sqls)){
			db_query($sql);
		}
	}

	// See if we're migrating from an old version of the drinks code with a
	// buffactivate field
	$sql = "DESCRIBE ". db_prefix("drinks");
	$result = db_query($sql);
	while($row = db_fetch_assoc($result)) {
		if ($row['Field']=="buffactivate"){
			debug("Dropping buffactivate from the drinks table.");
			$sql = "ALTER TABLE " . db_prefix("drinks") . " DROP buffactivate";
			db_query($sql);
		} // end if
		if ($row['Field']=="hppercent" && $row['Type']=="float") {
			debug("Altering {$row['Field']} from float to int in the drinks table.");
			$sql = "UPDATE " . db_prefix("drinks") . " SET hppercent=hppercent*100";
			db_query($sql);
			$sql = "ALTER TABLE " . db_prefix("drinks") . " CHANGE {$row['Field']} {$row['Field']} int(11) NOT NULL DEFAULT 0";
			db_query($sql);
		}
		if (($row['Field']=="buffatkmod" || $row['Field']=="buffdefmod" ||
			 $row['Field']=="buffdmgmod" || $row['Field']=="buffdmgshield") &&
			($row['Type'] == "float")) {
			debug("Altering {$row['Field']} from float to text in the drinks table.");
			$sql = "ALTER TABLE " . db_prefix("drinks") . " CHANGE {$row['Field']} {$row['Field']} text NOT NULL";
			db_query($sql);
		}
	} // end while


	// Install the hooks.
	module_addhook("ale");
	module_addhook("newday");
	module_addhook("superuser");
	module_addhook("header-graveyard");
	module_addhook("commentary");
	module_addhook("soberup");
	module_addhook("dragonkill");
	return true;
}
?>
