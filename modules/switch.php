<?php

function switch_getmoduleinfo()
{
    $info = [
        'name' => 'Switch Accounts',
        'author'=> '`&`bStephen Kise`b',
        'version' => '0.1',
        'category' => 'Miscellaneous',
        'description' =>
            'Switch your accounts on-the-fly with a quick click!',
        'download' => 'nope',
        'settings' => [
            'charstat' => 'Where should we place the logout and switch accounts?, text| Vital Info',
        ],
        'prefs' => [
            'accounts' => 'Accounts that this player has validated:, viewonly| []',
        ],
    ];
    return $info;
}

function switch_install()
{
    module_addhook('charstats');
    return true;
}

function switch_uninstall()
{
    return true;
}

function switch_dohook($hook, $args)
{
    switch ($hook) {
        case 'charstats':
            global $SCRIPT_NAME, $session;
            if ($SCRIPT_NAME == 'village.php' && $session['user']['specialinc'] == '') {
                addcharstat("Vital Info");
                addcharstat(
                    sprintf_translate(
                        "<a href='login.php?op=logout'>%s</a>",
                        "`%Log Out"
                    ),
                    sprintf_translate(
                        "<a href='runmodule.php?module=switch' style='font-weight: bold;'>%s</a>",
                        appoencode('`%Switch')
                    )
                );
                addnav('', 'login.php?op=logout');
                addnav('', 'runmodule.php?module=switch');
            }
            break;
    }
    return $args;
}

function switch_run()
{
    global $session;
    $op = httpget('op');
    $id = httpget('id');
    $accounts = db_prefix('accounts');
    $allAccounts = json_decode(get_module_pref('accounts'), true);
    page_header('Switch Accounts');
    switch ($op) {
        case 'add':
            addnav('Go back', 'runmodule.php?module=switch');
            output("`@Please enter the information of the account you want to add to your switch list. Note that this link is symbolic, meaning it will be added both ways!`n`n`0");
            rawoutput(
                "<form action='runmodule.php?module=switch&op=verify' method='POST'>
                <input type='text' name='login' placeholder='Login' />
                <input type='password' name='password' placeholder='Password' />
                <input type='submit' value='Create Link' />
                </form>"
            );
            addnav('', 'runmodule.php?module=switch&op=verify');
            break;
        case 'verify':
            $post = httpallpost();
            $post['password'] = md5(md5($post['password']));
            $post['login'] = filter_var($post['login'], FILTER_SANITIZE_STRING);
            $sql = db_query(
                "SELECT acctid, name, uniqueid, lastip
                FROM $accounts
                WHERE password = '{$post['password']}'
                AND login = '{$post['login']}'"
            );
            if (db_num_rows($sql) == 0) {
                addnav('Go back', 'runmodule.php?module=switch&op=add');
                output("`\$Sorry, no account was found with those credentials!");
                break;
            }
            else {
                $row = db_fetch_assoc($sql);
                addnav('Go back', 'runmodule.php?module=switch');
                if ($row['acctid'] == $session['user']['acctid']) {
                    output("`\$Sorry, but you cannot add a link to yourself!");
                    break;
                }
                output(
                    "`@Success! Adding a link between`^ %s `@and `^%s`@!`0",
                    $session['user']['name'],
                    $row['name']
                );
                $targetPref = json_decode(
                    get_module_pref('accounts', 'switch', $row['acctid']),
                    true
                );
                $targetPref = array_merge($targetPref, [$session['user']['acctid']]);
                set_module_pref(
                    'accounts',
                    json_encode($targetPref),
                    'switch',
                    $row['acctid']
                );
                $userPref = json_decode(get_module_pref('accounts'), true);
                $userPref = array_merge($userPref, [$row['acctid']]);
                set_module_pref('accounts', json_encode($userPref));
            }
            break;
        case 'remove':
            addnav('Go back', 'runmodule.php?module=switch');
            if (!in_array($id, $allAccounts)) {
                output("`\$Woops! That account is not linked to yours!");
                break;
            }
            $unsetId = array_search($id, $allAccounts);
            unset($allAccounts[$unsetId]);
            set_module_pref('accounts', json_encode($allAccounts));
            $targetPref = get_module_pref('accounts', false, $id);
            $targetPref = json_decode($targetPref, true);
            $key = array_search($session['user']['acctid'], $targetPref);
            unset($targetPref[$key]);
            set_module_pref('accounts', json_encode($targetPref), 'switch', $id);
            output("`@The link between your accounts has been destroyed!");
            break;
        case 'switch':
            require_once('lib/redirect.php');
            require_once('lib/checkban.php');
            if (!in_array($id, $allAccounts)) {
                addnav('Go back', 'runmodule.php?module=switch');
                output(
                    "`\$There was an error. We could not validate your link between these accounts!"
                );
                debuglog('tried to switch into an account they do not have access to!');
                break;
            }
            $sql = db_query("UPDATE $accounts SET loggedin = 0 WHERE acctid = '{$session['user']['acctid']}'");
            $sql = db_query("UPDATE $accounts SET loggedin = 1 WHERE acctid = '$id'");
            $sql = db_query("SELECT * FROM $accounts WHERE acctid = '$id'");
            $session['user'] = db_fetch_assoc($sql);
            $session['loggedin'] = true;
            $session['laston'] = date('Y-m-d H:i:s');
            $session['user']['laston'] = $session['laston'];
            redirect(($session['user']['restorepage']?:'news.php'));
            break;
        default:
            require_once('lib/villagenav.php');
            villagenav();
            addnav('Link an account', 'runmodule.php?module=switch&op=add');
            if (count($allAccounts) > 0) {
                output("`i`@Which account would you like to sign in to?`i`n`n`0");
                rawoutput(
                    "<table class='switchAccounts'>
                        <tr>
                            <th>
                                Ops
                            </th>
                            <th>
                                Name
                            </th>
                        </tr>
                    "
                );
                foreach ($allAccounts as $acctid) {
                    debug($acctid);
                    $sql = db_query(
                        "SELECT name
                        FROM $accounts
                        WHERE acctid = '$acctid'"
                    );
                    $row = db_fetch_assoc($sql);
                    output(
                        "<tr>
                            <td>
                                [<a href='runmodule.php?module=switch&op=remove&id=%s'>Del</a>]
                            </td>
                            <td>
                                <a href='runmodule.php?module=switch&op=switch&id=%s'>%s</a>
                            </td>
                        </tr>",
                        $acctid,
                        $acctid,
                        $row['name'],
                        true
                    );
                    addnav('', 'runmodule.php?module=switch&op=remove&id=' . $acctid);
                    addnav('', 'runmodule.php?module=switch&op=switch&id=' . $acctid);
                }
                rawoutput("</table>");
            }
            else {
                output(
                    "`@You need to add accounts! Click 'Link an account' to get started!"
                );
            }
            break;
    }
    page_footer();
}
