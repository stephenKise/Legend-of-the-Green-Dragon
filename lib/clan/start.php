<?php

page_header('Clan Halls');

addnav('Clan Options');

$op = httpget('op');
if ($op == '') {
    require_once('lib/clan/default.php');
} elseif ($op == 'motd') {
    require_once('lib/clan/motd.php');
} elseif ($op == 'membership') {
    require_once('lib/clan/membership.php');
} elseif ($op == 'withdrawconfirm') {
    output('clan.withdraw_confirmation');
    addnav('Withdraw?');
    addnav('No', 'clan.php');
    addnav('!?Yes', 'clan.php?op=withdraw');
} elseif ($op == 'withdraw') {
    require_once('lib/clan/withdraw.php');
}

page_footer();