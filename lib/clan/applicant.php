<?php

$registrar = getsetting('clanregistrar', '`%Karissa');
$currentClanId = (int)$session['user']['clanid'];
$sql = db_query_cached(
    "SELECT clanname AS name,
     clanshort AS tag,
     clandesc AS description,
     clanmotd AS motd,
     clanid AS id,
     descauthor,
     motdauthor,
     customsay
     FROM $clansPrefix
     WHERE clanid = '$currentClanId'",
    "clan_data:$currentClanId",
    3600
);
$clanData = db_fetch_assoc($sql);

page_header('Clan Halls');

addnav('Clan Options');
output('`b`c`&Clan Halls`c`b');

if ($currentClanId > 0 && httpget('op') === false) {
    // applied for membership to a clan
    output(
        "clan.awaiting_approval",
        $registrar,
        $clanData['name']
    );
    addnav('List Clans', 'clan.php?op=list');
    addnav('Withdraw Application', 'clan.php?op=withdraw');
    page_footer();
}

switch (httpget('op')) {
    case 'apply':
        require_once 'lib/clan/apply.php';
        break;
    case 'list':
        require_once 'lib/clan/list.php';
        break;
    case 'new':
        require_once 'lib/clan/applicant_new.php';
        break;
    case 'withdraw':
        $mailPrefix = db_prefix('mail');
		$session['user']['clanid'] = 0;
		$session['user']['clanrank'] = CLAN_APPLICANT;
		$session['user']['clanjoindate'] = null;
		output(
            "clan.withdraw_application",
            $registrar,
            $clanData['clanname']
        );
        addnav('List Clans', 'clan.php?op=list');
		addnav('Create a new Clan', 'clan.php?op=new');
        break;
    default:
        output(
            "clan.introduction",
            $registrar
        );
        addnav('List Clans', 'clan.php?op=list');
		addnav('Create a new Clan', 'clan.php?op=new');
        modulehook('clan-enter');
        break;
}

page_footer();
