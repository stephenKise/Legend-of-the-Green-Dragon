<?php

require_once('common.php');
require_once('lib/villagenav.php');

villagenav();

if (httpget('detail') > 0) {
	require_once('lib/clan/detail.php');
} 
if (
    (int) $session['user']['clanrank'] == CLAN_APPLICANT
    && httpget('op') !== 'withdraw'
) {
	require_once('lib/clan/applicant.php');
}

switch (httpget('op')) {
    case 'list':
        require_once('lib/clan/list.php');
        break;
    case 'membership':
        require_once('lib/clan/membership.php');
        break;
    case 'motd':
        require_once('lib/clan/motd.php');
        break;
    case 'withdraw':
        require_once('lib/clan/withdraw.php');
        break;
    case 'withdrawconfirm':
        page_header('Clan Withdrawal');
        output('clan.withdraw_confirmation');
        addnav('Withdraw?');
        addnav('No', 'clan.php');
        addnav('!?Yes', 'clan.php?op=withdraw');
        page_footer();
        break;    
    default:
        require_once('lib/clan/default.php');
        break;
}
