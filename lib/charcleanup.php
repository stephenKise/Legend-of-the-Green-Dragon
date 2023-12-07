<?php
declare(strict_types=1);

function char_cleanup(int $id, int $type): bool
{
    $return = modulehook(
        'delete_character',
        [
            'acctid' => $id,
            'deltype' => $type,
            'dodel' => true 
        ]
    );
    if (!$return['dodel']) {
        return false;
    }
    $accounts = db_prefix('accounts');
    $commentary = db_prefix('commentary');
    $mail = db_prefix('mail');
    $news = db_prefix('news');
    $clans = db_prefix('clans');
    db_query("DELETE FROM $commentary WHERE author = '$id'");
    db_query("DELETE FROM $mail WHERE msgto = '$id' OR msgfrom = '$id'");
    db_query("DELETE FROM $news WHERE accountid = '$id'");
    module_delete_userprefs($id);
    $leader = CLAN_LEADER;
    $applicant = CLAN_APPLICANT;
    $sql = db_query("SELECT clanrank, clanid FROM $accounts WHERE acctid = '$id'");
    $row = db_fetch_assoc($sql);
    if ($row['clanid'] != 0 && $row['clanrank'] == $leader) {
        $cid = $row['clanid'];
        $sql = db_query(
            "SELECT acctid, clanrank
            FROM $accounts
            WHERE clanid = '{$row['clanid']}'
            AND clanrank > '$applicant'
            AND acctid != '$id'
            ORDER BY clanrank DESC, clanjoindate"
        );
        if (db_num_rows($sql)) {
            $row = db_fetch_assoc($sql);
            if ($row['clanrank'] != $leader) {
                db_query("UPDATE $accounts SET clanrank = '$leader' WHERE acctid = {$row['acctid']}");
            }
        }
        else {
            db_query("DELETE FROM $clans WHERE clanid = '{$row['clanid']}'");
            db_query(
                "UPDATE $accounts
                SET clanid = '0',
                clanrank = '0',
                clanjoindate = '0000-00-00 00:00:00'
                WHERE clanid = '{$row['clanid']}'"
            );
        }
    }
    $sql = db_query("DELETE FROM $accounts WHERE acctid = '$id'");
    if (!$sql) {
        return false;
    }
    return true;
}

