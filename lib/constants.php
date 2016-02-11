<?php
// addnews ready
// translator ready
// mail ready

$defines = array();
function myDefine($name,$value){
	global $defines;
	define($name,$value);
	$defines[$name] = $value;
}

//Superuser constants
myDefine("SU_MEGAUSER",1);
myDefine("SU_EDIT_MOUNTS",2);
myDefine("SU_EDIT_CREATURES",4);
myDefine("SU_EDIT_PETITIONS",8);
myDefine("SU_EDIT_COMMENTS",16);
myDefine("SU_EDIT_DONATIONS",32);
myDefine("SU_EDIT_USERS",64);
myDefine("SU_EDIT_CONFIG",128);
myDefine("SU_INFINITE_DAYS",256);
myDefine("SU_EDIT_EQUIPMENT",512);
myDefine("SU_EDIT_PAYLOG",1024);
myDefine("SU_DEVELOPER",2048);
myDefine("SU_POST_MOTD",4096);
myDefine("SU_DEBUG_OUTPUT",8192);
myDefine("SU_MODERATE_CLANS",16384);
myDefine("SU_EDIT_RIDDLES",32768);
myDefine("SU_MANAGE_MODULES",65536);
myDefine("SU_AUDIT_MODERATION",131072);
myDefine("SU_IS_TRANSLATOR",262144);
myDefine("SU_RAW_SQL", 524288);
myDefine("SU_VIEW_SOURCE", 1048576);
myDefine("SU_NEVER_EXPIRE", 2097152);
myDefine("SU_EDIT_ITEMS", 4194304);
myDefine("SU_GIVE_GROTTO", 8388608);
myDefine("SU_OVERRIDE_YOM_WARNING", 16777216);
myDefine("SU_SHOW_PHPNOTICE", 33554432);
myDefine("SU_IS_GAMEMASTER", 67108864);

myDefine("SU_ANYONE_CAN_SET",SU_DEBUG_OUTPUT | SU_INFINITE_DAYS | SU_OVERRIDE_YOM_WARNING | SU_SHOW_PHPNOTICE);
myDefine("SU_DOESNT_GIVE_GROTTO",SU_DEBUG_OUTPUT | SU_INFINITE_DAYS | SU_VIEW_SOURCE|SU_NEVER_EXPIRE);
myDefine("SU_HIDE_FROM_LEADERBOARD",SU_MEGAUSER | SU_EDIT_DONATIONS | SU_EDIT_USERS | SU_EDIT_CONFIG | SU_INFINITE_DAYS | SU_DEVELOPER | SU_RAW_SQL);
myDefine("NO_ACCOUNT_EXPIRATION", SU_HIDE_FROM_LEADERBOARD|SU_NEVER_EXPIRE);
//likely privs which indicate a visible admin.
myDefine("SU_GIVES_YOM_WARNING", SU_EDIT_COMMENTS | SU_EDIT_USERS | SU_EDIT_CONFIG | SU_POST_MOTD);

//Clan constants
//Changed for v1.1.0 Dragonprime Edition to extend clan possibilities
myDefine("CLAN_APPLICANT",0);
myDefine("CLAN_MEMBER",10);
myDefine("CLAN_OFFICER",20);
myDefine("CLAN_LEADER",30);
myDefine("CLAN_FOUNDER",31);

//Location Constants
myDefine("LOCATION_FIELDS","Degolburg");
myDefine("LOCATION_INN","The Boar's Head Inn");

//Gender Constants
myDefine("SEX_MALE",0);
myDefine("SEX_FEMALE",1);

//Miscellaneous
myDefine("INT_MAX",4294967295);

myDefine("RACE_UNKNOWN","Horrible Gelatinous Blob");

//Character Deletion Types
myDefine("CHAR_DELETE_AUTO",1);
myDefine("CHAR_DELETE_MANUAL",2);
myDefine("CHAR_DELETE_PERMADEATH",3); //reserved for the future -- I don't have any plans this way currently, but it seemed appropriate to have it here.
myDefine("CHAR_DELETE_SUICIDE",4);

// Constants used in lib/modules - for providing more information about the
// status of the module
myDefine("MODULE_NO_INFO",0);
myDefine("MODULE_INSTALLED",1);
myDefine("MODULE_VERSION_OK",2);
myDefine("MODULE_NOT_INSTALLED",4);
myDefine("MODULE_FILE_NOT_PRESENT",8);
myDefine("MODULE_VERSION_TOO_LOW",16);
myDefine("MODULE_ACTIVE",32);
myDefine("MODULE_INJECTED",64);

?>
