<?php
declare(strict_types = 1);

function get_partner(int $player = 0) :string
{
    global $session;
    $accounts = db_prefix('accounts');
    if ($player <> 0) {
        $sql = db_query(
            "SELECT a.sex, b.name FROM $accounts AS a
            LEFT JOIN $accounts AS b ON a.marriedto = b.acctid
            WHERE a.acctid = '$player'"
        );
        $row  = db_fetch_assoc($sql);
        if ($row['name'] <> '') {
            $partner = $row['name'];
        }
        else {
            db_query(
                "UPDATE $accounts SET marriedto = '0'
                WHERE acctid = '$player'"
            );
            $partner = (
                $row['sex'] ?
                getsetting('bard', '`^Seth') :
                getsetting('barmaid', '`%Violet')
            );
        }
    }
    else {
        $sql = db_query(
            "SELECT name FROM $accounts
            WHERE acctid = '{$session['user']['marriedto']}'"
        );
        if ($row = db_fetch_assoc($sql)) {
            $partner = $row['name'];
        }
        else {
            $session['user']['marriedto'] = 0;
            $partner = (
                $session['user']['sex'] ?
                getsetting('bard', '`^Seth') :
                getsetting('barmaid', '`%Violet')
            );
        }
    }
    return $partner;
}
