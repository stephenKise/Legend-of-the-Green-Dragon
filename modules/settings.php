<?php

/**
 * Settings
 * 
 * Provides a more organized preference system. Allows admins to restructure
 * their settings layout easily.
 * 
 * @author Stephen Kise
 * @todo Fix the template preference and cookie manipulation.
 */

function settings_getmoduleinfo()
{
    $info = [
        'name' => 'Settings',
        'author'=> '`&`bStephen Kise`b',
        'version' => '0.1',
        'category' => 'Miscellaneous',
        'description' =>
            'A more organized preference system.',
        'download' => 'nope',
    ];
    // is_module_active() apparently only returns true after the module has been
    // encoutered... Just click 'reinstall' to quickly see the settings.
    if (is_module_active('settings') && $info['settings']['rewrite'] == '') {
        $userprefs = db_prefix('module_userprefs');
        $modules = db_prefix('modules');
        $sql = db_query(
            "SELECT DISTINCT mu.modulename, mu.setting, m.formalname
            FROM $userprefs AS mu
            LEFT JOIN $modules AS m ON m.modulename = mu.modulename
            WHERE setting LIKE 'user_%'"
        );
        $fill = [];
        while ($row = db_fetch_assoc($sql)) {
            $fill["{$row['modulename']}__{$row['setting']}"] = $row['formalname'];
        }
        $info['settings']['rewrite'] = 'Rewrite condition for module settings, textarea| ' . json_encode($fill);
    }
    return $info;
}

function settings_install()
{
    module_addhook('footer-news');
    module_addhook('village');
    module_addhook('footer-modules');
    return true;
}

function settings_uninstall()
{
    return true;
}

function settings_dohook($hook, $args)
{
    switch ($hook) {
        case 'footer-news':
        case 'village':
            if ($hook == 'village') {
                addnav($args['othernav']);
            }
            else {
                addnav('News');
            }
            addnav('*?Settings*', 'runmodule.php?module=settings');
            blocknav('prefs.php');
            break;
        case 'footer-modules':
            if ((httpget('cat') != '' && httpget('module') != '') || is_array(httppost('module'))) {
                $userprefs = db_prefix('module_userprefs');
                $modules = db_prefix('modules');
                $sql = db_query(
                    "SELECT DISTINCT m.modulename AS fallback,
                    mu.modulename, mu.setting, m.formalname
                    FROM $modules AS m
                    LEFT JOIN $userprefs AS mu ON m.modulename = mu.modulename
                    WHERE (setting LIKE 'user_%' OR m.infokeys LIKE '%|prefs|%')"
                );
                $fill = [];
                $rewrite = json_decode(get_module_setting('rewrite'), true);
                while ($row = db_fetch_assoc($sql)) {
                    if ($row['setting'] != '' && strpos($row['setting'], 'user_') !== false) {
                        $structuredKey = "{$row['modulename']}__{$row['setting']}";
                        if ($rewrite[$structuredKey] != $row['formalname']) {
                            $fill[$structuredKey] = $rewrite[$structuredKey];
                        }
                        else {
                            $fill[$structuredKey] = $row['formalname'];
                        }
                    }
                    else {
                        $possibleKeys = get_module_info($row['fallback'])['prefs'];
                        foreach ($possibleKeys as $key => $val) {
                            if (strpos($key, 'user_') !== false) {
                                $structuredKey = "{$row['fallback']}__$key";
                                if ($rewrite[$structuredKey] != $row['formalname'] && $rewrite[$structuredKey] != '') {
                                    $fill[$structuredKey] = $rewrite[$structuredKey];
                                }
                                else {
                                    $fill[$structuredKey] = $row['formalname'];
                                }
                            }
                        }
                    }
                }
                if ($fill != $rewrite) {
                    set_module_setting('rewrite', json_encode($fill));
                    output(
                        "`QUpdating the 'Settings' module rewrite conditions..."
                    );
                }
            }
            break;
    }
    return $args;
}

