<?php
//translator ready
//addnews ready
//mail ready
/**
 * Contains information for all the tables
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */
/**
 * Returns information on all the tables
 *
 * @return array Information on all the tables
 */
function get_all_tables(){
return array(
	'accounts'=>array(
		'acctid'=>array(
			'name'=>'acctid', 'type'=>'int(11) unsigned', 'extra'=>'auto_increment'
			),
		'name'=>array(
			'name'=>'name', 'type'=>'varchar(60)'
			),
		'sex'=>array(
			'name'=>'sex', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'specialty'=>array(
			'name'=>'specialty', 'type'=>'varchar(20)',
			),
		'experience'=>array(
			'name'=>'experience', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'gold'=>array(
			'name'=>'gold', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'weapon'=>array(
			'name'=>'weapon', 'type'=>'varchar(50)', 'default'=>'Fists'
			),
		'armor'=>array(
			'name'=>'armor', 'type'=>'varchar(50)', 'default'=>'T-Shirt'
			),
		'seenmaster'=>array(
			'name'=>'seenmaster', 'type'=>'int(4) unsigned', 'default'=>'0'
			),
		'level'=>array(
			'name'=>'level', 'type'=>'int(11) unsigned', 'default'=>'1'
			),
		'defense'=>array(
			'name'=>'defense', 'type'=>'int(11) unsigned', 'default'=>'1'
			),
		'attack'=>array(
			'name'=>'attack', 'type'=>'int(11) unsigned', 'default'=>'1'
			),
		'alive'=>array(
			'name'=>'alive', 'type'=>'int(11) unsigned', 'default'=>'1'
			),
		'goldinbank'=>array(
			'name'=>'goldinbank', 'type'=>'int(11)', 'default'=>'0'
			),
		'marriedto'=>array(
			'name'=>'marriedto', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'spirits'=>array(
			'name'=>'spirits', 'type'=>'int(4)', 'default'=>'0'
			),
		'laston'=>array(
			'name'=>'laston', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'hitpoints'=>array(
			'name'=>'hitpoints', 'type'=>'int(11)', 'default'=>'10'
			),
		'maxhitpoints'=>array(
			'name'=>'maxhitpoints', 'type'=>'int(11) unsigned', 'default'=>'10'
			),
		'gems'=>array(
			'name'=>'gems', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'weaponvalue'=>array(
			'name'=>'weaponvalue', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'armorvalue'=>array(
			'name'=>'armorvalue', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'location'=>array(
			'name'=>'location', 'type'=>'varchar(25)', 'default'=>'Degolburg'
			),
		'turns'=>array(
			'name'=>'turns', 'type'=>'int(11) unsigned', 'default'=>'10'
			),
		'title'=>array(
			'name'=>'title', 'type'=>'varchar(25)'
			),
		'password'=>array(
			'name'=>'password', 'type'=>'varchar(32)'
			),
		'badguy'=>array(
			'name'=>'badguy', 'type'=>'text'
			),
		'companions'=>array(
			'name'=>'companions', 'type'=>'text'
			),
		'allowednavs'=>array(
			'name'=>'allowednavs', 'type'=>'mediumtext'
			),
		'loggedin'=>array(
			'name'=>'loggedin', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'resurrections'=>array(
			'name'=>'resurrections', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'superuser'=>array(
			'name'=>'superuser', 'type'=>'int(11) unsigned', 'default'=>'1'
			),
		'weapondmg'=>array(
			'name'=>'weapondmg', 'type'=>'int(11)', 'default'=>'0'
			),
		'armordef'=>array(
			'name'=>'armordef', 'type'=>'int(11)', 'default'=>'0'
			),
		'age'=>array(
			'name'=>'age', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'charm'=>array(
			'name'=>'charm', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'specialinc'=>array(
			'name'=>'specialinc', 'type'=>'varchar(50)'
			),
		'specialmisc'=>array(
			'name'=>'specialmisc', 'type'=>'text'
			),
		'login'=>array(
			'name'=>'login', 'type'=>'varchar(50)'
			),
		'lastmotd'=>array(
			'name'=>'lastmotd',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'playerfights'=>array(
			'name'=>'playerfights', 'type'=>'int(11) unsigned', 'default'=>'3'
			),
		'lasthit'=>array(
			'name'=>'lasthit', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'seendragon'=>array(
			'name'=>'seendragon', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'dragonkills'=>array(
			'name'=>'dragonkills', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'locked'=>array(
			'name'=>'locked', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'restorepage'=>array(
			'name'=>'restorepage', 'type'=>'varchar(128)', 'null'=>'1'
			),
		'hashorse'=>array(
			'name'=>'hashorse', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'bufflist'=>array(
			'name'=>'bufflist', 'type'=>'text'
			),
		'gentime'=>array(
			'name'=>'gentime', 'type'=>'double unsigned', 'default'=>'0'
			),
		'gentimecount'=>array(
			'name'=>'gentimecount', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'lastip'=>array(
			'name'=>'lastip', 'type'=>'varchar(40)'
			),
		'uniqueid'=>array(
			'name'=>'uniqueid', 'type'=>'varchar(32)', 'null'=>'1'
			),
		'dragonpoints'=>array(
			'name'=>'dragonpoints', 'type'=>'text'
			),
		'boughtroomtoday'=>array(
			'name'=>'boughtroomtoday', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'emailaddress'=>array(
			'name'=>'emailaddress', 'type'=>'varchar(128)'
			),
		'emailvalidation'=>array(
			'name'=>'emailvalidation', 'type'=>'varchar(32)'
			),
		'sentnotice'=>array(
			'name'=>'sentnotice', 'type'=>'int(11)', 'default'=>'0'
			),
		'prefs'=>array(
			'name'=>'prefs', 'type'=>'text'
			),
		'pvpflag'=>array(
			'name'=>'pvpflag', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'transferredtoday'=>array(
			'name'=>'transferredtoday', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'soulpoints'=>array(
			'name'=>'soulpoints', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'gravefights'=>array(
			'name'=>'gravefights', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'hauntedby'=>array(
			'name'=>'hauntedby', 'type'=>'varchar(50)'
			),
		'deathpower'=>array(
			'name'=>'deathpower', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'gensize'=>array(
			'name'=>'gensize', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'recentcomments'=>array(
			'name'=>'recentcomments',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'donation'=>array(
			'name'=>'donation', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'donationspent'=>array(
			'name'=>'donationspent', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'donationconfig'=>array(
			'name'=>'donationconfig', 'type'=>'text'
			),
		'referer'=>array(
			'name'=>'referer', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'refererawarded'=>array(
			'name'=>'refererawarded', 'type'=>'tinyint(1)', 'default'=>'0'
			),
		'bio'=>array(
			'name'=>'bio', 'type'=>'varchar(255)'
			),
		'race'=>array(
			'name'=>'race', 'type'=>'varchar(25)', 'default'=>'0'
			),
		'biotime'=>array(
			'name'=>'biotime', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'banoverride'=>array(
			'name'=>'banoverride',
			'type'=>'tinyint(4)',
			'null'=>'1',
			'default'=>'0'
			),
		'buffbackup'=>array(
			'name'=>'buffbackup', 'type'=>'text', 'null'=>'1'
			),
		'amountouttoday'=>array(
			'name'=>'amountouttoday', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'pk'=>array(
			'name'=>'pk', 'type'=>'tinyint(3) unsigned', 'default'=>'0'
			),
		'dragonage'=>array(
			'name'=>'dragonage', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'bestdragonage'=>array(
			'name'=>'bestdragonage', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'ctitle'=>array(
			'name'=>'ctitle', 'type'=>'varchar(25)'
			),
		'beta'=>array(
			'name'=>'beta', 'type'=>'tinyint(3) unsigned', 'default'=>'0'
			),
		'slaydragon'=>array(
			'name'=>'slaydragon', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'fedmount'=>array(
			'name'=>'fedmount', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'regdate'=>array(
			'name'=>'regdate',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'clanid'=>array(
			'name'=>'clanid', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'clanrank'=>array(
			'name'=>'clanrank', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'clanjoindate'=>array(
			'name'=>'clanjoindate',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'acctid'
			),
		'key-name'=>array(
			'name'=>'name', 'type'=>'key', 'columns'=>'name'
			),
		'key-level'=>array(
			'name'=>'level', 'type'=>'key', 'columns'=>'level'
			),
		'key-login'=>array(
			'name'=>'login', 'type'=>'key', 'columns'=>'login'
			),
		'key-alive'=>array(
			'name'=>'alive', 'type'=>'key', 'columns'=>'alive'
			),
		'key-laston'=>array(
			'name'=>'laston', 'type'=>'key', 'columns'=>'laston'
			),
		'key-lasthit'=>array(
			'name'=>'lasthit', 'type'=>'key', 'columns'=>'lasthit'
			),
		'key-emailaddress'=>array(
			'name'=>'emailaddress', 'type'=>'key', 'columns'=>'emailaddress'
			),
		'key-clanid'=>array(
			'name'=>'clanid', 'type'=>'key', 'columns'=>'clanid'
			),
		'key-locked'=>array(
			'name'=>'locked', 'type'=>'key', 'columns'=>'locked,loggedin,laston'
			),
		'key-referer'=>array(
			'name'=>'referer', 'type'=>'key', 'columns'=>'referer'
			),
		'key-uniqueid'=>array(
			'name'=>'uniqueid', 'type'=>'key', 'columns'=>'uniqueid'
			),
		'key-emailvalidation'=>array(
			'name'=>'emailvalidation', 'type'=>'key', 'columns'=>'emailvalidation'
			),
		),
	'accounts_output'=>array(
		'acctid'=>array(
			'name'=>'acctid', 'type'=>'int(11) unsigned'
			),
		'output'=>array(
			'name'=>'output', 'type'=>'mediumtext'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'acctid'
			),
		),
	'companions'=>array(
		'companionid'=>array(
			'name'=>'companionid', 'type'=>'int(11) unsigned', 'extra'=>'auto_increment',
			),
		'name'=>array(
			'name'=>'name', 'type'=>'varchar(255)', 'null'=>'0'
			),
		'category'=>array(
			'name'=>'category', 'type'=>'varchar(255)', 'null'=>'0'
			),
		'description'=>array(
			'name'=>'description', 'type'=>'text', 'null'=>'0'
			),
		'attack'=>array(
			'name'=>'attack', 'type'=>'int(6) unsigned', 'null'=>'0', 'default'=>'1'
			),
		'attackperlevel'=>array(
			'name'=>'attackperlevel', 'type'=>'int(6) unsigned', 'null'=>'0', 'default'=>'0'
			),
		'defense'=>array(
			'name'=>'defense', 'type'=>'int(6) unsigned', 'null'=>'0', 'default'=>'1'
			),
		'defenseperlevel'=>array(
			'name'=>'defenseperlevel', 'type'=>'int(6) unsigned', 'null'=>'0', 'default'=>'0'
			),
		'maxhitpoints'=>array(
			'name'=>'maxhitpoints', 'type'=>'int(6) unsigned', 'null'=>'0', 'default'=>'10'
			),
		'maxhitpointsperlevel'=>array(
			'name'=>'maxhitpointsperlevel', 'type'=>'int(6) unsigned', 'null'=>'0', 'default'=>'10'
			),
		'abilities'=>array(
			'name'=>'abilities', 'type'=>'text', 'null'=>'0', 'default'=>''
			),
		'cannotdie'=>array(
			'name'=>'cannotdie', 'type'=>'tinyint(4)', 'null'=>'0', 'default'=>'0'
			),
		'cannotbehealed'=>array(
			'name'=>'cannotbehealed', 'type'=>'tinyint(4)', 'null'=>'0', 'default'=>'1'
			),
		'companionlocation'=>array(
			'name'=>'companionlocation', 'type'=>'varchar(25)', 'default'=>'all'
			),
		'companionactive'=>array(
			'name'=>'companionactive', 'type'=>'tinyint(25)', 'default'=>'1'
			),
		'companioncostdks'=>array(
			'name'=>'companioncostdks', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'companioncostgems'=>array(
			'name'=>'companioncostgems', 'type'=>'int(6)', 'default'=>'0'
			),
		'companioncostgold'=>array(
			'name'=>'companioncostgold', 'type'=>'int(10)', 'default'=>'0'
			),
		'jointext'=>array(
			'name'=>'jointext', 'type'=>'text', 'default'=>''
			),
		'dyingtext'=>array(
			'name'=>'dyingtext', 'type'=>'varchar(255)', 'default'=>''
			),
		'allowinshades'=>array(
			'name'=>'allowinshades', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'allowinpvp'=>array(
			'name'=>'allowinpvp', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'allowintrain'=>array(
			'name'=>'allowintrain', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'companionid'
			),
	),
	'paylog'=>array(
		'payid'=>array(
			'name'=>'payid', 'type'=>'int(11)', 'null'=>'0', 'extra'=>'auto_increment'
			),
		'info'=>array(
			'name'=>'info', 'type'=>'text'
			),
		'response'=>array(
			'name'=>'response', 'type'=>'text', 'null'=>'0'
			),
		'txnid'=>array(
			'name'=>'txnid', 'type'=>'varchar(32)', 'null'=>'0'
			),
		'amount'=>array(
			'name'=>'amount', 'type'=>'float(9,2)', 'null'=>'0', 'default'=>'0.00'
			),
		'name'=>array(
			'name'=>'name', 'type'=>'varchar(50)', 'null'=>'0'
			),
		'acctid'=>array(
			'name'=>'acctid', 'type'=>'int(11) unsigned', 'null'=>'0', 'default'=>'0',
			),
		'processed'=>array(
			'name'=>'processed', 'type'=>'tinyint(4) unsigned', 'null'=>'0',
			'default'=>'0'
			),
		'filed'=>array(
			'name'=>'filed', 'type'=>'tinyint(4) unsigned', 'null'=>'0',
			'default'=>'0'
			),
		'txfee'=>array(
			'name'=>'txfee', 'type'=>'float(9,2)', 'null'=>'0',
			'default'=>'0.00'
			),
		'processdate'=>array(
			'name'=>'processdate', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'payid'
			),
		'key-txnid'=>array(
			'name'=>'txnid', 'type'=>'key', 'columns'=>'txnid'
			),
		),
	'armor'=>array(
		'armorid'=>array(
			'name'=>'armorid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'armorname'=>array(
			'name'=>'armorname', 'type'=>'varchar(128)', 'null'=>'1'
			),
		'value'=>array(
			'name'=>'value', 'type'=>'int(11)', 'default'=>'0'
			),
		'defense'=>array(
			'name'=>'defense', 'type'=>'int(11)', 'default'=>'1'
			),
		'level'=>array(
			'name'=>'level', 'type'=>'int(11)', 'default'=>'0'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'armorid'
			),
		),
	'bans'=>array(
		'ipfilter'=>array(
			'name'=>'ipfilter', 'type'=>'varchar(15)'
			),
		'uniqueid'=>array(
			'name'=>'uniqueid', 'type'=>'varchar(32)'
			),
		'banexpire'=>array(
			'name'=>'banexpire', 'type'=>'datetime', 'null'=>'1'
			),
		'banreason'=>array(
			'name'=>'banreason', 'type'=>'text'
			),
		'banner'=>array(
			'name'=>'banner', 'type'=>'varchar(50)'
			),
		'lasthit'=>array(
			'name'=>'lasthit', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'key-banexpire'=>array(
			'name'=>'banexpire', 'type'=>'key', 'columns'=>'banexpire'
			),
		'key-uniqueid'=>array(
			'name'=>'uniqueid', 'type'=>'key', 'columns'=>'uniqueid'
			),
		'key-ipfilter'=>array(
			'name'=>'ipfilter', 'type'=>'key', 'columns'=>'ipfilter'
			),
		),
	'clans'=>array(
		'clanid'=>array(
			'name'=>'clanid', 'type'=>'int(11) unsigned', 'extra'=>'auto_increment'
			),
		'clanname'=>array(
			'name'=>'clanname', 'type'=>'varchar(255)'
			),
		'clanshort'=>array(
			'name'=>'clanshort', 'type'=>'varchar(5)'
			),
		'clanmotd'=>array(
			'name'=>'clanmotd', 'type'=>'text', 'null'=>'1'
			),
		'clandesc'=>array(
			'name'=>'clandesc', 'type'=>'text', 'null'=>'1'
			),
		'motdauthor'=>array(
			'name'=>'motdauthor', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'descauthor'=>array(
			'name'=>'descauthor', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'customsay'=>array(
			'name'=>'customsay', 'type'=>'varchar(15)'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'clanid'
			),
		'key-clanname'=>array(
			'name'=>'clanname', 'type'=>'key', 'columns'=>'clanname'
			),
		'key-clanshort'=>array(
			'name'=>'clanshort', 'type'=>'key', 'columns'=>'clanshort'
			)
		),
	'commentary'=>array(
		'commentid'=>array(
			'name'=>'commentid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'section'=>array(
			'name'=>'section', 'type'=>'varchar(20)', 'null'=>'1'
			),
		'author'=>array(
			'name'=>'author', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'comment'=>array(
			'name'=>'comment', 'type'=>'varchar(200)'
			),
		'postdate'=>array(
			'name'=>'postdate',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'commentid'
			),
		'key-section'=>array(
			'name'=>'section', 'type'=>'key', 'columns'=>'section'
			),
		'key-postdate'=>array(
			'name'=>'postdate', 'type'=>'key', 'columns'=>'postdate'
			)
		),
	'creatures'=>array(
		'creatureid'=>array(
			'name'=>'creatureid', 'type'=>'int(11)', 'extra'=>'auto_increment'
			),
		'creaturename'=>array(
			'name'=>'creaturename', 'type'=>'varchar(50)', 'null'=>'1'
			),
		'creaturelevel'=>array(
			'name'=>'creaturelevel', 'type'=>'int(11)', 'null'=>'1'
			),
		'creatureweapon'=>array(
			'name'=>'creatureweapon', 'type'=>'varchar(50)', 'null'=>'1'
			),
		'creaturelose'=>array(
			'name'=>'creaturelose', 'type'=>'varchar(120)', 'null'=>'1'
			),
		'creaturewin'=>array(
			'name'=>'creaturewin', 'type'=>'varchar(120)', 'null'=>'1'
			),
		'creaturegold'=>array(
			'name'=>'creaturegold', 'type'=>'int(11)', 'null'=>'1'
			),
		'creatureexp'=>array(
			'name'=>'creatureexp', 'type'=>'int(11)', 'null'=>'1'
			),
		'oldcreatureexp'=>array(
			'name'=>'oldcreatureexp', 'type'=>'int(11)', 'null'=>'1'
			), //this field is obsolete and will be dropped by the installer
		'creaturehealth'=>array(
			'name'=>'creaturehealth', 'type'=>'int(11)', 'null'=>'1'
			),
		'creatureattack'=>array(
			'name'=>'creatureattack', 'type'=>'int(11)', 'null'=>'1'
			),
		'creaturedefense'=>array(
			'name'=>'creaturedefense', 'type'=>'int(11)', 'null'=>'1'
			),
		'creatureaiscript'=>array(
			'name'=>'creatureaiscript', 'type'=>'text', 'null'=>'1'
			),
		'oldcreatureexp'=>array(
			'name'=>'oldcreatureexp', 'type'=>'int(11)', 'null'=>'1'
			),
		'createdby'=>array(
			'name'=>'createdby', 'type'=>'varchar(50)', 'null'=>'1'
			),
		'forest'=>array(
			'name'=>'forest', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'graveyard'=>array(
			'name'=>'graveyard', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'creatureid'
			),
		'key-creaturelevel'=>array(
			'name'=>'creaturelevel', 'type'=>'key', 'columns'=>'creaturelevel'
			)
		),
	'debuglog'=>array(
		'id'=>array(
			'name'=>'id', 'type'=>'int(11) unsigned', 'extra'=>'auto_increment'
			),
		'date'=>array(
			'name'=>'date', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'actor'=>array(
			'name'=>'actor', 'type'=>'int(11) unsigned', 'null'=>'1'
			),
		'target'=>array(
			'name'=>'target', 'type'=>'int(11) unsigned', 'null'=>'1'
			),
		'message'=>array(
			'name'=>'message', 'type'=>'text'
			),
		'field'=>array(
			'name'=>'field', 'type'=>'varchar(20)', 'null'=>'0', 'default'=>''
			),
		'value'=>array(
			'name'=>'value', 'type'=>'float(9,2)', 'null'=>'0', 'default'=>'0.00'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'id'
			),
		'key-date'=>array(
			'name'=>'date', 'type'=>'key', 'columns'=>'date'
			),
		'key-field'=>array(
			'name'=>'field', 'type'=>'key', 'columns'=>'actor,field'
			),
		),
	'faillog'=>array(
		'eventid'=>array(
			'name'=>'eventid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'date'=>array(
			'name'=>'date', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'post'=>array(
			'name'=>'post', 'type'=>'tinytext'
			),
		'ip'=>array(
			'name'=>'ip', 'type'=>'varchar(40)'
			),
		'acctid'=>array(
			'name'=>'acctid', 'type'=>'int(11) unsigned', 'null'=>'1'
			),
		'id'=>array(
			'name'=>'id', 'type'=>'varchar(32)'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'eventid'
			),
		'key-date'=>array(
			'name'=>'date', 'type'=>'key', 'columns'=>'date'
			),
		'key-acctid'=>array(
			'name'=>'acctid', 'type'=>'key', 'columns'=>'acctid'
			),
		'key-ip'=>array(
			'name'=>'ip', 'type'=>'key', 'columns'=>'ip'
			)
		),
	'gamelog'=>array(
		'logid'=>array(
			'name'=>'logid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment',
			),
		'message'=>array(
			'name'=>'message',
			'type'=>'text',
			),
		'category'=>array(
			'name'=>'category',
			'type'=>'varchar(50)',
			),
		'filed'=>array(
			'name'=>'filed',
			'type'=>'tinyint(4)',
			'default'=>'0',
			),
		'date'=>array(
			'name'=>'date',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00',
			),
		'who'=>array(
			'name'=>'who',
			'type'=>'int(11) unsigned',
			'default'=>'0',
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'logid',
			),
		'key-date'=>array(
			'name'=>'date',
			'type'=>'key',
			'columns'=>'category,date',
			),
		),
	'logdnetbans'=>array(
		'banid'=>array('name'=>'banid','type'=>'int(11) unsigned','extra'=>'auto_increment'),
		'bantype'=>array('name'=>'bantype','type'=>'varchar(20)'),
		'banvalue'=>array('name'=>'banvalue','type'=>'varchar(255)'),
		'key-PRIMARY'=>array('name'=>'PRIMARY','type'=>'PRIMARY KEY','unique'=>'1','columns'=>'banid'),
		),
	'logdnet'=>array(
		'serverid'=>array(
			'name'=>'serverid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'address'=>array(
			'name'=>'address', 'type'=>'varchar(255)'
			),
		'description'=>array(
			'name'=>'description', 'type'=>'varchar(255)'
			),
		'priority'=>array(
			'name'=>'priority', 'type'=>'double', 'default'=>'100'
			),
		'lastupdate'=>array(
			'name'=>'lastupdate',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'version'=>array(
			'name'=>'version', 'type'=>'varchar(255)', 'default'=>'Unknown'
			),
		'admin'=>array(
			'name'=>'admin', 'type'=>'varchar(255)', 'default'=>'unknown'
			),
		'lastping'=>array(
			'name'=>'lastping',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'recentips'=>array(
			'name'=>'recentips',
			'type'=>'varchar(255)',
			'default'=>'',
			),
		'count'=>array(
			'name'=>'count',
			'type'=>'int(11) unsigned',
			'default'=>'0',
			),
		'lang'=>array(
			'name'=>'lang',
			'type'=>'varchar(20)',
			'default'=>'',
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'serverid'
			)
		),
	'mail'=>array(
		'messageid'=>array(
			'name'=>'messageid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'msgfrom'=>array(
			'name'=>'msgfrom', 'type'=>'varchar(255)', 'default'=>'0'
			),
		'msgto'=>array(
			'name'=>'msgto', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'subject'=>array(
			'name'=>'subject', 'type'=>'varchar(255)'
			),
		'body'=>array(
			'name'=>'body', 'type'=>'text'
			),
		'sent'=>array(
			'name'=>'sent', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'seen'=>array(
			'name'=>'seen', 'type'=>'tinyint(1)', 'default'=>'0'
			),
		'originator'=>array(
			'name'=>'originator', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'messageid'
			),
		'key-msgto'=>array(
			'name'=>'msgto', 'type'=>'key', 'columns'=>'msgto'
			),
		'key-seen'=>array(
			'name'=>'seen', 'type'=>'key', 'columns'=>'seen'
			)
		),
	'masters'=>array(
		'creatureid'=>array(
			'name'=>'creatureid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'creaturename'=>array(
			'name'=>'creaturename', 'type'=>'varchar(50)', 'null'=>'1'
			),
		'creaturelevel'=>array(
			'name'=>'creaturelevel', 'type'=>'int(11)', 'null'=>'1'
			),
		'creatureweapon'=>array(
			'name'=>'creatureweapon', 'type'=>'varchar(50)', 'null'=>'1'
			),
		'creaturelose'=>array(
			'name'=>'creaturelose', 'type'=>'varchar(120)', 'null'=>'1'
			),
		'creaturewin'=>array(
			'name'=>'creaturewin', 'type'=>'varchar(120)', 'null'=>'1'
			),
		'creaturegold'=>array(
			'name'=>'creaturegold', 'type'=>'int(11)', 'null'=>'1'
			),
		'creatureexp'=>array(
			'name'=>'creatureexp', 'type'=>'int(11)', 'null'=>'1'
			),
		'creaturehealth'=>array(
			'name'=>'creaturehealth', 'type'=>'int(11)', 'null'=>'1'
			),
		'creatureattack'=>array(
			'name'=>'creatureattack', 'type'=>'int(11)', 'null'=>'1'
			),
		'creaturedefense'=>array(
			'name'=>'creaturedefense', 'type'=>'int(11)', 'null'=>'1'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'creatureid'
			)
		),
	'moderatedcomments'=>array(
		'modid'=>array(
			'name'=>'modid', 'type'=>'int(11) unsigned', 'extra'=>'auto_increment'
			),
		'comment'=>array(
			'name'=>'comment', 'type'=>'text', 'null'=>'1'
			),
		'moderator'=>array(
			'name'=>'moderator', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'moddate'=>array(
			'name'=>'moddate', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'modid'
			)
		),
	'module_event_hooks'=>array(
		'event_type'=>array(
			'name'=>'event_type', 'type'=>'varchar(20)'
			),
		'modulename'=>array(
			'name'=>'modulename', 'type'=>'varchar(50)'
			),
		'event_chance'=>array(
			'name'=>'event_chance', 'type'=>'text'
			),
		'key-modulename'=>array(
			'name'=>'modulename', 'type'=>'key', 'columns'=>'modulename'
			),
		'key-event_type'=>array(
			'name'=>'event_type', 'type'=>'key', 'columns'=>'event_type'
			)
		),
	'module_hooks'=>array(
		'modulename'=>array(
			'name'=>'modulename', 'type'=>'varchar(50)'
			),
		'location'=>array(
			'name'=>'location', 'type'=>'varchar(50)'
			),
		'function'=>array(
			'name'=>'function', 'type'=>'varchar(50)'
			),
		'whenactive'=>array(
			'name'=>'whenactive', 'type'=>'text'
			),
		'priority'=>array(
			'name'=>'priority','type'=>'int(11)','default'=>'50'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'modulename,location,function'
			),
		'key-location'=>array(
			'name'=>'location', 'type'=>'key', 'columns'=>'location'
			),
		),
	'module_objprefs'=>array(
		'modulename'=>array(
			'name'=>'modulename', 'type'=>'varchar(50)'
			),
		'objtype'=>array(
			'name'=>'objtype', 'type'=>'varchar(50)'
			),
		'setting'=>array(
			'name'=>'setting', 'type'=>'varchar(50)'
			),
		'objid'=>array(
			'name'=>'objid', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'value'=>array(
			'name'=>'value', 'type'=>'text', 'null'=>'1'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'modulename,objtype,setting,objid'
			)
		),

	'module_settings'=>array(
		'modulename'=>array(
			'name'=>'modulename', 'type'=>'varchar(50)'
			),
		'setting'=>array(
			'name'=>'setting', 'type'=>'varchar(50)'
			),
		'value'=>array(
			'name'=>'value', 'type'=>'text', 'null'=>'1'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'modulename,setting'
			)
		),
	'module_userprefs'=>array(
		'modulename'=>array(
			'name'=>'modulename', 'type'=>'varchar(50)'
			),
		'setting'=>array(
			'name'=>'setting', 'type'=>'varchar(50)'
			),
		'userid'=>array(
			'name'=>'userid', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'value'=>array(
			'name'=>'value', 'type'=>'text', 'null'=>'1'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'modulename,setting,userid'
			),
		'key-modulename'=>array(
			'name'=>'modulename', 'type'=>'key', 'columns'=>'modulename,userid'
			),
		'key-userid'=>array( // Speed up char deletion, takes a lot of space, though
			'name'=>'userid', 'type'=>'key', 'columns'=>'userid'
			),
		),
	'modules'=>array(
		'modulename'=>array(
			'name'=>'modulename', 'type'=>'varchar(50)'
			),
		'formalname'=>array(
			'name'=>'formalname', 'type'=>'varchar(255)'
			),
		'description'=>array(
			'name'=>'description', 'type'=>'text'
			),
		'moduleauthor'=>array(
			'name'=>'moduleauthor', 'type'=>'varchar(255)'
			),
		'active'=>array(
			'name'=>'active', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'filename'=>array(
			'name'=>'filename', 'type'=>'varchar(255)'
			),
		'installdate'=>array(
			'name'=>'installdate',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'installedby'=>array(
			'name'=>'installedby', 'type'=>'varchar(50)'
			),
		'filemoddate'=>array(
			'name'=>'filemoddate',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'type'=>array(
			'name'=>'type', 'type'=>'tinyint(4)', 'default'=>'0'
			),
		'extras'=>array(
			'name'=>'extras', 'type'=>'text', 'null'=>'1'
			),
		'category'=>array(
			'name'=>'category', 'type'=>'varchar(50)'
			),
		'infokeys'=>array(
			'name'=>'infokeys', 'type'=>'text'
			),
		'version'=>array(
			'name'=>'version', 'type'=>'varchar(10)', 'null'=>'1'
			),
		'download'=>array(
			'name'=>'download', 'type'=>'varchar(200)', 'null'=>'1'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'modulename'
			)
		),
	'motd'=>array(
		'motditem'=>array(
			'name'=>'motditem',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'motdtitle'=>array(
			'name'=>'motdtitle', 'type'=>'varchar(200)', 'null'=>'1'
			),
		'motdbody'=>array(
			'name'=>'motdbody', 'type'=>'text', 'null'=>'1'
			),
		'motddate'=>array(
			'name'=>'motddate', 'type'=>'datetime', 'null'=>'1'
			),
		'motdtype'=>array(
			'name'=>'motdtype', 'type'=>'tinyint(4) unsigned', 'default'=>'0'
			),
		'motdauthor'=>array(
			'name'=>'motdauthor', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'motditem'
			)
		),
	'mounts'=>array(
		'mountid'=>array(
			'name'=>'mountid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'mountname'=>array(
			'name'=>'mountname', 'type'=>'varchar(50)'
			),
		'mountdesc'=>array(
			'name'=>'mountdesc', 'type'=>'text', 'null'=>'1'
			),
		'mountcategory'=>array(
			'name'=>'mountcategory', 'type'=>'varchar(50)'
			),
		'mountbuff'=>array(
			'name'=>'mountbuff', 'type'=>'text', 'null'=>'1'
			),
		'mountcostgems'=>array(
			'name'=>'mountcostgems', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'mountcostgold'=>array(
			'name'=>'mountcostgold', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'mountactive'=>array(
			'name'=>'mountactive', 'type'=>'int(11) unsigned', 'default'=>'1'
			),
		'mountforestfights'=>array(
			'name'=>'mountforestfights', 'type'=>'int(11)', 'default'=>'0'
			),
		'newday'=>array(
			'name'=>'newday', 'type'=>'text'
			),
		'recharge'=>array(
			'name'=>'recharge', 'type'=>'text'
			),
		'partrecharge'=>array(
			'name'=>'partrecharge', 'type'=>'text'
			),
		'mountfeedcost'=>array(
			'name'=>'mountfeedcost', 'type'=>'int(11) unsigned', 'default'=>'20'
			),
		'mountlocation'=>array(
			'name'=>'mountlocation', 'type'=>'varchar(25)', 'default'=>'all'
			),
		'mountdkcost'=>array(
			'name'=>'mountdkcost', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'mountid'
			),
		'key-mountid'=>array(
			'name'=>'mountid', 'type'=>'key', 'columns'=>'mountid'
			)
		),
	'nastywords'=>array(
		'words'=>array(
			'name'=>'words', 'type'=>'text', 'null'=>'1'
			),
		'type'=>array(
			'name'=>'type', 'type'=>'varchar(10)', 'null'=>'1'
			)
		),
	'news'=>array(
		'newsid'=>array(
			'name'=>'newsid', 'type'=>'int(11) unsigned', 'extra'=>'auto_increment'
			),
		'newstext'=>array(
			'name'=>'newstext', 'type'=>'text'
			),
		'newsdate'=>array(
			'name'=>'newsdate', 'type'=>'date', 'default'=>'0000-00-00'
			),
		'accountid'=>array(
			'name'=>'accountid', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'arguments'=>array(
			'name'=>'arguments', 'type'=>'text'
			),
		'tlschema'=>array(
			'name'=>'tlschema', 'type'=>'varchar(255)', 'default'=>'news'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'newsid,newsdate'
			),
		'key-accountid'=>array(
			'name'=>'accountid', 'type'=>'key', 'columns'=>'accountid'
			),
		'key-newsdate'=>array(
			'name'=>'newsdate', 'type'=>'key', 'columns'=>'newsdate'
			),
		),
	'petitions'=>array(
		'petitionid'=>array(
			'name'=>'petitionid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'author'=>array(
			'name'=>'author', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'date'=>array(
			'name'=>'date', 'type'=>'datetime', 'default'=>'0000-00-00 00:00:00'
			),
		'status'=>array(
			'name'=>'status', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'body'=>array(
			'name'=>'body', 'type'=>'text', 'null'=>'1'
			),
		'pageinfo'=>array(
			'name'=>'pageinfo', 'type'=>'text', 'null'=>'1'
			),
		'closedate'=>array(
			'name'=>'closedate',
			'type'=>'datetime',
			'default'=>'0000-00-00 00:00:00'
			),
		'closeuserid'=>array(
			'name'=>'closeuserid', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'ip'=>array(
			'name'=>'ip', 'type'=>'varchar(40)', 'default'=>''
			),
		'id'=>array(
			'name'=>'id', 'type'=>'varchar(32)', 'default'=>''
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'petitionid'
			)
		),
	'pollresults'=>array(
		'resultid'=>array(
			'name'=>'resultid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'choice'=>array(
			'name'=>'choice', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'account'=>array(
			'name'=>'account', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'motditem'=>array(
			'name'=>'motditem', 'type'=>'int(11) unsigned', 'default'=>'0'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'resultid'
			)
		),
	'referers'=>array(
		// This table needs to be myISAM since pre-4.0.14 mysql cannot index
		// on blob tables under innoDB and we have no way to determine
		// with 100% accuracy (mysql_get_server_info merely returns an
		// arbitrary string) what the version of the database is. :/
		'RequireMyISAM'=>1,
		'refererid'=>array(
			'name'=>'refererid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'uri'=>array(
			'name'=>'uri', 'type'=>'text', 'null'=>'1'
			),
		'count'=>array(
			'name'=>'count', 'type'=>'int(11)', 'null'=>'1'
			),
		'last'=>array(
			'name'=>'last', 'type'=>'datetime', 'null'=>'1'
			),
		'site'=>array(
			'name'=>'site', 'type'=>'varchar(50)'
			),
		'dest'=>array(
			'name'=>'dest', 'type'=>'varchar(255)', 'null'=>'1'
			),
		'ip'=>array(
			'name'=>'ip', 'type'=>'varchar(40)', 'null'=>'1'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'refererid'
			),
		'key-uri'=>array(
			'name'=>'uri', 'type'=>'key', 'columns'=>'uri(100)'
			),
		'key-site'=>array(
			'name'=>'site', 'type'=>'key', 'columns'=>'site'
			)
		),
	'settings'=>array(
		'setting'=>array(
			'name'=>'setting', 'type'=>'varchar(20)'
			),
		'value'=>array(
			'name'=>'value', 'type'=>'varchar(255)'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'setting'
			)
		),
	'taunts'=>array(
		'tauntid'=>array(
			'name'=>'tauntid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'taunt'=>array(
			'name'=>'taunt', 'type'=>'text', 'null'=>'1'
			),
		'editor'=>array(
			'name'=>'editor', 'type'=>'varchar(50)', 'null'=>'1'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'tauntid'
			)
		),
	'untranslated'=>array(
		// This table needs to be myISAM since pre-4.0.14 mysql cannot index
		// on blob tables under innoDB and we have no way to determine
		// with 100% accuracy (mysql_get_server_info merely returns an
		// arbitrary string) what the version of the database is. :/
		'RequireMyISAM'=>1,
		'intext'=>array(
			'name'=>'intext', 'type'=>'blob', 'null'=>'0'
			),
		'language'=>array(
			'name'=>'language', 'type'=>'varchar(10)'
			),
		'namespace'=>array(
			'name'=>'namespace', 'type'=>'varchar(255)'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'intext(200),language,namespace'
			),
		'key-language'=>array(
			'name'=>'language', 'type'=>'key', 'columns'=>'language'
			),
		'key-intext1'=>array(
			'name'=>'intext1', 'type'=>'key', 'columns'=>'intext(200),language'
			),
		),
	'translations'=>array(
		'tid'=>array(
			'name'=>'tid', 'type'=>'int(11)', 'extra'=>'auto_increment'
			),
		'language'=>array(
			'name'=>'language', 'type'=>'varchar(10)'
			),
		'uri'=>array(
			'name'=>'uri', 'type'=>'varchar(255)'
			),
		'intext'=>array(
			'name'=>'intext', 'type'=>'blob'
			),
		'outtext'=>array(
			'name'=>'outtext', 'type'=>'blob'
			),
		'author'=>array(
			'name'=>'author', 'type'=>'varchar(50)', 'null'=>'1'
			),
		'version'=>array(
			'name'=>'version', 'type'=>'varchar(50)', 'null'=>'1'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'tid'
			),
		'key-language'=>array(
			'name'=>'language', 'type'=>'key', 'columns'=>'language,uri'
			),
		'key-uri'=>array(
			'name'=>'uri', 'type'=>'key', 'columns'=>'uri'
			),
		),
	'weapons'=>array(
		'weaponid'=>array(
			'name'=>'weaponid',
			'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'weaponname'=>array(
			'name'=>'weaponname', 'type'=>'varchar(128)', 'null'=>'1'
			),
		'value'=>array(
			'name'=>'value', 'type'=>'int(11)', 'default'=>'0'
			),
		'damage'=>array(
			'name'=>'damage', 'type'=>'int(11)', 'default'=>'1'
			),
		'level'=>array(
			'name'=>'level', 'type'=>'int(11)', 'default'=>'0'
			),
		'key-PRIMARY'=>array(
			'name'=>'PRIMARY',
			'type'=>'primary key',
			'unique'=>'1',
			'columns'=>'weaponid'
			)
		),
	'titles'=>array(
		'titleid'=>array(
			'name'=>'titleid', 'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'
			),
		'dk'=>array(
			'name'=>'dk', 'type'=>'int(11)', 'default'=>'0'
			),
		'ref'=>array(
			'name'=>'ref', 'type'=>'varchar(100)', 'null'=>'0', 'default'=>""
			),
		'male'=>array(
			'name'=>'male', 'type'=>'varchar(25)', 'null'=>'0', 'default'=>""
			),
		'female'=>array(
			'name'=>'female', 'type'=>'varchar(25)', 'null'=>'0', 'default'=>""
			),
		'key-PRIMARY' => array(
			'name' => 'PRIMARY',
			'type' => 'primary key',
			'unique' => '1',
			'columns' => 'titleid',
			),
		'key-dk' => array(
			'name' => 'dk',
			'type' => 'key',
			'columns' => 'dk',
			),
		),
);
}
?>

