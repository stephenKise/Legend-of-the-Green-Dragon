<?php

function changelog_getmoduleinfo()
{
    $info = [
        'name' => 'Changelog',
        'author'=> '`&`bStephen Kise`b',
        'version' => '0.1b',
        'category' => 'Administrative',
        'description' =>
            'Display all changes made on the server.',
        'download' => 'nope',
        'settings' => [
            'category' => 'What category should we list all changes under?, text| Changelog',
        ],
    ];
    return $info;
}

function changelog_install()
{
    module_addhook('header-modules');
    return true;
}

function changelog_uninstall()
{
    return true;
}

function changelog_dohook($hook, $args)
{
    switch ($hook) {
        case 'header-modules':
            $module = httppost('module') ?: httpget('module');
            $op = httpget('op');
            if ($module != '') {
                if (substr($op, -1) == 'e') {
                    $op = substr($op, 0, -1);
                }
                else if ($op == 'mass') {
                    $method = array_keys(httpallpost())[1];
                    if (substr($method, -1) == 'e') {
                        $method = substr($method, 0, -1);
                    }
                    $op = "mass $method";
                    $plural = 's';
                }
                require_once('lib/gamelog.php');
                if (is_array($module)) {
                    $lastModule = array_pop($module);
                    $module = implode(', ', $module);
                    $module .= ",`@ and `^$lastModule";
                }
                gamelog(
                    sprintf_translate(
                        '`Q%sed`@ the `^%s`@ module%s.',
                        $op,
                        $module,
                        $plural
                    ),
                    get_module_setting('category')
                );
            }
            break;
    }
    return $args;
}