function settings_run()
{
    global $session;
    $op = httpget('op');
    $category = stripslashes(rawurldecode(httpget('cat')))?:'Account';
    page_header("Settings - $category");
    switch ($op) {
        case 'save':
            $accounts = db_prefix('accounts');
            $post = httpallpost();
            unset($post['showFormTabIndex']);
            foreach ($post as $key => $val) {
                $post[$key] = stripcslashes($val);
            }
            $post['oldvalues'] = json_decode($post['oldvalues'], true);
            foreach ($post['oldvalues'] as $key => $val) {
                $post['oldvalues'][$key] = stripslashes($val);
            }
            $post = modulehook('prefs-change', $post);
            if ($post['return'] != '') {
                $return = $post['return'];
                unset($post['return']);
            }
            //Fix template changes.
            if (md5(md5($post['oldpass'])) == $session['user']['password'] && $post['newpass'] != '') {
                require_once('lib/systemmail.php');
                $newPass = md5(md5($post['newpass']));
                db_query(
                    "UPDATE $accounts
                    SET password = '$newPass'
                    WHERE acctid = '{$session['user']['acctid']}'"
                );
                systemmail(
                    $session['user']['acctid'],
                    'Account Settings',
                    "`@Your password was changed successfully!"
                );
            }
            unset($post['newpass']);
            unset($post['oldpass']);
            foreach ($post as $key => $val) {
                if ($key == 'bio' && $val != $post['oldvalues']['bio']) {
                    $session['user']['bio'] = $val;
                }
                else if (!is_array($val) && $val != $post['oldvalues'][$key]) {
                    if (strpos($key, '__user') || strpos($key, '__check')) {
                        $moduleKey = explode('__', $key);
                        set_module_pref($moduleKey[1], $val, $moduleKey[0]);
                        unset($moduleKey);
                    }
                    else {
                        $session['user']['prefs'][$key] = $val;
                    }
                }
            }
            $prefs = @serialize($session['user']['prefs']);
            db_query(
                "UPDATE $accounts SET prefs = '$prefs'
                WHERE acctid = '{$session['user']['acctid']}'"
            );
            redirect("runmodule.php?module=settings&cat=$return&save=true");
            addnav('Go back', 'runmodule.php?module=settings');
            break;
        default:
            $modules = db_prefix('modules');
            $userprefs = db_prefix('module_userprefs');
            $rewrite = trim(get_module_setting('rewrite'));
            $rewrite = json_decode($rewrite, true);
            $languages = getsetting('serverlanguages', 'en, English');
            $prefs = $session['user']['prefs'];
            $prefs['bio'] = $session['user']['bio'];
            $prefs['template'] = $_COOKIE['template']?:getsetting('defaultskin', 'jade.htm');
            $prefs['email'] = $session['user']['emailaddress'];
            $prefsFormat = [
                'Account' => [
                    'bio' => 'Short biography, textarea',
                    'newpass' => 'New password, password',
                    'oldpass' => 'If you are changing your password&comma; type your old one, password',
                    'email' => 'Email, text',
                ],
                'Display' => [
                    'template' => 'Skin, theme',
                    'language' => 'Which language do you prefer?, enum, ' . $languages,
                    'timestamp' => 'Show timestamps in commentary?, enum, 0, None, 1, Real Time, 2, Relative Time',
                ],
                'Game Behavior' => [
                    'emailonmail' => 'Receive emails when you receive a mail?, bool',
                    'systemmail' => 'Receive emails for system messages?, bool',
                    'Be sure to check your email\'s spam folder and add our email as a trusted sender!, note',
                    'dirtyemail' => 'Allow profanity in mail?, bool',
                    'timeoffset' => sprintf_translate(
                        'Hours to offset time (currently %s)?, int',
                        date(
                            $prefs['timeformat'],
                            (strtotime('now') + ($prefs['timeoffset'] * 3600))
                        )
                    ),
                    'ihavenocheer' => 'Disable holiday text?, bool',
                    'nojump' => 'Disable jumping to the commentary when posting or refreshing?, bool',
                ],
            ];
            if (count(explode(',', $languages)) < 3) {
                unset($prefs['Display']['language']);
            }
            $prefsFormat = modulehook('prefs-format', $prefsFormat);
            $prefsTemp = [];
            $modulesFound = [];
            $sql = db_query(
                "SELECT modulename, formalname FROM $modules
                WHERE infokeys LIKE '%|prefs|%'
                AND active = 1
                ORDER BY modulename"
            );
            while ($row = db_fetch_assoc($sql)) {
                $formal = $row['formalname'];
                $modulesFound[$row['modulename']] = true;
                if (module_status($row['modulename']) == MODULE_FILE_NOT_PRESENT) {
                    foreach ($rewrite as $key => $moduleName) {
                        if ($moduleName == $formal || strpos($key, $row['modulename']) !== false) {
                            unset($rewrite[$key]);
                        }
                    }
                    set_module_setting('rewrite', json_encode($rewrite));
                }
                else {
                    $prefsTemp[$formal] = get_module_info($row['modulename'])['prefs'];
                }
                unset($prefsTemp[$formal][0]);
                foreach ($prefsTemp[$formal] as $setting => $description) {
                    $description = explode('|', $description)[0];
                    if (strpos($setting, 'user_') === false) {
                        unset($prefsTemp[$formal][$setting]);
                    }
                    else {
                        $structuredKey = "{$row['modulename']}__$setting";
                        if ($rewrite[$structuredKey] != $formal) {
                            $prefsTemp[$rewrite[$structuredKey]][$structuredKey] = $description;
                        }
                        else {
                            $prefsTemp[$formal][$structuredKey] = $description;
                        }
                        unset($prefsTemp[$formal][$setting]);
                    }
                }
                if (count($prefsTemp[$formal]) == 0) {
                    unset($prefsTemp[$formal]);
                    unset($modulesFound[$row['modulename']]);
                }
            }
            foreach ($modulesFound as $name => $true) {
                $sql = db_query(
                    "SELECT modulename, setting, value FROM $userprefs
                    WHERE modulename = '$name'
                    AND (setting LIKE 'user_%' OR setting LIKE 'check_%')
                    AND userid = '{$session['user']['acctid']}'
                    "
                );
                while ($row = db_fetch_assoc($sql)) {
                    $prefs["{$row['modulename']}__{$row['setting']}"] = $row['value'];
                }
            }
            $prefsFormat = array_merge_recursive($prefsFormat, $prefsTemp);
            $prefsFormat = modulehook('prefs-format', $prefsFormat);
            require_once('lib/villagenav.php');
            villagenav();
            addnav('Refresh', 'runmodule.php?module=settings');
            addnav('Categories');
            foreach (array_keys($prefsFormat) as $int => $name) {
                addnav(
                    $name,
                    "runmodule.php?module=settings&cat=" . rawurlencode($name)
                );
            }
            output("`c`b`i`Q$category Settings`b`i`c");
            if (httpget('save')) {
                output("`@`iYour Settings have been saved!`i`n");
            }
            rawoutput(
                "<form action='runmodule.php?module=settings&op=save' method = 'POST'>"
            );
            require_once('lib/showform.php');
            showform($prefsFormat[$category], $prefs);
            rawoutput(
                sprintf(
                    "<input type='hidden' name='oldvalues' value='%s' />",
                    json_encode($prefs)
                )
            );
            rawoutput("<input type='hidden' name='return' value='$category' />");
            rawoutput("</form>");
            rawoutput(
                "<script type='text/javascript'>
                document.getElementsByName('template')[0].onchange = function () {
                    var index = this.selectedIndex;
                    var selection = this.children[index].value;
                    document.cookie = 'template=' + selection + ';expires=86400';
                }

                </script>"
            );
            addnav('', 'runmodule.php?module=settings&op=save');
            break;
    }
    page_footer();
}
