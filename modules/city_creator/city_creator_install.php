<?php

global $mysqli_resource;
require_once('lib/tabledescriptor.php');
$fields = array(
	'cityid'		=>array('name'=>'cityid',					'type'=>'tinyint(3)',				'null'=>'0',	'extra'=>'auto_increment'),
	'cityname'		=>array('name'=>'cityname',					'type'=>'varchar(30)',				'null'=>'0'),
	'citytype'		=>array('name'=>'citytype',					'type'=>'varchar(30)',				'null'=>'0'),
	'cityauthor'	=>array('name'=>'cityauthor',				'type'=>'varchar(30)',				'null'=>'1'),
	'cityactive'	=>array('name'=>'cityactive',				'type'=>'tinyint(1)		unsigned',	'null'=>'0',	'default'=>'0'),
	'citychat'		=>array('name'=>'citychat',					'type'=>'tinyint(1)		unsigned',	'null'=>'0',	'default'=>'0'),
	'citytravel'	=>array('name'=>'citytravel',				'type'=>'tinyint(1)		unsigned',	'null'=>'0',	'default'=>'0'),
	'citytext'		=>array('name'=>'citytext',					'type'=>'text',						'null'=>'1'),
	'stabletext'	=>array('name'=>'stabletext',				'type'=>'text',						'null'=>'1'),
	'armortext'		=>array('name'=>'armortext',				'type'=>'text',						'null'=>'1'),
	'weaponstext'	=>array('name'=>'weaponstext',				'type'=>'text',						'null'=>'1'),
	'mercenarycamptext'		=>array('name'=>'mercenarycamptext','type'=>'text',						'null'=>'1'),
	'cityblockmods'	=>array('name'=>'cityblockmods',			'type'=>'text',						'null'=>'1'),
	'cityblocknavs'	=>array('name'=>'cityblocknavs',			'type'=>'text',						'null'=>'1'),
	'module'		=>array('name'=>'module',					'type'=>'varchar(30)',				'null'=>'1'),
	'key-PRIMARY'	=>array('name'=>'PRIMARY',	'type'=>'primary key',	'unique'=>'1',	'columns'=>'cityid'),
	'key-cityid'	=>array('name'=>'cityid',	'type'=>'key',							'columns'=>'cityid')
);

synctable(db_prefix('cities'), $fields, TRUE);

if( is_module_active('city_creator') )
{
	output("`c`b`QUpdating 'city_creator' Module.`0`b`c`n");
}
else
{
	output("`c`b`QInstalling 'city_creator' Module.`0`b`c`n");
	if( is_module_active('cityprefs') )
	{
		$module_info = get_module_info('cityprefs');
		if( $module_info['version'] != '1.1.0' ) output('`n`$`bWARNING:`b `3The module "cityprefs" is installed and conflicts with this module. Please upload my version of "cityprefs" so that it overwrites the old one. If you\'re unsure about anything then feel free to post in the "city_creator" module\'s discussion thread.`0`n');
	}
	else
	{
		db_query("INSERT INTO " . db_prefix('cities') . " (`cityname`,`citytype`,`cityauthor`,`cityactive`,`cityblocknavs`,`module`) VALUES ('".getsetting('villagename', LOCATION_FIELDS)."','Village','Eric Stevens',1,'".mysqli_real_escape_string($mysqli_resource, serialize(array('forest'=>1,'weapons'=>1,'armor'=>1)))."','city_creator')");
		output('`n`3The capital city has been added to the city creator table. It may require slight editing.`0`n');
	}
}

module_addhook('header-superuser');
module_addhook('changesetting');
module_addhook('cityinvalidatecache');
module_addhook_priority('everyhit-loggedin',5);
module_addhook_priority('header-village',5);
module_addhook_priority('villagetext',10);
module_addhook_priority('stabletext',10);
module_addhook_priority('armortext',10);
module_addhook_priority('weaponstext',10);
module_addhook_priority('mercenarycamptext',10);
module_addhook_priority('travel',10);
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
