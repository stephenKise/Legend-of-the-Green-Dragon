<?php

require_once 'lib/clan/func.php';

$accountsPrefix = db_prefix('accounts');
$clanId = $session['user']['clanid'];
$clansPrefix = db_prefix('clans');
$selectedClanQuery = db_query_cached(
    "SELECT clanname AS name,
     FROM $clansPrefix
     WHERE clanid = '$clanId'",
    "clan_data:$clanId",
    3600
);
$clanData = db_fetch_assoc($selectedClanQuery);
['name' => $clanName] = $clanData;

page_header("$clanName Members"); 
addnav('Clan Options');
addnav('Clan Hall', 'clan.php');
output('This is your current clan membership:`n');

$setRank = (int) httpget('setrank') ?: 0;
$whoacctid = (int) httpget('whoacctid');
[
    'clanrank' => $userClanRank,
    'clanid' => $userClanId,
    'name' => $userName
] = $session['user'];

if ($setRank > 0) {
	$result = db_query(
        "SELECT name, login, clanrank
         FROM $accountsPrefix
         WHERE acctid = $whoacctid
         LIMIT 1"
    );
	$row = db_fetch_assoc($result);
	$who = $row['login'];
	$whoname = $row['name'];
	if ($setRank > '') {
		$args = modulehook(
            'clan-setrank',
            [
                'setrank' => $setRank,
                'login' => $who,
                'name' => $whoname,
                'acctid' => $whoacctid,
                'clanid' => $userClanId,
                'oldrank' => $row['clanrank']
            ]
        );
		if (!(isset($args['handled']) && $args['handled'])) {
			db_query(
                "UPDATE $accountsPrefix
                SET clanrank = GREATEST(0, least($userClanRank, $setRank))
                WHERE login = '$who'"
            );
			debuglog("$userName changed rank of {$whoname} to {$setRank}.", $whoacctid);
		}
	}
}

$remove = (int) httpget('remove') ?: 0;
if ($remove > 0) {
    $applicantRank = CLAN_APPLICANT;
    invalidatedatacache("clan_members:$userClanId");
	$result = db_query(
        "SELECT name, login, clanrank
         FROM $accountsPrefix
         WHERE acctid = $remove"
    );
	$row = db_fetch_assoc($result);
	$args = modulehook(
        'clan-setrank',
        [
            'setrank' => 0,
            'login' => $row['login'],
            'name' => $row['name'],
            'acctid' => $remove,
            'clanid' => $userClanId,
            'oldrank' => $row['clanrank']
        ]
    );
	db_query(
        "UPDATE $accountsPrefix
         SET clanrank = $applicantRank, clanid = 0, clanjoindate = NULL
         WHERE acctid = $remove AND clanrank <= $userClanRank"
     );
	debuglog(
        "$userName removed player {$row['login']} from {$clanData['name']}.",
        $remove
    );
}

$clanRanks = [
    CLAN_APPLICANT => loadTranslation('clan.rank_applicant'),
    CLAN_MEMBER => loadTranslation('clan.rank_member'),
    CLAN_OFFICER => loadTranslation('clan.rank_officer'),
    CLAN_LEADER => loadTranslation('clan.rank_leader'), 
    CLAN_FOUNDER => loadTranslation('clan.rank_founder'),
];
$moduleArgs = modulehook(
    'clanranks',
    ['ranks' => $clanRanks, 'clanid' => $clanId]
);
$clanRanks = $moduleArgs['ranks'];

$rank = loadTranslation('clan.members_list_rank_header');
$name = loadTranslation('clan.members_list_name_header');
$level = loadTranslation('clan.members_list_level_header');
$dragonKills = loadTranslation('clan.members_list_dk_header');
$joinDate = loadTranslation('clan.members_list_joindate_header');
$lastOn = loadTranslation('clan.members_list_laston_header');
$operations = translate_inline('Operations');
$operationsColumn = ($userClanRank >= CLAN_OFFICER)
    ? sprintf(
        loadTranslation('clan.members_list_operations_column'),
        loadTranslation('clan.members_list_operations_header')
    )
    : '';
$promote = loadTranslation('clan.members_list_operation_promote');
$demote = loadTranslation('clan.members_list_operation_demote');
$stepDown = loadTranslation('clan.members_list_operation_step_down');
$removeMember = loadTranslation('clan.members_list_operation_remove');
$confirmRemove = loadTranslation('clan.members_list_confirm_remove');
// @todo: Remove additional attributes, suggest a class for each table individually

