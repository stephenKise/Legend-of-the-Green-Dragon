<?php

// TODO: 'collapse' module hooks could probably be removed when module handling is refactored
require_once('common.php');
require_once('lib/commentary.php');
require_once('lib/events.php');
require_once('lib/experience.php');

checkday();

loadNamespace('village');
$villageName = getsetting('villagename', LOCATION_FIELDS);
$innName = getsetting('innname', LOCATION_INN);
$validLocations = [];
$validLocations[$villageName] = 'village';
$validLocations = modulehook('validlocation', $validLocations);
// Set user location for invalid location name
if (!isset($validLocations[$session['user']['location']])) {
	$session['user']['location'] = $villageName;
}
// Get clean location name, so color codes are not stored for module hook names
$currentLocation = sanitize($session['user']['location']);

// Cache newest character
$accountsTable = db_prefix('accounts');
$sql = db_query_cached(
    "SELECT name, acctid
    FROM $accountsTable
    ORDER BY acctid DESC
    LIMIT 1",
    'newest-character'
);
$newCharacter = db_fetch_assoc($sql);

// Pull translations from the cache for local language, more manageable than repeated loadTranslation() calls
$i18n = $TRANSLATION_CACHE["{$language}.village"];
$i18n['title'] = sprintf($i18n['title'], $villageName);
$i18n['description'] = str_replace('`%', '`%%', $i18n['description']);
$i18n['description'] = sprintf($i18n['description'], $villageName, $villageName);
$i18n['new_character'] = sprintf($i18n['new_character'], $newCharacter['name']);
$i18n['new_character_data'] = $newCharacter;
$texts = modulehook('villagetext', $i18n);
$texts = modulehook("villagetext-{$session['user']['location']}", $texts);
$navs = $texts['navs'];
$navHeaders = $texts['nav_headers'];

page_header($texts['title']);

$skipDescription = handle_event('village');

// Failed to slay the dragon (?)
if ($session['user']['slaydragon'] == 1) {
	$session['user']['slaydragon'] = 0;
}

// Remove the dead players
if (!@$session['user']['alive']) {
	redirect('shades.php');
}

// Masters hunt down truant students
if (getsetting('automaster', 1) && $session['user']['seenmaster'] != 1) {
	$level = $session['user']['level'] + 1;
	$dks = $session['user']['dragonkills'];
	$requiredExp = exp_for_next_level($level, $dks);
	if (
        $session['user']['experience'] > $requiredExp &&
		$session['user']['level'] < 15
    ) {
		redirect('train.php?op=autochallenge');
	}
}

// Avoid a village event if browsing commentary
$postedCommentary = httppost('insertcommentary');
if (sizeof(httpallget()) <= 1 && !$postedCommentary) {
	if (module_events('village', getsetting('villagechance', 0)) != 0) {
		if (checknavs()) {
			page_footer();
            exit;
		}
        else {
			// Reset the special for good.
			$session['user']['specialinc'] = '';
			$session['user']['specialmisc'] = '';
			$skipDescription = true;
			httpset('op', '');
		}
	}
}

$navigation = [
    'gate' => [
        'forest' => 'forest.php',
        'pvp' => 'pvp.php',
        'logout' => 'login.php?op=logout',
        'mercenary' => 'mercenarycamp.php',
    ],
    'fight' => [
        'train' => 'train.php',
        'lodge' => 'lodge.php',
    ],
    'market' => [
        'weapons' => 'weapons.php',
        'armor' => 'armor.php',
        'bank' => 'bank.php',
        'gypsy' => 'gypsy.php',
    ],
    'tavern' => [
        'inn' => 'inn.php',
        'stables' => 'stables.php',
        'gardens' => 'gardens.php',
        'rock' => 'rock.php',
        'clan' => 'clan.php'
    ],
    'info' => [
        'news' => 'news.php',
        'list' => 'list.php',
        'hof' => 'hof.php',
        'faq' => 'petition.php?op=faq'
    ],
    'other' => [
        'preferences' => 'prefs.php'
    ],
];
if (!getsetting('enablecompanions', false)) unset($navigation['gate']['mercenary']);
if (!getsetting('pvp', true)) unset($navigation['gate']['pvp']);
if (!getsetting('allowclans', true)) unset($navigation['gate']['clan']);
if ($session['user']['superuser'] & SU_EDIT_COMMENTS) {
    $navigation['superuser']['moderate'] = 'moderate.php';
}
if ($session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO) {
    $navigation['superuser']['superuser'] = 'superuser.php';
}
if ($session['user']['superuser'] & SU_INFINITE_DAYS) {
    $navigation['superuser']['new_day'] = 'newday.php';
}
$navHeaders = [];
// Handle all possible navigation items
foreach ($navigation as $section => $links) {
    if (!array_key_exists($section, $navHeaders)) addnav($texts['nav_headers'][$section]);
    foreach ($links as $key => $uri) {
        if ($key !== 'faq') {
            addnav($texts['navs'][$key], $uri);
        }
        else {
            addnav($texts['navs'][$key], $uri, false, true);
        }
    }
}


// Display base description and current game time
modulehook('collapse{', ['name' => "village-desc-{$currentLocation}"]);
output($texts['description']);
modulehook('}collapse');
modulehook('collapse{', ['name' => "village-clock-{$currentLocation}"]);
output($texts['clock'], getgametime());
modulehook('}collapse');

// Hook for village being displayed, without an event
modulehook('village-desc', $texts);
modulehook("village-desc-{$currentLocation}", $texts);

// Handle newest player display
$newCharacterMsg = ($session['user']['acctid'] == $newCharacter['acctid']) ?
    $texts['new_character_is_user'] :
    sprintf($texts['new_character'], $newCharacter['name']);
modulehook('collapse{', ['name' => "village-newest-{$currentLocation}"]);

// Display newest character, add user editor navigation if user has SU_EDIT_USERS
if ($session['user']['superuser'] & SU_EDIT_USERS) {
    $editUserUri = "user.php?op=edit&userid={$newCharacter['acctid']}";
    $edit = loadTranslation('common.edit');
    $newCharacterMsg = "[<a href='$editUserUri'>$edit</a>] $newCharacterMsg";
    addnav('', $editUserUri);
}
output($newCharacterMsg, true);
modulehook('}collapse');

// Hook for all villages, and specific named villages
modulehook('village', $texts);
modulehook("village-{$currentLocation}", $texts);

$commentary = $texts['commentary'];
$args = modulehook('blockcommentarea', ['section' => $commentary['section']]);
if (!isset($args['block']) || $args['block'] != 'yes') {
    addcommentary();
	output($texts['talk']);
	commentdisplay(
        '',
        $commentary['section'],
        'Speak',
        25,
        $commentary['says'],
        $commentary['says']
    );
}

module_display_events('village', 'village.php');
page_footer();
