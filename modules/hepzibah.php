<?php
// addnews ready
// mail ready
// translator ready

/* Hepzibah the Spook */
/* ver 1.0 by Shannon Brown => SaucyWench -at- gmail -dot- com */
/* 8th Sept 2004 */


require_once("lib/villagenav.php");
require_once("lib/http.php");

function hepzibah_getmoduleinfo(){
    $info = array(
        "name"=>"Hepzibah the Spook",
        "version"=>"1.0",
        "author"=>"Shannon Brown",
        "category"=>"Village Specials",
        "download"=>"core_module",
        "settings"=>array(
            "Hepzibah the Spook - Settings,title",
			"hepzibahloc"=>"Where does the Hepzibah appear,location|".getsetting("villagename", LOCATION_FIELDS)
        )
    );
    return $info;
}

function hepzibah_test() {
	// I'm guessing this is what you really wanted to do Saucy.
	global $session;
	$city = get_module_setting("hepzibahloc", "hepzibah");
	if ($city != $session['user']['location']) return 0;
	return (max(1, (500-$session['user']['dragonkills'])/5));
}

function hepzibah_install(){
	module_addhook("changesetting");
	module_addeventhook("village", "require_once(\"modules/hepzibah.php\"); return hepzibah_test();");
	// This doesn't work
	//
	//module_addeventhook("village", "return (max(1,(500-\$session['user']['dragonkills'])/2));");
	// only in Esoterra
	//if ($session['user']['location'] != get_module_setting("hepzibahloc")) $aloc=1;
	//module_addeventhook("village","\$aloc=get_module_setting(\"aloc\", \"hepzibah\");return (\$aloc?0:100);");
    return true;
}

function hepzibah_uninstall(){
    return true;
}

function hepzibah_dohook($hookname,$args){
    global $session;
    switch($hookname){
    	case "changesetting":
			if ($args['setting'] == "villagename") {
				if ($args['old'] == get_module_setting("hepzibahloc")) {
					set_module_setting("hepzibahloc", $args['new']);
				}
			}
			break;
		}
    return $args;
}

function hepzibah_runevent($type) {
    global $session;
	$from = "village.php?";
	// Since there is no interaction here, don't even set this
    //$session['user']['specialinc'] = "module:Hepzibah";
	$voucher = get_module_pref("voucher","marquee");
	$city = $session['user']['location'];
    $op = httpget('op');

	// Since the text in both cases is mostly the same, make it common
	output("`7As you're walking around, admiring the sights, a wizened old woman approaches.`n`n");
	output("Her greying hair stands out in shock, and her nose is hooked and gnarled.");
	output("It takes all your willpower not to run away from this ghastly sight.`n`n");
	output("She smiles the most evil looking smile you have ever encountered.`n`n");
	output("`&\"Hello, warrior!");

	if (!is_module_installed("marquee") || $voucher) {
		output("Enjoying your visit?\"`n`n");
		output("`7Before you can answer, she has wandered off towards another tourist.`n`n");
		output("`7You shudder and head away quickly.`n`n");
	}elseif ($op == "" && !$voucher) {
		output("For you, a gift!\"`n`n");
		output("`7Before you can protest, she has grabbed your wrist, and placed a small voucher into your hand.");
		output("It reads, `Q\"One Free Pizza at the Marquee\".`n`n");
		output("`&\"Enjoy your stay in $city, warrior!\" `7she says, before wandering off towards another tourist.`n`n");
		set_module_pref("voucher",1,"marquee");
	}
	// Since we never set the special inc, we don't need to unset it.
	//if ($op != "") {
    //    $session['user']['specialinc'] = "";
	//}
}
?>
