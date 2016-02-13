<?php

function addgems_getmoduleinfo()
{
    $info = [
        'name' => 'Add Gems',
        'author' => 'Shannon Brown',
        'version' => '1.0',
        'category' => 'General',
        'download' => 'core_module',
        'settings' => [
            'how_many' => 'Amount of gems to give the players, int| 2',
            'message' => 'What should the message be?, text| Thank you for playing Legend of the Green Dragon! You have earned two gems for this game day!',
        ],
    ];
    return $info;
}

function addgems_install()
{
    module_addhook('newday');
    return true;
}

function addgems_uninstall()
{
    return true;
}

function addgems_dohook($hook, $args)
{
    global $session;
    $session['user']['gems'] += get_module_setting('how_many');
    output("`^%s`n`0", get_module_setting('message'));
    return $args;
}