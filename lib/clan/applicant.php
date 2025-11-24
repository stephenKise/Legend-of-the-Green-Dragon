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
        "`7You approach `%%s`7 who smiles at you, but lets you know that
         your application to `^%s`7 hasn't been accepted yet.",
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
            "`7You tell `%%s`7 that you're not interested in joining %s. ",
            $registrar,
            $clanData['clanname']
        );
		output(
            "She reaches into her desk, withdraws your application, and
             tears it up. \"`5You wouldn't have been happy there anyhow, I
             don't think,`7\" as she tosses the shreds in her trash can."
        );
        addnav('List Clans', 'clan.php?op=list');
		addnav('Create a new Clan', 'clan.php?op=new');
        break;
    default:
    	output(
            "`7You stand in the center of a great marble lobby filled with pillars.
             All around the walls of the lobby are various doors which lead to
             various clan halls. The doors each possess a variety of intricate
             mechanisms which are obviously elaborate locks designed to be opened
             only by those who have been educated on how to operate them. Nearby,
             you watch another warrior glance about nervously to make sure no one
             is watching before touching various levers and knobs on the door. With
             a large metallic \"chunk\" the lock on the door disengages, and the
             door swings silently open, admitting the warrior before slamming shut.
             `n`n"
        );
        output(
            "In the center of the lobby sits a highly polished desk, behind which
             sits `%%s`7, the clan registrar. She can take your filing for a new
             clan, or accept your application to an existing clan.`n`n",
            $registrar
        );
        addnav('List Clans', 'clan.php?op=list');
		addnav('Create a new Clan', 'clan.php?op=new');
        modulehook('clan-enter');
        break;
}

page_footer();
