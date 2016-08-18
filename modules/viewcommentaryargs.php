<?php

function viewcommentaryargs_getmoduleinfo()
{
    $info = [
        'name' => 'Additional Commentary Args',
        'author'=> '`&`bStephen Kise`b',
        'version' => '0.1',
        'category' => 'Commentary',
        'description' =>
            'Adds additional arguments for the &quot;viewcommentary&quot; hook. ',
        'download' => 'nope',
    ];
    return $info;
}

function viewcommentaryargs_install()
{
    module_addhook('blockcommentarea');
    module_addhook_priority('viewcommentary', '1');
    return true;
}

function viewcommentaryargs_uninstall()
{
    return true;
}

function viewcommentaryargs_dohook($hook, $args)
{
    global $currentCommentaryArea;
    switch ($hook) {
        case 'blockcommentarea':
            $currentCommentaryArea = $args['section'];
            break;
        case 'viewcommentary':
            $accounts = db_prefix('accounts');
            $commentary = db_prefix('commentary');
            preg_match("/bio.php\?char=(.*)&ret/", $args['commentline'], $matches);
            $acctid = filter_var($matches[1], FILTER_SANITIZE_NUMBER_INT);
            $sql = db_query_cached(
                "SELECT login, name FROM $accounts WHERE acctid = $acctid",
                "commentary-author_name-$acctid",
                86400
            );
            $row = db_fetch_assoc($sql);
            $name = $row['name'];
            $login = $row['login'];
            $temp = explode($row['name'], $args['commentline']);
            $temp = str_replace('`3 says, "`#', '', $temp[1]);
            $temp = str_replace('`3"', '', $temp);
            $temp = str_replace('/me', '', $temp);
            $temp = str_replace(':', '', $temp);
            $temp = str_replace('</a>', '', $temp);
            $temp = full_sanitize($temp);
            $temp = addslashes(implode('%', str_split(trim($temp))));
            $sql = db_query(
                "SELECT commentid, comment, postdate FROM $commentary
                WHERE comment LIKE '%$temp%'
                AND section = '$currentCommentaryArea'"
            );
            $row = db_fetch_assoc($sql);
            $args = [
                'commentline' => $args['commentline'],
                'section' => $currentCommentaryArea,
                'commentid' => $row['commentid'],
                'comment' => $row['comment'],
                'author_acctid' => $acctid,
                'author_login' => $login,
                'author_name' => $name,
                'date' => $row['postdate']
            ];
            unset($row);
            unset($temp);
            break;
    }
    return $args;
}
