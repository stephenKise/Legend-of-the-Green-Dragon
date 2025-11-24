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
    "`7You approach %s`7 and inquire about starting a new Clan. She tells you
     that there are three requirements to starting a Clan. You Clan must have a
     name no longer than 50 characters, an abbreviated name no longer than 5
     characters, and pay a fee of `@%s `^gold `7and `@%s `%gems`7.`n`n",
    $registrar,
    $requiredGold,
    $requiredGems
);
addnav('Return to the Lobby', 'clan.php');

// @todo: Create some workaround in the future so that all output() functions are preloaded
// in the database or filesystem.
// This seems to be here originally so that admins can translate easier.
$improperFundsMsg = translate_inline(
    "\"`5Since you do not have proper payment, I cannot allow you to
     establish a Clan here,`7\" she says.`n`n"
);
$clanFormMsg = translate_inline(
    "\"`5If you're ok with these requirements, please fill the following
     form,`7\" she says, handing you a sheet of paper.`n`n"
);
if ($userGold < $requiredGold || $userGems < $requiredGems) {
	output_notl($improperFundsMsg);
} else {
	output_notl($clanFormMsg);
    createClanForm();
}
page_footer();