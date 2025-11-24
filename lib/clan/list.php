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

if (db_num_rows($result) > 0) {
	output(
        "`7You ask %s`7 for the clan listings. She points you toward a marquee
         board near the entrance of the lobby that lists the clans.`0`n`n",
        $registrar
    );
	$v = 0;
	$memb_n = translate_inline('`7(`@%s`7 members)`0');
	$memb_1 = translate_inline('`7(`$%s`7 member)`0');
	rawoutput('<table cellspacing="0" cellpadding="2" align="left">');
	while ($row = db_fetch_assoc($result)) {    
        $trClass = $v % 2 ? 'trlight' : 'trdark';
		if ($row['member_count'] == 0) {
			db_query(
                "DELETE FROM $clansPrefix WHERE clanid = {$row['id']}"
            );
		} else {
			rawoutput(sprintf_translate('<tr class="%s"><td>', $trClass));
			if ($row['member_count'] == 1) {
				$memb = sprintf($memb_1, $row['member_count']);
			} else {
				$memb = sprintf($memb_n, $row['member_count']);
			}
			output_notl(
                '• `2&lt;`7%s`2&gt; <a href="clan.php?detail=%s">%s</a> %s`n',
				$row['tag'],
				$row['id'],
				htmlent($row['name']),
				$memb,
                true
            );
			rawoutput('</td></tr>');
			addnav('', "clan.php?detail={$row['id']}");
			$v++;
		}
	}
	rawoutput('</table>');
	addnav('Return to the Lobby','clan.php');
} else {
	output(
        "`7You ask %s`7 for the clan listings. She stares at you blankly for a
         few moments, then says, \"`5Sorry pal, no one has had enough gumption
         to start up a clan yet.  Maybe that should be you, eh?`7\"",
        $registrar
    );
	addnav('Apply for a New Clan', 'clan.php?op=new');
	addnav('Return to the Lobby', 'clan.php');
}

page_footer();
