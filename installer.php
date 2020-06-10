<?php

define('ALLOW_ANONYMOUS', true);
define('OVERRIDE_FORCED_NAV', true);
define('IS_INSTALLER', true);
if (!file_exists('dbconnect.php')) {
    define('DB_NODB', true);
} else {
    include_once 'dbconnect.php';
}
require_once 'common.php';
require_once 'lib/all_tables.php';
require_once 'lib/tabledescriptor.php';
require_once 'lib/installer/installer_sqlstatements.php';
require_once 'lib/installer/installer_default_settings.php';
$stages = [
    '1. Introduction',
    '2. Server Credentials',
    '3. Deploy Server',
    '4. Account Creation',
    '5. Finish!'
];

$stage = 0;
if ((int) httpget('stage') > 0) {
    $stage = (int) httpget('stage');
}
if (!isset($session['stagecompleted'])) {
    $session['stagecompleted'] = -1;
}
if ($stage > $session['stagecompleted'] + 1) {
    $stage = $session['stagecompleted'];
}
if (!isset($session['dbinfo'])) {
    $session['dbinfo'] = [
        'DB_HOST' => '',
        'DB_USER' => '',
        'DB_PASS' => '',
        'DB_NAME' => ''
    ];
}

page_header("LotGD Installer &#151; %s", $stages[$stage]);
switch ($stage) {
    case 0:
    case 1:
    case 2:
    case 3:
        include_once "lib/installer/Stage$stage.php";
        if ($stage == 2) {
            if (!$endNavigation) {
                output("`n`n`QAll complete! Continue onto the next stage!");
            } else {
                output(
                    "`n`n`4Please remove anything created by the installer and
                    try again after following any suggestions provided."
                );
            }
        }
        break;
    default:
        include_once 'lib/installer/Stage0.php';
        break;
}


if (!$authenticate) {
    if ($session['user']['loggedin']) {
        addnav('Back to the game', $session['user']['restorepage']);
    }
    addnav('Install Stages');
    if ($endNavigation) {
        $maxStage = $session['stagecompleted'];
    } else {
        $maxStage = $session['stagecompleted'] + 1;
    }
    debug($maxStage, true);
    for ($x = 0; $x <= min(count($stages) - 1, $maxStage); $x++) {
        if ($x == $stage) {
            $stages[$x] = "`^{$stages[$x]} <----";
        }
        addnav($stages[$x], "installer.php?stage=$x");
    }
}
page_footer(false);
