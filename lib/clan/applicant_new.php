<?php

require_once 'lib/clan/func.php';

$apply = httpget('apply');
['gems' => $userGems, 'gold' => $userGold] = $session['user'];
$clansPrefix = db_prefix('clans');

// Changed goldtostartclan, etc for gems to clan_gold_requirement, clan_gems_requirement
$requiredGold = getsetting('clan_gold_req', 10000);
$requiredGems = getsetting('clan_gems_req', 100);
// Form has been submitted, process the Clan creation
if ($apply == 1) {
    require_once 'lib/clan/create_clan.php';
}

output(
    "clan.create_clan_requirements",
    $registrar,
    $requiredGold,
    $requiredGems
);
addnav('Return to the Lobby', 'clan.php');

if ($userGold < $requiredGold || $userGems < $requiredGems) {
	output('clan.creation_improper_funds');
} else {
	output('clan.creation_form_instructions');
    createClanForm();
}
page_footer();