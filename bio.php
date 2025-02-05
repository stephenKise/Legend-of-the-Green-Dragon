<?php

require_once('common.php');
require_once('lib/sanitize.php');

tlschema('bio');
checkday();

$ret = httpget('ret');
if ($ret == '') {
	$return = '/list.php';
} else {
	$return = cmd_sanitize($ret);
}
$return = substr($return, strrpos($return, '/') + 1);
tlschema('nav');
addnav('Return');
if ($ret == '' || $return == 'list.php') {
    addnav('Return to the warrior list', $return);
} else {
    addnav('Return whence you came', $return);
}
tlschema();

$char = httpget('char');
//Legacy support
if (is_numeric($char)) {
	$where = "acctid = $char";
} else {
	$where = "login = '$char'";
}

$accountsPrefix = db_prefix('accounts');
$clansPrefix = db_prefix('clans');
$result = db_query(
    "SELECT login, name, level, sex, title, specialty, hashorse, acctid,
        resurrections, bio, dragonkills, race, clanname, clanshort, clanrank,
        $accountsPrefix.clanid, laston, loggedin
    FROM $accountsPrefix
    LEFT JOIN $clansPrefix
    ON $accountsPrefix.clanid = $clansPrefix.clanid
    WHERE $where;"
);
$target = db_fetch_assoc($result);
if (!$target) {
	page_header('Invalid Character');
	output("This character is already deleted.");
	page_footer();
}
$target['login'] = rawurlencode($target['login']);
$id = $target['acctid'];
$target['return_link'] = $return;

page_header("Character Biography: %s", full_sanitize($target['name']));
modulehook('biotop', $target);
if ($session['user']['superuser'] & SU_EDIT_USERS) {
    addnav('Superuser');
    addnav("Edit User", "user.php?op=edit&userid=$id");
}

output("`^Biography for %s`^.", $target['name']);
// This really makes no sense, there is no override on navigation.
// @TODO: Remove this if statement.
if ($session['user']['loggedin']) {
    $write = translate_inline('Write Mail');
    $login = $target['login'];
    $mailLink = "mail.php?op=write&to=$login";
    $popup = popup($mailLink);
    $img = 'images/newscroll.GIF';
    rawoutput(
        "<a href='$mailLink' target='_blank' onClick='$popup;return false;'>
        <img src='$img' width='16' height='16' alt='$write' border='0'>
        </a>"
    );
}
output_notl("`n`n");

if ($target['clanname'] > '' && getsetting('allowclans', false)) {
    $ranks = [
        CLAN_APPLICANT => '`!Applicant`0',
        CLAN_MEMBER => '`#Member`0',
        CLAN_OFFICER => '`^Officer`0',
        CLAN_LEADER => '`&Leader`0',
        CLAN_FOUNDER => '`$Founder`0'
    ];
    $ranks = modulehook(
        'clanranks',
        ['ranks' => $ranks, 'clanid' => $target['clanid']]
    );
    tlschema('clans');
    $ranks = translate_inline($ranks['ranks']);
    tlschema();
    output(
        "`@%s`2 is a %s`2 to `%%s`2`n",
        $target['name'],
        $ranks[$target['clanrank']],
        $target['clanname']
    );
}

output("`^Title: `@%s`n",$target['title']);
output("`^Level: `@%s`n",$target['level']);
$loggedIn = false;
if (
    $target['loggedin'] 
    && (date('U') - strtotime($target['laston']) < getsetting('LOGINTIMEOUT', 900))
) {
    $loggedIn = true;
}
$status = translate_inline($loggedIn ? '`#Online`0' : '`$Offline`0');
output("`^Status: %s`n", $status);

output("`^Resurrections: `@%s`n", $target['resurrections']);

$race = $target['race'];
if (!$race) $race = RACE_UNKNOWN;
tlschema('race');
$race = translate_inline($race);
tlschema();
output("`^Race: `@%s`n", $race);

$genders = ['Male', 'Female'];
$genders = translate_inline($genders);
output("`^Gender: `@%s`n", $genders[$target['sex']]);

$specialties = modulehook(
    'specialtynames',
    ['' => translate_inline('Unspecified')]
);
if (isset($specialties[$target['specialty']])) {
	output("`^Specialty: `@%s`n", $specialties[$target['specialty']]);
}

$mountsPrefix = db_prefix('mounts');
$hasHorse = $target['hashorse'];
$result = db_query_cached(
    "SELECT * FROM $mountsPrefix WHERE mountid='$hasHorse';",
    "mountdata-$hasHorse",
    3600
);
$mountData = db_fetch_assoc($result);
$mountData['acctid'] = $target['acctid'];
$mountData = modulehook('bio-mount', $mountData);
$none = translate_inline('`iNone`i');
if (!isset($mountData['mountname']) || $mountData['mountname'] == '') {
    $mountData['mountname'] = $none;
}
output("`^Creature: `@%s`0`n", $mountData['mountname']);

modulehook('biostat', $target);
if ($target['dragonkills'] > 0)
    output("`^Dragon Kills: `@%s`n", $target['dragonkills']);
if ($target['bio'] > '')
    output("`^Bio: `@`n%s`n", soap($target['bio']));

modulehook('bioinfo', $target);
output("`n`^Recent accomplishments (and defeats) of %s`^", $target['name']);
$newsPrefix = db_prefix('news');
$result = db_query(
    "SELECT *
    FROM $newsPrefix
    WHERE accountid={$id}
    ORDER BY newsdate DESC, newsid ASC
    LIMIT 100"
);

$odate = '';
tlschema('news');
while ($row = db_fetch_assoc($result)) {
    tlschema($row['tlschema']);
    if ($row['arguments'] > '') {
        $arguments = [];
        $base_arguments = json_decode($row['arguments']);
        array_push($arguments, $row['newstext']);
        foreach ($base_arguments as $key => $val) {
            array_push($arguments, $val);
        }
        $news = call_user_func_array('sprintf_translate', $arguments);
        rawoutput(tlbutton_clear());
    } else {
        $news = translate_inline($row['newstext']);
        rawoutput(tlbutton_clear());
    }
    tlschema();
    if ($odate != $row['newsdate']) {
        output_notl(
            '`n`b`@%s`0`b`n',
            date('D, M d', strtotime($row['newsdate']))
        );
        $odate = $row['newsdate'];
    }
    output_notl("`@$news`0`n");
}
tlschema();

modulehook('bioend', $target);
page_footer();
