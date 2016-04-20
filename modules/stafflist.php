<?php

function stafflist_getmoduleinfo()
{
    $info = [
        'name' => 'Staff List',
        'author'=> '`&`bStephen Kise`b',
        'version' => '0.1',
        'category' => 'Administrative',
        'description' =>
            'Add a list of all supporting members of your realm.',
        'download' => 'nope',
        'prefs' => [
            'rank' => 'What rank is this member?, range, 0, 10, 1| 0',
            'Be aware that higher numbers show first in the list!, note',
        ],
    ];
    return $info;
}

function stafflist_install()
{
    module_addhook('village');
    return true;
}

function stafflist_uninstall()
{
    return true;
}

function stafflist_dohook($hook, $args)
{
    switch ($hook) {
        case 'village':
            addnav($args['infonav']);
            addnav(translate_inline('Staff List'), 'runmodule.php?module=stafflist');
            break;
    }
    return $args;
}

function stafflist_run()
{
    $userPrefs = db_prefix('module_userprefs');
    $accounts = db_prefix('accounts');
    page_header('Staff List');
    villagenav();
    rawoutput(
        "<table class='stafflist'>
            <tr>
                <th>
                    Staff Member
                </th>
            </tr>"
    );
    $sql = db_query(
        "SELECT a.name AS name FROM $userPrefs AS u
        LEFT JOIN $accounts AS a ON a.acctid = u.userid
        WHERE u.modulename = 'stafflist'
        AND u.setting = 'rank'
        AND u.value <> 0
        ORDER BY u.value+0 DESC
        "
    );
    while ($row = db_fetch_assoc($sql)) {
        output(
            "<tr>
                <td>
                    %s
                </td>
            </tr>",
            $row['name'],
            true
        );
    }
    rawoutput("</table>");
    output(
        "`i`^%s`i",
        translate_inline(
            getsetting(
                'superuseryommessage',
                "Asking an admin for gems, gold, weapons, armor, or anything else which you have not earned will not be honored.  If you are experiencing problems with the game, please use the 'Petition for Help' link instead of contacting an admin directly."
            )
        )
    );
    page_footer();
}
