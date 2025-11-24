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
        "$userName removed player {$row['login']} from {$claninfo['clanname']}.",
        $remove
    );
}

$clanRanks = [
    CLAN_APPLICANT => '`!Applicant`0',
    CLAN_MEMBER => '`#Member`0',
    CLAN_OFFICER => '`^Officer`0',
    CLAN_LEADER => '`&Leader`0', 
    CLAN_FOUNDER => '`$Founder`0'
];
$moduleArgs = modulehook(
    'clanranks',
    ['ranks' => $clanRanks, 'clanid' => $clanId]
);
$clanRanks = translate_inline($moduleArgs['ranks']);

$rank = translate_inline('Rank');
$name = translate_inline('Name');
$level = translate_inline('Level');
$dragonKills = translate_inline('Dragon Kills');
$joinDate = translate_inline('Join Date');
$lastOn = translate_inline('Last On');
$operations = translate_inline('Operations');
$operationsColumn = ($userClanRank >= CLAN_OFFICER)
    ? "<td>$operations</td>"
    : '';
$promote = translate_inline('Promote');
$demote = translate_inline('Demote');
$stepDown = translate_inline('`$Step down as founder');
$removeMember = translate_inline('Remove From Clan');
$confirmRemove = translate_inline('Are you sure you wish to remove this member?');
// @todo: Remove additional attributes, suggest a class for each table individually
rawoutput(
    "<table border='0' cellpadding='2' cellspacing='0'>
     <tr class='trhead'>
      <td>$rank</td>
      <td>$name</td>
      <td>$level</td>
      <td>$dragonKills</td>
      <td>$joinDate</td>
      <td>$lastOn</td>
      $operationsColumn
     </tr>"
);
$i = 0;
$totalDks = 0;
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
	output_notl(
        "<tr class='$rowHighlight'>
            <td>`#$targetClanRank`0</td>
            <td><a href='$bioLink'>`&$targetName`0</a></td>
            <td align='center'>`^$targetLevel`0</td>
            <td align='center'>`\$$targetDks`0</td>
            <td>`3$targetJoinDate`0</td>
            <td>`#$targetLastOn`0</td>",
        true
    );
	if ($userClanRank >= CLAN_OFFICER) {
        $encodedLogin = rawurlencode($targetLogin);
        $encodedName = rawurlencode($targetName);
        $previousRank = clan_previousrank($clanRanks, $targetRank);
		rawoutput('<td>');
		if ($row['clanrank'] < $userClanRank && $row['clanrank'] < CLAN_FOUNDER) {
            $nextRank = clan_nextrank($clanRanks, $targetRank);
            $promoteUri = "clan.php?op=membership&setrank=$nextRank&who=$encodedLogin&whoname=$encodedName&whoacctid=$targetId";
			rawoutput("[ <a href='$promoteUri'>$promote</a> | ");
			addnav('', $promoteUri);
		} else {
			output_notl("[ `)$promote`0 | ");
		}
		if (
            $row['clanrank'] <= $session['user']['clanrank']
            && $row['clanrank'] > CLAN_APPLICANT
            && $row['login'] != $session['user']['login']
            && $previousRank > 0
        ) {
            $demoteUri = "clan.php?op=membership&setrank=$previousRank&whoacctid=$targetId";
			rawoutput("<a href='$demoteUri'>$demote</a> | ");
			addnav('', $demoteUri);
		} elseif (
            $row['clanrank'] == CLAN_FOUNDER
            && $row['clanrank'] > CLAN_APPLICANT
            && $row['login'] == $session['user']['login']
        ) {
            $stepDownUri = "clan.php?op=membership&setrank=$previousRank&whoacctid=$targetId";
			output_notl("<a href='$stepDownUri'>$stepDown</a> | ",true);
			addnav('', $stepDownUri);
		} else {
			output_notl("`)$demote`0 | ");
		}
		if ($row['clanrank'] <= $session['user']['clanrank'] && $row['login']!=$session['user']['login']) {
            $removeUri = "clan.php?op=membership&remove=$targetId";
			rawoutput(
                "<a href='$removeUri' onClick=\"return confirm('$confirmRemove');\">
                 $removeMember
                 </a> ]"
            );
			addnav('', $removeUri);
		} else {
			output_notl("`)$removeMember`0 ]");
		}
		rawoutput('</td>');
	}
	rawoutput('</tr>');
}
rawoutput('</table>');
output('`n`n`^This clan has a total of `$%s`^ Dragon kills.', $totalDks);

page_footer();