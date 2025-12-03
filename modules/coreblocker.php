<?php
function coreblocker_getmoduleinfo() {
    $settings = [
        'gardens_php' => 'Block the Gardens?,bool|0',
        'rock_php' => 'Block the Curious Looking Rock?,bool|0',
        'gypsy_php' => 'Block the Gypsy?,bool|0',
        'hof_php' => 'Block the Hall of Fame?,bool|0',
        'stables_php' => 'Block the Stables?,bool|0',
        'bank_php' => 'Block the Bank?,bool|0',
        'inn_php' => 'Block the Inn?,bool|0',
        'list_php' => 'Block the List of Players?,bool|0',
        'petition_php?op=faq' => 'Block the Faq?,bool|0',
        'lodge_php' => 'Block the Hunter\'s Lodge?,bool|0',
        'news_php' => 'Block the Daily News?,bool|0',
        'mercenarycamp_php' => 'Block the Mercenary Camp?,bool|0',
        'pvp_php' => 'Block pvp?,bool|0',
    ];
	$info = [
		'name' => 'Block Core Programs',
		'version' => '1.01',
		'author' => 'DaveS, idea by Daddlertl and Nightborn',
		'category' => 'Administrative',
		'download' =>'' ,
		'settings' => $settings,
	];
	return $info;
}

function coreblocker_install(): bool
{
	module_addhook('village');
	return true;
}

function coreblocker_uninstall(): bool
{
	return true;
}

function blockedList(): array
{
    $settings = get_all_module_settings('coreblocker');
    $blocked = [];
    foreach($settings as $setting => $value) {
        $setting = str_replace('_', '.', $setting);
        if ($value == 1) {
            $blocked[] = $setting;
        }
        
    }
	return $blocked;
}

function coreblocker_dohook($hookname, $args) {
    foreach(blockedList() as $blocked) {
        blocknav($blocked, true);
    }
	return $args;
}
