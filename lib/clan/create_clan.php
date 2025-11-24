<?php

require_once 'lib/clan/func.php';


['clan_name' => $submittedName,'clan_tag' => $submittedTag] = httpallpost();
$errorMsgs = [
    translate_inline(
        "%s`7 looks over your form but quickly informs you that your clan
         name must consist only of letters, spaces, apostrophes, or
         dashes. Also, your short name can consist only of letters. She
         hands you a blank form."
    ),
    translate_inline(
        "%s`7 looks over your form but quickly stops to remind you, \"`5A
         name no longer than 50 characters, and an abbreviate name no
         longer than 5 characters. Here is another blank form to try
         again.`7\""
    ),
    translate_inline(
        "%s`7 looks over your form but informs you that the desired Clan
         name or tag is already taken, and hands you a blank form."
    ),
];

$clanName = stripslashes($submittedName);
$clanName = full_sanitize($clanName);
$clanName = preg_replace("'[^[:alpha:] \\'-]'", '', $clanName);
$clanName = addslashes($clanName);
httppostset('clan_name', $clanName);

$clanTag = full_sanitize($submittedTag);
$clanTag = preg_replace("'[^[:alpha:]]'", '', $clanTag);
httppostset('clan_tag', $clanTag);

// Name/tag has special characters, require a clean name/tag
if ($clanName != $submittedName || $clanTag != $submittedTag) {
    output_notl($errorMsgs[0], $registrar);
    createClanForm($clanName, $clanTag);
    addnav('Return to the Lobby', 'clan.php');
    page_footer();
}

// Name/tag is too short or too long
if (
    strlen($clanName) < 1
    || strlen($clanName) > 50
    || strlen($clanTag) < 1
    || strlen($clanTag) > 5
) {
    output_notl($errorMsgs[1], $registrar);
    createClanForm($clanName, $clanTag);
    addnav('Return to the Lobby', 'clan.php');
    page_footer();
}

// Clan exists with requested name/tag
$result = db_query(
    "SELECT * FROM $clansPrefix
    WHERE clanname = '$clanName'
    OR clanshort = '$clanTag'"
);
if (db_num_rows($result) > 0) {
    output_notl($errorMsgs[2], $registrar);
    createClanForm($clanName, $clanTag);
    addnav('Return to the Lobby', 'clan.php');
    page_footer();
}

$userId = $session['user']['acctid'];
$moduleArgs = [
    'submitted_name' => $submittedName,
    'submitted_tag' => $submittedTag,
    'clan_name' => $clanName,
    'clan_tag' => $clanTag,
    'applicant' => $session['user'],
    'applicant_id' => $userId,
    'blocked' => false,
    'block_msg' => '',
];
$moduleArgs = modulehook('process-createclan', $moduleArgs);
// Module has blocked clan creation
if ($moduleArgs['blocked'] === true) {
    output_notl(sprintf_translate($moduleArgs['block_msg']));
    createClanForm($clanName, $clanTag);
    addnav('Return to the Lobby', 'clan.php');
    page_footer();
}

// @todo: Add default 'customsay' to the clan table, in lib/all_tables.php
db_query(
    "INSERT INTO $clansPrefix (clanname, clanshort, customsay, motdauthor, descauthor)
    VALUES ('$clanName', '$clanTag', 'says', $userId, $userId )"
);
$id = db_insert_id();
$session['user']['clanid'] = $id;
$session['user']['clanrank'] = CLAN_FOUNDER;
$session['user']['clanjoindate'] = date('Y-m-d H:i:s');
$session['user']['gold'] -= $requiredGold;
$session['user']['gems'] -= $requiredGems;
output(
    "%s`7 looks over your form, and finding that everything seems to be in
    order, she takes your fees, stamps the form \"`\$APPROVED`7\" and
    files it in a drawer.`n`n Congratulations, you've created a new clan
    named `^%s`7!",
    $registrar,
    stripslashes($clanName)
);
addnav('Enter your Clan Hall', 'clan.php');
debuglog(
    "created <$clanTag> Clan (-$requiredGold gold, -$requiredGems gems)"
);
page_footer();