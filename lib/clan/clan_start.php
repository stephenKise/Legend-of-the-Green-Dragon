<?php
	page_header("Clan Hall for %s",  full_sanitize($claninfo['clanname']));
	addnav("Clan Options");
	if ($op==""){
		require_once("lib/clan/clan_default.php");
	}elseif ($op=="motd"){
		require_once("lib/clan/clan_motd.php");
	}elseif ($op=="membership"){
		require_once("lib/clan/clan_membership.php");
	}elseif ($op=="withdrawconfirm"){
		output("Are you sure you want to withdraw from your clan?");
		addnav("Withdraw?");
		addnav("No","clan.php");
		addnav("!?Yes","clan.php?op=withdraw");
	}elseif ($op=="withdraw"){
		require_once("lib/clan/clan_withdraw.php");
	}

?>