$i = 0;
$totalDks = 0;
$members = [];
$result = db_query(
    "SELECT name, login, acctid, clanrank, laston, clanjoindate, dragonkills, level
     FROM $accountsPrefix
     WHERE clanid=$clanId
     ORDER BY clanrank DESC, dragonkills DESC, level DESC, clanjoindate"
);
while ($row = db_fetch_assoc($result)) {
	$i++;
    [
        'acctid' => $targetId,
        'clanrank' => $targetRank,
        'laston' => $targetLastOn,
        'dragonkills' => $targetDks,
        'name' => $targetName,
        'level' => $targetLevel,
        'clanjoindate' => $targetJoinDate,
        'login' => $targetLogin
    ] = $row;
    $targetClanRank = $clanRanks[$targetRank];
    // $targetLastOn = date('Y-m-d H:i:s'(strtotime($row['laston']));
	$totalDks += $targetDks;
    $rowHighlight = $i % 2 ? 'trlight' : 'trdark';
    $targetUri = urlencode($_SERVER['REQUEST_URI']);
    $bioLink = "bio.php?char=$targetId&ret=$targetUri";
    addnav('', $bioLink);
    $operationStr = '';
	if ($userClanRank >= CLAN_OFFICER) {
        $encodedLogin = rawurlencode($targetLogin);
        $encodedName = rawurlencode($targetName);
        $previousRank = clan_previousrank($clanRanks, $targetRank);
		if ($row['clanrank'] < $userClanRank && $row['clanrank'] < CLAN_FOUNDER) {
            $nextRank = clan_nextrank($clanRanks, $targetRank);
            $promoteUri = "clan.php?op=membership&setrank=$nextRank&who=$encodedLogin&whoname=$encodedName&whoacctid=$targetId";
			$operationStr .= "<td>[ <a href='$promoteUri'>$promote</a> | ";
			addnav('', $promoteUri);
		} else {
			$operationStr .= "<td>[ `)$promote`0 | ";
		}
		if (
            $row['clanrank'] <= $session['user']['clanrank']
            && $row['clanrank'] > CLAN_APPLICANT
            && $row['login'] != $session['user']['login']
            && $previousRank > 0
        ) {
            $demoteUri = "clan.php?op=membership&setrank=$previousRank&whoacctid=$targetId";
			$operationStr .= "<a href='$demoteUri'>$demote</a> | ";
			addnav('', $demoteUri);
		} elseif (
            $row['clanrank'] == CLAN_FOUNDER
            && $row['clanrank'] > CLAN_APPLICANT
            && $row['login'] == $session['user']['login']
        ) {
            $stepDownUri = "clan.php?op=membership&setrank=$previousRank&whoacctid=$targetId";
			$operationStr .= "<a href='$stepDownUri'>$stepDown</a> | ";
			addnav('', $stepDownUri);
		} else {
			$operationStr .= "`)$demote`0 | ";
		}
		if ($row['clanrank'] <= $session['user']['clanrank'] && $row['login']!=$session['user']['login']) {
            $removeUri = "clan.php?op=membership&remove=$targetId";
			$operationStr .= "<a 
                href='$removeUri'
                onClick=\"return confirm('$confirmRemove');\"
                >
                $removeMember
                </a> ]</td>";
			addnav('', $removeUri);
		} else {
			$operationStr .= "`)$removeMember`0 ]</td>";
		}
	}
	$member = sprintf(
        loadTranslation('clan.members_list_row'),
        $rowHighlight,
        $targetClanRank,
        $bioLink,
        $targetName,
        $targetLevel,
        $targetDks,
        date('m-d-Y', strtotime($targetJoinDate)),
        date('m-d-Y', strtotime($targetLastOn)),
        $operationStr
    );
    array_push($members, $member);
}
output_notl(
    sprintf(
        loadTranslation('clan.members_list_template'),
        $rank,
        $name,
        $level,
        $dragonKills,
        $joinDate,
        $lastOn,
        $operationsColumn,
        implode($members),
    ),
    true
);
output('`n`n`^This clan has a total of `$%s`^ Dragon kills.', $totalDks);

page_footer();