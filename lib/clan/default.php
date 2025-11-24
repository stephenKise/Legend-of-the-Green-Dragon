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
output(
    "Having pressed the secret levers and turned the secret knobs on the lock
     of the door to your clan's hall, you gain entrance and chat with your clan
     mates.`n`n"
);
modulehook('}collapse');

$accountsPrefix = db_prefix('accounts');
if ($clanMotdAuthorId > 0 && $clanMotd > '') {
    $result = db_query_cached(
        "SELECT name FROM $accountsPrefix WHERE acctid = $clanMotdAuthorId",
        "clan_motd_author:$clanId",
        60 * 60 * 24
    );
    $row = db_fetch_assoc($result);
	rawoutput('<div style="margin-left: 15px; padding-left: 15px;">');
	output('`b`&Current MoTD:`b `#by %s`0`n', $row['name']);
	output_notl(nltoappon($clanMotd));
	rawoutput('</div><br />');
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
    modulehook('collapse{', ['name'=>'clan_description']);
    output('`n`n`&`bCurrent Description:`b `#by %s`0`n', $clanDescAuthor);
    output_notl(nltoappon($clanDesc));
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