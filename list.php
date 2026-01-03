<?php

define('ALLOW_ANONYMOUS',true);

require_once 'common.php';
require_once 'lib/http.php';
require_once 'lib/villagenav.php';

tlschema('list');

page_header('Characters List');

$accountsPrefix = db_prefix('accounts');
$loginTimeout = getsetting('LOGINTIMEOUT', 900);
$loginTimeoutString = date('Y-m-d H:i:s', strtotime("-$loginTimeout seconds"));
if ($session['user']['loggedin']) {
	checkday();
	if ($session['user']['alive']) {
		villagenav();
	} else {
		addnav('Return to the Graveyard', 'graveyard.php');
	}
	addnav('Currently Online','list.php');
	if ($session['user']['clanid'] > 0) {
		addnav('Online Clan Members', 'list.php?op=clan');
	}
} else {
	addnav('Login Screen','index.php');
	addnav('Currently Online','list.php');
}

$playersPerPage = 50;

$sql = "SELECT count(acctid) AS c FROM $accountsPrefix WHERE locked = 0";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$totalPlayers = $row['c'];

$op = httpget('op');
$page = httpget('page');
$search = '';
$limit = '';

if ($op == 'search') {
	$search='%';
	$name = httppost('name');
	for ($x = 0; $x < strlen($name); $x++) {
		$search .= substr($name, $x, 1) . '%';
	}
	$search = " AND name LIKE '" . addslashes($search) . "' ";
} 
else {
	$pageOffset = (int)$page;
	if ($pageOffset > 0) $pageOffset--;
	$pageOffset *= $playersPerPage;
	$from = $pageOffset + 1;
	$to = min($pageOffset + $playersPerPage, $totalPlayers);

	$limit = " LIMIT $pageOffset, $playersPerPage ";
}
addnav('Pages');
for ($i = 0; $i < $totalPlayers; $i += $playersPerPage) {
	$pageNumber = $i / $playersPerPage + 1;
	if ($page == $pageNumber) {
		addnav(
            sprintf_translate(
                " ?`b`#Page %s`0 (%s-%s)`b",
                $pageNumber,
                $i+1,
                min($i + $playersPerPage, $totalPlayers)
            ),
            "list.php?page=$pageNumber"
        );
	}
    else {
		addnav(
            sprintf_translate(
                " ?Page %s (%s-%s)",
                $pageNumber,
                $i + 1,
                min($i + $playersPerPage, $totalPlayers)
            ),
            "list.php?page=$pageNumber"
        );
	}
}

if ($page == '' && $op == '') {
	$title = translate_inline('Characters Currently Online');
	$sql = "SELECT acctid, name, login, alive, location, race, sex, level, laston, loggedin
		FROM $accountsPrefix
		WHERE locked = 0
		AND loggedin = 1
		AND laston > '$loginTimeoutString'
		ORDER BY level DESC, dragonkills DESC, login ASC";
	$result = db_query_cached($sql, 'list_characters_online', 60);
}
else if ($op=='clan') {
	$title = translate_inline('Clan Members Online');
	$sql = "SELECT acctid, name, login, alive, location, race, sex, level, laston, loggedin
        FROM $accountsPrefix
        WHERE locked = 0
        AND loggedin = 1
        AND laston > '$loginTimeoutString'
        AND clanid = '{$session['user']['clanid']}'
        ORDER BY level DESC, dragonkills DESC, login ASC";
	$result = db_query($sql);
}
else {
	if ($totalPlayers > $playersPerPage && $op != 'search') {
		$title = sprintf_translate(
            'Character List (Page %s: %s-%s of %s)',
            $pageOffset / $playersPerPage + 1,
            $from,
            $to,
            $totalPlayers
        );
	}
    else {
		$title = sprintf_translate('Character List');
	}
	rawoutput(tlbutton_clear());
	$sql = "SELECT acctid, name, login, alive, hitpoints, location, race, sex, level, laston, loggedin
        FROM $accountsPrefix
        WHERE locked = 0
        $search
        ORDER BY level DESC, dragonkills DESC, login ASC
        $limit";
	$result = db_query($sql);
}

if ($session['user']['loggedin']) {
	$searchLabel = translate_inline('Search by name: ');
	$searchBtn = translate_inline('Search');

	rawoutput(
        "<form action='list.php?op=search' method='POST'>
            $searchLabel
            <input name='name'>
            <input type='submit' class='button' value='$searchBtn'>
        </form>");
	addnav('','list.php?op=search');
}

$max = db_num_rows($result);
if ($max > getsetting('maxlistsize', 100)) {
	$max = getsetting('maxlistsize', 100);
	output(
        '`$Too many names match that search. Showing only the first %s.`0`n',
        $max
    );
}

if ($page == '' && $op == '') {
	$title .= sprintf_translate(': %s', $max);
}

$alive = translate_inline('Alive');
$level = translate_inline('Level');
$name = translate_inline('Name');
$loc = translate_inline('Location');
$race = translate_inline('Race');
$sex = translate_inline('Sex');
$lastOn = translate_inline('Last On');
$writeMail = translate_inline('Write Mail');
$online = translate_inline('`#(Online)');
$female = translate_inline('`%Female`0');
$male = translate_inline('`!Male`0');

output_notl("`c`b$title`b");
rawoutput(
    "<table class='list-warriors' border = 0 cellpadding = 2 cellspacing = 1>
        <tr class = 'trhead'>
        <td>$alive</td>
        <td>$level</td>
        <td>$name</td>
        <td>$loc</td>
        <td>$race</td>
        <td>$sex</td>
        <td>$lastOn</td>
    </tr>"
);

$rowCount = 0;
foreach ($result as $row) {
    $rowClass = $rowCount % 2 ? 'trdark' : 'trlight';
    $rowCount++;
    $rawLogin = rawurlencode($row['login']);
    $mailLink = "mail.php?op=write&to=$rawLogin";
    $mailString = "<a
        href = '$mailLink'
        target = '_blank'
        onClick = '" . popup($mailLink) . "; return false;'
        >
            <img
                src='images/newscroll.GIF'
                width = '16'
                height = '16'
                alt='$writeMail'
                border = '0'
            />
        </a>";
    $bioLink = "bio.php?char={$row['acctid']}";
    $bioString = "<a href='$bioLink'> {$row['name']} </a>";
	$userLoggedIn = (date('U') - strtotime($row['laston']) < $loginTimeout && $row['loggedin']);
	$lastOnDate = relativedate($row['laston']);
	$aliveStatus = translate_inline($row['alive'] ? '`@Yes`0' : '`$No`0');
    output(
        sprintf_translate(
            "<tr class='%s'>
                <td>%s</td>
                <td>`^%s`0</td>
                <td>%s %s</td> 
                <td>%s %s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
            </tr>",
            $rowClass,
            $aliveStatus,
            $row['level'],
            $session['user']['loggedin'] !== false ? $mailString : '',
            $session['user']['loggedin'] !== false ? $bioString : $row['name'],
            $row['location'],
            $userLoggedIn ? $online : '',
            $row['race'] ? $row['race'] : RACE_UNKNOWN,
            $row['sex'] ? $female : $male,
            $lastOnDate
        ),
        true
    );
    addnav('', $bioLink);
}
rawoutput("</table>");
output_notl('`c');
page_footer();