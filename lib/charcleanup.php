<?php

function char_cleanup($id, $type)
{
	// this function handles the grunt work of character cleanup.

	// Run any modules hooks who want to deal with character deletion, or stop it
	$return = modulehook("delete_character",
			array("acctid"=>$id, "deltype"=>$type, "dodel"=>true));
			
	if(!$return['dodel']) return false;

	// delete the output field from the accounts_output table introduced in 1.1.1

	db_query("DELETE FROM " . db_prefix("accounts_output") . " WHERE acctid=$id;");

	// delete the comments the user posted, necessary to have the systemcomments with acctid 0 working

	db_query("DELETE FROM " . db_prefix("commentary") . " WHERE author=$id;");

	// Clean up any clan positions held by this character
	$sql = "SELECT clanrank,clanid FROM " . db_prefix("accounts") .
		" WHERE acctid=$id";
	$res = db_query($sql);
	$row = db_fetch_assoc($res);
	if ($row['clanid'] != 0 && $row['clanrank'] == CLAN_LEADER) {
		$cid = $row['clanid'];
		// We need to auto promote or disband the clan.
		$sql = "SELECT name,acctid,clanrank FROM " . db_prefix("accounts") .
			" WHERE clanid=$cid AND clanrank > " . CLAN_APPLICANT . " AND acctid<>$id ORDER BY clanrank DESC, clanjoindate";
		$res = db_query($sql);
		if (db_num_rows($res)) {
			// Okay, we can promote if needed
			$row = db_fetch_assoc($res);
			if ($row['clanrank'] != CLAN_LEADER) {
				// No other leaders, promote this one
				$id1 = $row['acctid'];
				$sql = "UPDATE " . db_prefix("accounts") .
					" SET clanrank=" . CLAN_LEADER . " WHERE acctid=$id1";
				db_query($sql);
			}
		} else {
			// this clan needs to be disbanded.
			$sql = "DELETE FROM " . db_prefix("clans") . " WHERE clanid=$cid";
			db_query($sql);
			// And just in case we goofed, no players associated with a
			// deleted clan  This shouldn't be important, but.
			$sql = "UPDATE " . db_prefix("accounts") . " SET clanid=0,clanrank=0,clanjoindate='0000-00-00 00:00;00' WHERE clanid=$cid";
			db_query($sql);
		}
	}

	// Delete any module user prefs
	module_delete_userprefs($id);
	
	// Delete any mail to or from the user
	db_query('DELETE FROM ' . db_prefix('mail') . ' WHERE msgto=' . $id . ' OR msgfrom=' . $id);
	
	// Delete any news from the user
	db_query('DELETE FROM ' . db_prefix('news') . ' WHERE accountid=' . $id);
	
	return true;
}

?>
