<?php

require_once('lib/systemmail.php');
$applicantRank = CLAN_APPLICANT;
$officerRank = CLAN_OFFICER;
$leaderRank = CLAN_LEADER;
$accountsPrefix = db_prefix('accounts');
[
    'acctid' => $userId,
    'clanid' => $userClanId,
    'clanrank' => $userClanRank,
    'name' => $userName,
] = $session['user'];
$session['user']['clanid'] = 0;
$session['user']['clanrank'] = CLAN_APPLICANT;
unset($session['user']['clanjoindate']);
invalidatedatacache("clan_members:$userClanId");

page_header('Clan Halls');

modulehook(
    'clan-withdraw', 
    [
        'clanid' => $userClanId,
        'clanrank' => $userClanRank,
        'acctid' => $userId
    ]
);

addnav('Clan Options');
addnav('Return to the Lobby', 'clan.php');

if ($userClanRank === $applicantRank) {
    output('`&You have withdrawn your Clan application.`n');
} else {
    output('`&You have withdrawn from your clan.`n');
}

$result = db_query(
    "SELECT acctid
     FROM $accountsPrefix
     WHERE clanid = $userClanId
     AND clanrank >= $officerRank
     AND acctid <> $userId"
);
$withdrawalTitle = serialize(
    translate('`4Clan Withdrawal Notice`0')
);
$withdrawalMsg = sprintf_translate(
    '`&%s`^ has surrendered their position within your Clan!',
    $userName
);
while ($row = db_fetch_assoc($result)) {
	systemmail($row['acctid'], $withdrawalTitle, $withdrawalMsg);
}

$result = db_query(
    "SELECT count(*) AS leader_count
     FROM $accountsPrefix
     WHERE clanid = $userClanId
     AND clanrank >= $leaderRank
     AND acctid <> $userId"
);
$row = db_fetch_assoc($result);
// User was the last leader rank in the Clan
if ($row['leader_count'] == 0) {
    $result = db_query(
        "SELECT name, acctid, clanrank
         FROM $accountsPrefix
         WHERE clanid = $userClanId
         AND clanrank > $applicantRank
         AND acctid <> $userId
         ORDER BY clanrank DESC, clanjoindate
         LIMIT 1"
    );
    if ($row = db_fetch_assoc($result)) {
        // Promote highest ranking member by seniority
         db_query(
            "UPDATE $accountsPrefix
             SET clanrank = $leaderRank
             WHERE acctid = {$row['acctid']}"
        );
        output(
            "`^Promoting `%%s`^ to leader as they are the highest ranking
             member with the most seniority.`n`n",
            $row['name']
        );
    } else {
        // There are no other members, delete the Clan.
        $clansPrefix = db_prefix('clans');
        modulehook('clan-delete', ['clanid' => $userClanId]);
        db_query("DELETE FROM $clansPrefix WHERE clanid = $userClanId");
        db_query(
            "UPDATE $accountsPrefix
             SET clanid = 0, clanrank = $applicantRank, clanjoindate = NULL
             WHERE clanid = $userClanId"
        );
        output('`$Your Clan has been deleted.');
    }
}

page_footer();