<?php
/**
	Rewritten by MarcTheSlayer

	08/10/09 - v1.3.0
	+ Added pref so staff or donators can show as normal players if they want to.
	25/04/11 - v1.3.1
	+ Added a bunch settings and made some small alterations for them.
	+ Now gets the staff players from the 'stafflist' module instead of superuser flags.
	16/11/2011 - v1.3.2
	+ 2 missing db_query() lines. Found by Megan.
*/
function onlinelist_getmoduleinfo()
{
	$info = array(
		"name"=>"Online Character List",
		"description"=>"Sort the online name list into staff, donators and players.",
		"version"=>"1.3.2",
		"author"=>"Christian Rutsch modded by Seekey`2, rewritten by `@MarcTheSlayer",
		"category"=>"Administrative",
		"download"=>"http://dragonprime.net/index.php?topic=11876.0",
		"allowanonymous"=>1,
		"requires"=>array(
			"stafflist"=>"1.2|`\$Red Yates - core module",
		),
		"settings"=>array(
			"Main Settings,title",
				"showstaff"=>"Remove the staff section?,bool",
				"showdonators"=>"Remove the donators section?,bool",
				"shownewest"=>"Remove the newest player section?,bool",
				"newplayers"=>"Show new players for last:,datelength|5 days",
				"hide"=>"Hide staff/donators/newest section if empty?,bool",
			"Readme,title",
				"`@Players that have donated are by default listed as normal players. They'll need to go into their preferences to change this. If the donators section is off then this pref is ignored.,note",
				"`^I was told by a friend that there are unscrupulous LOGD admins that try to lure donators from other games to theirs. So be sure to warn your players if they wish to show themselves.,note",
		),
		"prefs"=>array(
			"Online Players List,title",
				"user_player"=>"If you're staff or a donator - show as just a player?,bool",
		),
	);
	return $info;
}

function onlinelist_install()
{
	if( is_module_active('onlinelist') )
	{
		output("`c`b`QUpdating 'onlinelist' Module.`b`n`c");
	}
	else
	{
		output("`c`b`QInstalling 'onlinelist' Module.`b`n`c");
		// By default, make all donators show as normal players.
		$sql = "SELECT acctid, SUM(amount)
				FROM " . db_prefix('paylog') . "
				GROUP BY acctid";
		$result = db_query($sql);
		while( $row = db_fetch_assoc($result) )
		{
			if( $row['amount'] > 0 ) set_module_pref('user_player',1,'onlinelist',$row['acctid']);
		}
	}
	module_addhook('onlinecharlist');
	return TRUE;
}

function onlinelist_uninstall()
{
	output("`n`c`b`Q'onlinelist' Module Uninstalled`0`b`c");
	return TRUE;
}

function onlinelist_dohook($hookname, $args)
{
	$args['handled'] = TRUE;
	$keys = translate_inline(array('`0Staff','`0Donators','`0Newest','`0Characters'));
	$list_array = array($keys[0]=>array(),$keys[1]=>array(),$keys[2]=>array(),$keys[3]=>array());

	$showstaff = get_module_setting('showstaff');
	$showdonators = get_module_setting('showdonators');
	$shownewest = get_module_setting('shownewest');
	if( $showstaff == 1 ) unset($list_array[$keys[0]]);
	if( $showdonators == 1 ) unset($list_array[$keys[1]]);
	if( $shownewest == 1 ) unset($list_array[$keys[2]]);

	$accounts = db_prefix('accounts');
	$userprefs = db_prefix('module_userprefs');

	$sql = "SELECT $accounts.acctid, $accounts.name, $accounts.regdate, $userprefs.value AS staff
			FROM $accounts
			LEFT JOIN $userprefs
				ON $accounts.acctid = $userprefs.userid
			WHERE $userprefs.modulename = 'stafflist'
				AND $userprefs.setting = 'rank'
				AND $accounts.locked = 0
				AND $accounts.loggedin = 1
				AND $accounts.laston > '" . date("Y-m-d H:i:s",strtotime("-".getsetting('LOGINTIMEOUT',900)." seconds")) . "'
			ORDER BY $accounts.superuser, $accounts.donation DESC";
	$result = db_query($sql);

	if( db_num_rows($result) > 0 )
	{
		$player_array = $donator_array = array();
	    $sql = "SELECT userid
	    		FROM $userprefs
	    		WHERE modulename = 'onlinelist'
	    			AND setting = 'user_player'
	    			AND value = 1";
		$result2 = db_query($sql);
		while( $row = db_fetch_assoc($result2) )
		{
			$player_array[] = $row['userid'];
		}

		if( $showdonators == 0 )
		{	// Just because they have site points, doesn't mean they donated.
			$sql = "SELECT acctid, SUM(amount)
					FROM " . db_prefix('paylog') . "
					GROUP BY acctid";
			$result2 = db_query($sql);
			while( $row = db_fetch_assoc($result2) )
			{
				if( $row['amount'] > 0 ) $donator_array[] = $row['acctid'];
			}
		}
		$newplayers = get_module_setting('newplayers');
		while( $row = db_fetch_assoc($result) )
		{
			if( !in_array($row['acctid'],$player_array) && isset($row['staff']) && $row['staff'] > 0 )
			{
				if( $showstaff == 1 ) $list_array[$keys[3]][] = $row['name'];
				else $list_array[$keys[0]][] = $row['name'];
			}
			elseif( !in_array($row['acctid'],$player_array) && in_array($row['acctid'],$donator_array) )
			{
				if( $showdonators == 1 ) $list_array[$keys[3]][] = $row['name'];
				else $list_array[$keys[1]][] = $row['name'];
			}
			elseif( $row['regdate'] > date("Y-m-d H:i:s",strtotime("-$newplayers", time())) )
			{
				if( $shownewest == 1 ) $list_array[$keys[3]][] = $row['name'];
				else $list_array[$keys[2]][] = $row['name'];
			}
			else
			{
				$list_array[$keys[3]][] = $row['name'];
			}
		}
	}

	$count = 0;
	$list_players = '';
	$hide = get_module_setting('hide');
	$online = translate_inline('Online');
	$none = translate_inline('`inone`i');
	$newest = translate_inline(array('New Player','New Players'));
	$characters = translate_inline(array('Player','Players'));
	$donaters = translate_inline(array('Donator','Donators'));
	$staff = translate_inline(array('Staff Member','Staff Members'));
	$plural_array = array($keys[3]=>$characters,$keys[2]=>$newest,$keys[1]=>$donaters,$keys[0]=>$staff);

	foreach( $list_array as $key => $value )
	{
		$player_count = count($list_array[$key]);
		$count += $player_count;
		if( $hide == 1 && $player_count == 0 && $key != $keys[3] ) continue;
		$plural = ( $player_count == 1 ) ? $plural_array[$key][0] : $plural_array[$key][1];
		$online_list = "`b$key $online`n($player_count $plural):`b`n";
		$list_players .= appoencode($online_list);
		if( $player_count > 0 )
		{
			foreach( $value as $key2 => $value2 )
			{
				$value = substr($value2, 0, 30);
				$list_players .= appoencode("`^$value2`n");
			}
		}
		else
		{
			$list_players .= appoencode("$none`n");
		}
		$list_players .= appoencode('`n');
	}

	$args['list'] = $list_players;
	$args['count'] = $count;

	return $args;
}

function onlinelist_run()
{
}
?>