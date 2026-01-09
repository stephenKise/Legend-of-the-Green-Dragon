<?php

global $mysqli_resource;
require_once('lib/tabledescriptor.php');
$fields = [
    'id' => [
        'name' => 'id',
        'type' => 'tinyint(3)',
        'null' => '0',
        'extra' => 'auto_increment'
    ],
	'name' => [
        'name' => 'name',
        'type' => 'varchar(25)',
        'null' => '0',
    ],
	'sanitized_name' => [
        'name' => 'sanitized_name',
        'type' => 'varchar(25)',
        'null' => '0',
    ],
	'type' => [
        'name' => 'type',
        'type' => 'varchar(30)',
        'null' => '0',
    ],
	'author' => [
        'name' => 'author',
		'type' => 'varchar(30)',
		'null' => '1',
    ],
    'active' => [
        'name' => 'active',
        'type' => 'tinyint(1)	unsigned',
        'null' => '0',
        'default' => '0',
    ],
	'chat' => [
        'name' => 'chat',
        'type' => 'tinyint(1) unsigned',
        'null' => '0',
        'default' => '0',
    ],
	'travel' => [
        'name' => 'travel',
		'type' => 'tinyint(1) unsigned',
        'null' => '0',
        'default' => '0',
    ],
	'block_mods' => [
        'name' => 'block_mods',
		'type' => 'text',
        'null' => '1',
    ],
	'block_navs' => [
        'name' => 'block_navs',
        'type' => 'text',
        'null' => '1',
    ],
	'module' => [
        'name' => 'module',
        'type' => 'varchar(30)',
        'null' => '1',
    ],
	'key-PRIMARY' => [
        'name' => 'PRIMARY',
        'type' => 'primary key',
        'unique' => '1',
        'columns' => 'id',
    ],
	'key-id' => [
        'name' => 'id',
        'type' => 'key',
        'columns' => 'id',
   ]
];
$villagesTable = db_prefix('villages');

synctable($villagesTable, $fields, TRUE);

if (is_module_active('villageCreator')) {
	output('village_creator.headers.update');
}
else {
	output('village_creator.headers.install');
    $mainVillage = getsetting('villagename', LOCATION_FIELDS);
    $blockedNavs = mysqli_escape_string($mysqli_resource, serialize([]));
    $sanitizedName = sanitize($mainVillage);
    $sanitizedName = str_replace(' ', '', $sanitizedName);
    $sanitizedName = strtolower($sanitizedName);
    $sanitizedName = mysqli_escape_string($mysqli_resource, $sanitizedName);
	db_query(
        "INSERT INTO {$villagesTable}
            (`name`, `sanitized_name`, `type`, `author`, `active`, `block_navs`, `module`)
        VALUES ('$mainVillage', '$sanitizedName', 'Village', 'Eric Stevens', 1, '$blockedNavs', 'city_creator')"
    );
	output('');
}

invalidatedatacache('village_travel');


module_addhook('header-superuser');
module_addhook('changesetting');
module_addhook('cityinvalidatecache');
module_addhook_priority('everyhit-loggedin', 5);
module_addhook_priority('header-village', 5);
module_addhook_priority('villagetext', 10);
// Temporarily disabled while the translations are merged
// module_addhook_priority('stabletext', 10);
// module_addhook_priority('armortext', 10);
// module_addhook_priority('weaponstext', 10);
// module_addhook_priority('mercenarycamptext', 10);
module_addhook_priority('travel', 10);
module_addhook('validlocation');
module_addhook('validforestloc');
module_addhook('stablelocs');
module_addhook('camplocs');
module_addhook('moderate');
module_addhook('blockcommentarea');
module_addhook('pvpstart');
module_addhook('pvpwin');
module_addhook('pvploss');
module_addhook('player-login');
module_addhook('innrooms');
module_addhook('showformextensions');
