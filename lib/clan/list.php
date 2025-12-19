<?php

page_header('Clan Listing');
addnav('Clan Options');

$registrar = getsetting('clanregistrar', '`%Karissa');
$clansPrefix = db_prefix('clans');
$accountsPrefix = db_prefix('accounts');
$clanApplicant = CLAN_APPLICANT;
$result = db_query(
    "SELECT MAX($clansPrefix.clanid) AS id,
     MAX(clanshort) AS tag,
     MAX(clanname) AS name,
     count($accountsPrefix.acctid) AS member_count
     FROM $clansPrefix
     LEFT JOIN $accountsPrefix
     ON $clansPrefix.clanid = $accountsPrefix.clanid
     AND clanrank > $clanApplicant
     GROUP BY $clansPrefix.clanid
     ORDER BY member_count DESC"
 );
 $clanRows = [];

if (db_num_rows($result) > 0) {
	output("clan.list_clans", $registrar);
	$v = 0;
	$memb_n = loadTranslation('clan.list_members');
	$memb_1 = loadTranslation('clan.list_member');
	while ($row = db_fetch_assoc($result)) {    
        $trClass = $v % 2 ? 'trlight' : 'trdark';
		if ($row['member_count'] == 0) {
			db_query(
                "DELETE FROM $clansPrefix WHERE clanid = {$row['id']}"
            );
		} else {
			if ($row['member_count'] == 1) {
				$memb = sprintf($memb_1, $row['member_count']);
			} else {
				$memb = sprintf($memb_n, $row['member_count']);
			}
			$clanRow = sprintf(
                loadTranslation('clan.list_row'),
                $trClass,
				$row['tag'],
				$row['id'],
				htmlent($row['name']),
				$memb
            );
            array_push($clanRows, $clanRow);
			addnav('', "clan.php?detail={$row['id']}");
			$v++;
		}
	}
	output(
        loadTranslation('clan.list_template', [join($clanRows)]),
        true
    );
	addnav('Return to the Lobby','clan.php');
} else {
	output("clan.list_clans_empty", $registrar);
	addnav('Apply for a New Clan', 'clan.php?op=new');
	addnav('Return to the Lobby', 'clan.php');
}

page_footer();
