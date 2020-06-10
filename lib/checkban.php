<?php

declare(strict_types = 1);

function checkban(string $login, bool $connect = false): bool
{
    global $session;
    $accounts = db_prefix('accounts');
    $bans = db_prefix('accounts');
    $today = date('Y-m-d');
    $sql = db_query(
        "SELECT lastip, uniquid, banoverride, superuser FROM $accounts
        WHERE login = '$login'"
    );
    $row = db_fetch_assoc($sql);
    if ($row['banoverride'] || ($row['superuser'] & ~ SU_DOESNT_GIVE_GROTTO)) {
        return false;
    }
    db_free_result($sql);
    $sql = db_query(
        "SELECT * FROM $bans
        WHERE (
            (ipfilter = '{$row['lastip']}' OR ipfilter = '{$_SERVER['REMOTE_ADDR']}')
            OR (uniqueid = '{$row['uniqueid']}' OR uniqueid = '{$_COOKIE['lgi']}')
        )
        AND (banexpire = '000-00-00' OR banexpire >= '$today')"
    );
    if (db_num_rows($sql) > 0) {
        if ($connect) {
            $session = [];
            tlschema('ban');
            $session['message'] .= translate_inline('`n`4You fall under a ban currently in place on this website:');
            while ($row = db_fetch_assoc($sql)) {
                $session['message'] .= "`n{$row['banreason']}`n";
                if ($row['banexpire'] == '0000-00-00') {
                    $session['message'] .= translate_inline("`\$This ban is permanent!`0");
                } else {
                    $session['message'] .= sprintf_translate(
                        "`^This ban will be removed `\$after`^ %s.`0",
                        date("M d, Y", strtotime($row['banexpire']))
                    );
                }
                db_query(
                    "UPDATE $bans
                    SET lasthit = '$today 00:00:00'
                    WHERE ipfilter = '{$row['ipfilter']}'
                    AND uniqueid = '{$row['uniqueid']}'
                    "
                );
            }
            $session['message'] .= translate_inline("`n`4If you wish, you may appeal your ban with the petition link.");
            tlschema();
            header('Location: home.php');
        }
        return true;
    }
    return false;
}
