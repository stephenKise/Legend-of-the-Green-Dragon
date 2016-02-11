<?php
// translator ready
// addnews ready
// mail ready

// Version 1.1 - no longer lifts tempmutes when player resurrects
//             - made it possible to extend temp bans
//             - removed the extra page and added support for returning to whence you came from

function mutemod_getmoduleinfo(){
	$info = array(
		"name"=>"Mute Moderation",
		"version"=>"1.1",
		"author"=>"Sneakabout",
		"category"=>"Administrative",
		"download"=>"core_module",
		"prefs"=>array(
			"Mute Moderation User Preferences,title",
            "muted"=>"Is this person muted?,bool|0",
            "tempmute"=>"For how many days is the player muted for?,int|0",
        )
	);
	return $info;
}

function mutemod_install(){
	module_addhook("insertcomment");
	module_addhook("bioinfo");
	module_addhook("newday");
	return true;
}

function mutemod_uninstall(){
	return true;
}

function mutemod_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
	case "insertcomment":
		if (get_module_pref("muted")) {
			$args['mute']=1;
			$mutemsg="`n`\$You have been muted by the staff for abusing the commentary function. You may not speak until this mute is lifted.`0`n`n";
			$mutemsg=translate_inline($mutemsg);
			$args['mutemsg']=$mutemsg;
		} elseif (get_module_pref("tempmute")) {
			$args['mute']=1;
			$mutemsg="`n`\$You have been temporarily muted by the staff for abusing the commentary function.";
			if (get_module_pref("tempmute") == 1){
				$mutemsg.=" You may speak again after %s day.`0`n`n";
			} else {
				$mutemsg.=" You may speak again after %s days.`0`n`n";
			}
			$mutemsg=sprintf(translate_inline($mutemsg), get_module_pref("tempmute"));
			$args['mutemsg']=$mutemsg;
		}
		break;
	case "bioinfo":
		$char = httpget('char');
		$id = $args['acctid'];
		mutemod_domute($id);
		// Handle the ability for super users to mute/unmute the player.
		if ($session['user']['superuser'] & SU_EDIT_COMMENTS) {
			addnav("Mute Player Options");
			$muted = get_module_pref("muted", false, $id);
			$tmuted = get_module_pref("tempmute", false, $id);
			if ($muted) {
				output("`n`\$This player has been permanently muted!`0`n");
				addnav("U?Un-mute player",
					"bio.php?char=".$args['acctid']."&ret=".rawurlencode(httpget("ret"))."&op=unmute");
			} else {
				addnav("M?Mute player",
					"bio.php?char=".$args['acctid']."&ret=".rawurlencode(httpget("ret"))."&op=mute");
			}
			if ($tmuted) {
				if ($tmuted == 1){
					output("`n`\$This player has been muted for %s day!`0`n", $tmuted);
				} else {
					output("`n`\$This player has been muted for %s days!`0`n", $tmuted);
				}
				addnav("E?Extend tempmute",
					"bio.php?char=".$args['acctid']."&ret=".rawurlencode(httpget("ret"))."&op=exttempmute");
				addnav("n?Un-tempmute player",
					"bio.php?char=".$args['acctid']."&ret=".rawurlencode(httpget("ret"))."&op=untempmute");
			} else {
				addnav("T?Tempmute player",
					"bio.php?char=".$args['acctid']."&ret=".rawurlencode(httpget("ret"))."&op=tempmute");
			}
		}
		break;
	case "newday":
		$tmuted = get_module_pref("tempmute");
		if ($tmuted && $args['resurrection'] != 'true') set_module_pref("tempmute",$tmuted-1);
		break;
	}
	return $args;
}

function mutemod_domute($id){
	global $session;
	$op = httpget('op');
	if (is_module_active("biocomment") && httpget('refresh')) return false;
	if ($op=="mute") {
		set_module_pref("muted",1,false,$id);
		modulehook("mute",array("userid"=>$id, "staffid"=>$session['user']['acctid'], "when"=>date("Y-m-d H:i:s")));
		output("`n`\$This player has now been muted!");
		output("`nThis will last until it is lifted, by you or another member of staff!`0`n");
	} elseif ($op=="unmute") {
		set_module_pref("muted",0,false,$id);
		output("`n`\$This player has now been unmuted!");
		output("`nThey can talk again!`0`n");
	} elseif ($op=="tempmute") {
		set_module_pref("tempmute",1,false,$id);
		modulehook("tempmute",array("userid"=>$id, "staffid"=>$session['user']['acctid'], "length"=>1,"when"=>date("Y-m-d H:i:s")));
		output("`n`\$This player has now been muted temporarily!");
		output("`nThey cannot talk until the next new day!`0`n");
	} elseif ($op=="untempmute") {
		set_module_pref("tempmute",0,false,$id);
		output("`n`\$This player has now been unmuted (from a temporary mute)!");
		output("`nThey can talk again!`0`n");
	} elseif ($op=="exttempmute") {
		$tmuted = get_module_pref("tempmute",false,$id)+1;
		set_module_pref("tempmute",$tmuted,false,$id);
		modulehook("tempmute",array("userid"=>$id, "staffid"=>$session['user']['acctid'], "length"=>$tmuted,"when"=>date("Y-m-d H:i:s")));
		output("`n`\$This player´s tempory mute has been extended!");
		output("`nThey cannot talk until %s days have passed!`0`n", $tmuted);
	}
}

function mutemod_run(){
}

?>
