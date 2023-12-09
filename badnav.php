<?php

define('OVERRIDE_FORCED_NAV', true);
require_once('common.php');
require_once('lib/villagenav.php');
[
    'loggedin' => $sessionLoggedIn,
    'allowednavs' => $allowedNavs,
    'user' => [
        'alive' => $isAlive,
        'acctid' => $acctId,
        'location' => $currentLocation,
        'loggedin' => $loggedIn,
        'restorepage' => $restorePage
    ]
] = $session;

if (!$sessionLoggedIn || !$loggedIn) {
    $session = [];
	redirect('index.php');
    die();
}
$accOutput = file_get_contents("accounts-output/{$acctId}.html");
if (strpos($accOutput, '<!--CheckNewDay()-->') !== false) {
    checkday();
}
if (
    !is_array($allowedNavs) 
    || count($allowedNavs) == 0 
    || $accOutput == '' 
    || !isNavigationInOutput($session)
) {
    tlschema('badnav');
    $allowedNavs = [];
	page_header('Your Navs Are Corrupted');
	if ($isAlive) {
		villagenav();
		output(
            'Your navs are corrupted, please return to %s.',
		    $currentLocation
        );
	} else {
		addnav('Return to Shades', 'shades.php');
		output('Your navs are corrupted, please return to the Shades.');
	}
	page_footer();
} else {
    // Send back the previous scene, do not run scripts from previous navigation.
    echo $accOutput;
}
$session['debug'] = '';
$session['user']['allowednavs'] = $allowedNavs;
saveuser();