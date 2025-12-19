<?php

require_once 'lib/commentary.php';
require_once 'lib/nltoappon.php';

$clanId = $session['user']['clanid'];
$clansPrefix = db_prefix('clans');
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
     WHERE clanid = '$clanId'",
    "clan_data:$clanId",
    3600
);
$clanData = db_fetch_assoc($selectedClanQuery);
[
    'name' => $clanName,
    'tag' => $clanTag,
    'description' => $clanDesc,
    'motd' => $clanMotd,
    'descauthor' => $clanDescAuthorId,
    'motdauthor' => $clanMotdAuthorId
] = $clanData;
    
page_header(full_sanitize("Clan Hall of $clanName"));

modulehook('collapse{', ['name'=>'clan_entry']);
output('clan.hall_default');
modulehook('}collapse');

$accountsPrefix = db_prefix('accounts');
if ($clanMotdAuthorId > 0 && $clanMotd > '') {
    $result = db_query_cached(
        "SELECT name FROM $accountsPrefix WHERE acctid = $clanMotdAuthorId",
        "clan_motd_author:$clanId",
        60 * 60 * 24
    );
    $row = db_fetch_assoc($result);
    output('clan.hall_current_motd', $row['name'], nltoappon($clanMotd), true);
}

commentdisplay(
    '',
    "clan-$clanId",
    'Speak',
    25,
    $clanData['customsay'] > '' ? $clanData['customsay'] : 'says'
);
modulehook('clanhall');

if ($clanDescAuthorId > 0 && $clanDesc > '') {
    $result = db_query_cached(
        "SELECT name FROM $accountsPrefix WHERE acctid = $clanDescAuthorId",
        "clan_desc_author:$clanId",
        60 * 60 * 24
    );
    $row = db_fetch_assoc($result);
    $clanDescAuthor = $row['name'];
    output('clan.hall_description', $clanDescAuthor, nltoappon($clanDesc), true);
    modulehook('collapse{', ['name'=>'clan_description']);
    modulehook('}collapse');
}

addnav('Clan Options');
if ($session['user']['clanrank'] >= CLAN_OFFICER) {
	addnav('Update MoTD / Clan Desc', 'clan.php?op=motd');
}
addnav('M?View Membership', 'clan.php?op=membership');
addnav('Withdraw From Your Clan', 'clan.php?op=withdrawconfirm');
addnav('List Clans', 'clan.php?op=list');

page_footer();