<?php

function get_partner($player = false)
{
    global $session;
    if ($player === false) {
    }
    else {
        if ($session['user']['marriedto'] == INT_MAX) {
            if ($session['user']['sex'] == SEX_MALE) {
                $partner = getsetting('bard', '`^Seth');
            }
            else {
                $partner = getsetting('barmaid', '`%Violet');
            }
        }
        else {
            $accounts = db_prefix('accounts');
            $sql = db_query(
                "SELECT name FROM $accounts
                WHERE acctid = '{$session['user']['marriedto']}'"
            );
            if ($row = db_fetch_assoc($sql)) {
                $partner = $row['name'];
            }
            else {
                $session['user']['marriedto'] = 0;
                if ($session['user']['sex'] == SEX_MALE) {
                    $partner = getsetting('bard', '`^Seth');
                }
                else {
                    $partner = getsetting('barmaid', '`%Violet');
                }
            }
        }
    }
    return $partner;
}
