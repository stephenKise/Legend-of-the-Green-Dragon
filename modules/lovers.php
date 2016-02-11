<?php
//addnews ready
// translator ready
// mail ready
require_once("lib/buffs.php");
require_once("lib/partner.php");
//should we move charm here?
//should we move marriedto here?

function lovers_getmoduleinfo(){
	$info = array(
		"name"=>"Violet and Seth Lovers",
		"author"=>"Eric Stevens",
		"version"=>"1.0",
		"category"=>"Inn",
		"download"=>"core_module",
		"prefs"=>array(
			"Lover Module User Preferences,title",
			"seenlover"=>"Visited Lover Today?,bool|0"
		)
	);
	return $info;
}

function lovers_install(){
	module_addhook("newday");
	module_addhook("inn");

	$sql = "DESCRIBE " . db_prefix("accounts");
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		if ($row['Field']=="seenlover"){
			$sql = "SELECT seenlover,acctid FROM " . db_prefix("accounts") . " WHERE seenlover>0";
			$result1 = db_query($sql);
			debug("Migrating seenlover.`n");
			while ($row1 = db_fetch_assoc($result1)){
				$sql = "INSERT INTO " . db_prefix("module_userprefs") . " (modulename,setting,userid,value) VALUES ('lovers','seenlover',{$row1['acctid']},{$row1['seenlover']})";
				db_query($sql);
			}//end while
			debug("Dropping seenlover column from the user table.`n");
			$sql = "ALTER TABLE " . db_prefix("accounts") . " DROP seenlover";
			db_query($sql);
			//drop it from the user's session too.
			unset($session['user']['seenlover']);
		}//end if
	}//end while
	return true;
}

function lovers_uninstall(){
	return true;
}

function lovers_dohook($hookname, $args){
	global $session;
	$partner = get_partner();
	switch($hookname){
	case "newday":
		set_module_pref("seenlover",0);
		if ($session['user']['marriedto'] == 4294967295){
			$dk = $session['user']['dragonkills'];


			// 0.7 seemed to be a perfect balance of no loss of charm.
			// 1.0 was too much.
			$dk = max(1, round(.85 * sqrt($dk), 0));
			$charmloss= e_rand(1,$dk);
			$session['user']['charm'] -= $charmloss;
			output("`n`%You're  married,  so there's no reason to keep up that perfect image, and you let yourself go a little today ( You lose `\$%s charmpoint(s)`%).`n",$charmloss);
			if ($session['user']['charm']<=0){
				output("`bWhen  you  wake  up, you find a note next to you, reading`n`5Dear %s`5,`n",$session['user']['name']);
				output("Despite  many  great  kisses, I find that I'm simply no longer attracted to you the way I used to be.`n`n");
				output("Call  me fickle, call me flakey, but I need to move on.");
				output("There are other warriors in the land, and I think some of them are really hot.");
				output("So it's not you, it's me, etcetera etcetera.`n`n");
				output("No hard feelings, Love,`n%s`b`n",$partner);
				addnews("`\$%s`\$ has left %s`\$ to pursue \"other interests.\"`0",$partner, $session['user']['name']);
				$session['user']['marriedto']=0;
				$session['user']['charm']=0;
			}
		}
		break;
	case "inn":
		addnav("Things to do");
		if ($session['user']['sex']==SEX_MALE){
			addnav(array("F?Flirt with %s", $partner),
					"runmodule.php?module=lovers&op=flirt");
			addnav(array("Chat with %s",translate_inline(getsetting("bard", "`^Seth"))),
					"runmodule.php?module=lovers&op=chat");
		}else{
			addnav(array("F?Flirt with %s", $partner),
					"runmodule.php?module=lovers&op=flirt");
			addnav(array("Gossip with %s",translate_inline(getsetting("barmaid", "`%Violet"))),
					"runmodule.php?module=lovers&op=chat");
		}
		break;
	}
	return $args;
}

function lovers_run(){
	global $session;
	require_once("lib/villagenav.php");
	$iname = getsetting("innname", LOCATION_INN);
	page_header($iname);
	rawoutput("<span style='color: #9900FF'>");
	output_notl("`c`b");
	output($iname);
	output_notl("`b`c");
	switch(httpget('op')){
	case "flirt":
		if ($session['user']['sex']==SEX_MALE) {
			require_once("modules/lovers/lovers_violet.php");
			lovers_violet();
		} else {
			require_once("modules/lovers/lovers_seth.php");
			lovers_seth();
		}
		break;
	case "chat":
		if ($session['user']['sex']==SEX_MALE) {
			require_once("modules/lovers/lovers_chat_seth.php");
			lovers_chat_seth();
		} else {
			require_once("modules/lovers/lovers_chat_violet.php");
			lovers_chat_violet();
		}
		break;
	}
	addnav("Return");
	addnav("I?Return to the Inn","inn.php");
	villagenav();
	rawoutput("</span>");
	page_footer();
}

function lovers_getbuff(){
	global $session;
	$partner = get_partner();
	$buff = array(
		"name"=>"`!Lover's Protection",
		"rounds"=>60,
		"wearoff"=>
		array("`!You miss %s`!.`0",$partner),
		"defmod"=>1.2,
		"roundmsg"=>"Your lover inspires you to keep safe!",
		"schema"=>"module-lovers",
	);
	return $buff;
}
?>
