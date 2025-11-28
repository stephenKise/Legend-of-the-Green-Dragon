<?php

require_once 'lib/nltoappon.php';

page_header('Clan Information');

$currentClanId = $session['user']['clanid'];
$targetId = (int) httpget('detail');
$returnLink = urlencode($_SERVER['REQUEST_URI']);
$detailTemplate = file_get_contents('lib/clan/templates/ClanDetailsTable.php');
$clansPrefix = db_prefix('clans');
// @todo Reformat the schema for clans, keys be renamed and not prefixed
// @todo Add a 'description_blocked' field for the Clans table
$selectedClanQuery = db_query_cached(
    "SELECT clanname AS name,
     clanshort AS tag,
     clandesc AS description,
     clanmotd AS motd,
     clanid AS id,
     descauthor,
     motdauthor,
     customsay
     FROM $clansPrefix
     WHERE clanid = '$targetId'",
    "clan_data:$targetId",
    3600
);
$clanData = db_fetch_assoc($selectedClanQuery);
[
    'name' => $clanName,
    'tag' => $clanTag,
    'description' => $clanDesc,
    'descauthor' => $clanDescAuthor
] = $clanData;

addnav('Clan Options');
if ((int) $currentClanId === 0) {
    addnav('Apply to this Clan', "clan.php?op=apply&to=$targetId");
}
addnav('List Clans', 'clan.php?op=list');
addnav('Return to the Lobby', 'clan.php');

page_header(
    'Clan membership for %s &lt;%s&gt;',
    full_sanitize($clanName),
    full_sanitize($clanTag)
);

// @todo Add/Rename SU constants, because this one doesn't seem right.
if ($session['user']['superuser'] & SU_EDIT_COMMENTS) {
    require_once('lib/clan/func.php');
    editClanNameForm($targetId);
}

// @todo Move this to its own file.
$renamedClanName = httppost('clan_name');
$renamedClanTag = httppost('clan_tag');
if (
    $session['user']['superuser'] & SU_EDIT_COMMENTS
    && $renamedClanName > ''
    && $renamedClanTag > ''
) {
	$name = full_sanitize($renamedClanName);
	$tag = full_sanitize($renamedClanTag);
    $additionalStmt = '';
    if (httppost('toggle_block') > '') {
        if ($clanDescAuthor == 0) {
            $newAuthor = $session['user']['acctid'];
            output('`$Unblocking Clan description.`0`n');
        } else {
            $newAuthor = 0;
            output('`$Blocking Clan description.`0`n');
        }
        $additionalStmt = ", descauthor = $newAuthor";
    } else {
        output('`QUpdating clan names.`0`n');
    }
	db_query(
        "UPDATE $clansPrefix
         SET clanname = '$renamedClanName', clanshort = '$renamedClanTag'
         $additionalStmt
         WHERE clanid = '$targetId'"
    );
    invalidatedatacache("clan_desc_author:$targetId");
	invalidatedatacache("clan_data:$targetId");
}

output('`@About `^%s`@:`0`n', $clanName);
if ($clanDescAuthor != 0) output_notl(nltoappon($clanDesc));
if ( nltoappon($clanDesc) != '' ) output('`n`n');

output(
    '`0This is the current clan membership of `^%s `2<`7%s`2>:`n',
    $clanName,
    $clanTag
);
$clanRanks = [
    CLAN_APPLICANT => '`!Applicant`0',
    CLAN_MEMBER => '`#Member`0',
    CLAN_OFFICER => '`^Officer`0',
    CLAN_LEADER => '`&Leader`0', 
    CLAN_FOUNDER => '`$Founder`0'
];
$moduleArgs = modulehook('clanranks', ['ranks' => $clanRanks, 'clanid' => $targetId]);
$clanRanks = translate_inline($moduleArgs['ranks']);
$totalDks = 0;
$memberRows = [];
$accountsPrefix = db_prefix('accounts');
$clanMembers = db_query_cached(
    "SELECT acctid, name, login, clanrank, clanjoindate, dragonkills
     FROM $accountsPrefix
     WHERE clanid = $targetId
     ORDER BY clanrank DESC, clanjoindate",
    "clan_members:$targetId",
    60 * 60 * 24
);

foreach ($clanMembers as $key => $clanMember) {
    $exitLoop = false;
    if (!is_array($clanMember)) {
        $exitLoop = true;
        $clanMember = $clanMembers;
    }
    $targetName = $clanMember['name'];
    $bioUri = 'bio.php?char=' . $clanMember['acctid'] . '&ret=' . $returnLink;
    $bioLink = "<a href='$bioUri'>$targetName</a>";
    $currentRow = sprintf(
        '<tr class="%s"><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
        count($memberRows) % 2 ? 'trlight' : 'trdark',
        $clanRanks[$clanMember['clanrank']],
        $bioLink,
        $clanMember['dragonkills'],
        $clanMember['clanjoindate']
    );
    array_push($memberRows, $currentRow);
	addnav('', $bioUri);
    if ($exitLoop === true) break;
}

output(sprintf($detailTemplate, join($memberRows)), true);
output('`^This Clan has a total of `$%s`^ dragon kills.`0`n', $totalDks);

page_footer();