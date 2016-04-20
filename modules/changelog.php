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
                if (in_array($op, ['activate', 'deactivate'])) {
                    $op = substr($op, 0, -1);
                }
                require_once('lib/gamelog.php');
                gamelog(
                    sprintf_translate(
                        '`Q%sed`@ the `^%s`@ module.',
                        $op,
                        $module
                    ),
                    get_module_setting('category')
                );
            }
            break;
    }
    return $args;
}
