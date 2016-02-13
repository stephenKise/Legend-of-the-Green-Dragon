<?php

function flawlesscap_getmoduleinfo()
{
    $info = [
        'name' => 'Flawless Fight Cap',
        'author' => 'Sixf00t4',
        'version' => '1.0',
        'category' => 'Forest',
        'description' => 'Limits the number of flawless fight rewards.',
        'download' => 'core_module',
        'settings' => [
            'max' => 'How many flawless wins are allowed per day?, int| 10',
        ],
        'prefs' => [
            'amount' => 'How many flawless wins today?, int| 0',
        ],
    ];
    return $info;
}

function flawlesscap_install()
{
    module_addhook('battle-victory');
    module_addhook('newday');
    return true;
}

function flawlesscap_uninstall()
{
    return true;
}

function flawlesscap_dohook($hook, $args)
{
    switch($hook) {
        case 'battle-victory';
            global $options;
            $runonce = false;
            if ($runonce !== false) break;
            if (
                $args['type'] == 'forest' &&
                (!isset($args['diddamage']) || $args['diddamage'] != 1)
            ) {
                $runonce = true;
                if (get_module_pref('amount') >= get_module_setting('max')) {
                    $options['denyflawless'] = '`nYou have already received the maximum flawless fight rewards for today.`n`n`0';
                }
                else{
                    increment_module_pref('amount');
                }
            }
            break;
        case 'newday':
            set_module_pref('amount', 0);
            break;
    }
    return $args;
}
