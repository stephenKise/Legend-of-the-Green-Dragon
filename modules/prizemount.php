<?php
// addnews ready
// translator ready
// mail ready

require_once("lib/systemmail.php");

function prizemount_getlist(){
	$mounts=",0,None";
	// The table lock is necessary since some place where it can be called
	// from already have a lock and if we don't lock we'll error there.
	db_query("LOCK TABLES ".db_prefix("mounts")." WRITE");
	$sql = "SELECT mountid,mountname,mountcategory FROM " .
		db_prefix("mounts") .  " ORDER BY mountcategory,mountid";
	$result = db_query($sql);
	//unlock it now, since we are done.
	db_query("UNLOCK TABLES");

	while ($row = db_fetch_assoc($result)){
		$mounts.="," . $row['mountid'] . "," . $row['mountcategory'] .
			": ". color_sanitize($row['mountname']) . "[" . $row['mountid'] . "]";
	}
	return $mounts;
}

function prizemount_getmoduleinfo(){
	$info = array(
		"name"=>"Prize Mount",
		"author"=>"JT Traub<br>w/ minor modifications by Chris Vorndran",
		"version"=>"1.15",
		"category"=>"General",
        "download"=>"core_module",
		"settings"=>array(
			"Prize Mount Module Settings,title",
			"mountid"=>"Which mount to award to players who have days?,enum".prizemount_getlist()."|0",
			"daysper5"=>"How many game days are awarded per $5 donated?,range,2,10,1|3",
			"awarding"=>"Are prize mounts being handed out?,bool|0",
		),
		"prefs"=>array(
			"Prize Mount User Preferences,title",
			"oldmount"=>"Id of old mount,viewonly",
			"daysleft"=>"How many days will the user get a special mount?,int|0",
			"carryed"=>"Dollars donated not used on mount,float|0",
			"user_reject"=>"Opt to not recieve the prize mount on a donation,bool|0",
		),
	);
	return $info;
}

function prizemount_install(){
	module_addhook("pre-newday");
	module_addhook("donation");
	module_addhook("footer-stables");
	module_addhook("header-stables");
	return true;
}
function prizemount_uninstall(){
	return true;
}

function prizemount_dohook($hookname,$args){
	global $session, $playermount;
	$newhorse = get_module_setting("mountid");

	// Do nothing if a prize mount id hasn't been set up!
	if ($newhorse == 0) return $args;

	// Mount Upgrades Interface
	// Mounts which upgrade from a prizemount are considered prizemounts.
	$prizemounts=array($newhorse => 1);
	while($newhorse && is_module_active("mountupgrade")){
		$newhorse=get_module_objpref("mounts",$newhorse,"upgradeto","mountupgrade");
		if ($newhorse) $prizemounts[$newhorse]=1;
	}
	switch($hookname){
	case "header-stables":
		if (array_key_exists($session['user']['hashorse'],$prizemounts)) {
			blocknav("stables.php?op=buymount", true);
			blocknav("stables.php?op=sellmount", true);
		}
		break;
	case "footer-stables":
		if (array_key_exists($session['user']['hashorse'],$prizemounts)) {
			blocknav("stables.php?op=buymount", true);
			blocknav("stables.php?op=sellmount", true);
			$op = httpget("op");
			if ($op == "examine") {
				output("`n`7While the creature you are examining is beautiful, you realize you cannot bear to part with your special mount.`n");
				blocknav("stables.php?op=buymount", true);
			} elseif ($op == "") {
				output("`n`7Regardless of how tempting the offer is, you know you cannot bear to part with your special mount.`n");
				blocknav("stables.php?op=sellmount", true);
			}
		}
		break;
	case "donation":
		if (!get_module_setting("awarding")) break;
		if (get_module_pref("user_reject", "prizemount", $args['id'])) break;
		$add = get_module_pref("carryed","prizemount", $args['id']);
		while ($add >= 5) {
			$add -= 5;
		}
		$add += $amt>500?$amt % 500:0;
		$amt = $args['amt']; // This amount is in donator points, not dollars
		// 500 is $5.00
		$adddays = floor($amt/500) * get_module_setting("daysper5");
		if ($add >= 5) {
			$adddays += get_module_setting("daysper5");
			$add -= 5;
		}
		if ($adddays == 0) break;
		$curdays = get_module_pref("daysleft", "prizemount", $args['id']);
		if ($adddays < 0) {
			$adddays = (abs($adddays) > $curdays) ? -$curdays : $adddays;
			systemmail($args['id'], array("Donation reversed!"),
				array("A previously made donation of \$%s has been reversed by PayPal.  The game is therefore removing %s days from those you have remaining on your prize mount.  If this causes the expiration of your prizemount, you will keep it for the rest of this game day and your previous mount will be returned to you on the next new day.", round($amt/100, 2), abs($adddays)));
		} else {
			if ($curdays) {
				systemmail($args['id'], array("Donation recorded!"),
					array("You have been awarded %s additional game days use of the prize mount for your donation of \$%s.  Thank you for your donation.", $adddays, round($amt/100, 2)));
			} else {
				systemmail($args['id'], array("Donation recorded!"),
					array("You have been awarded %s game days use of the prize mount for your donation of \$%s.  Your uses will begin on your next new day.  Thank you for your donation.", $adddays, round($amt/100, 2)));
			}
		}
		$days = $curdays + $adddays;
		set_module_pref("daysleft", $days, "prizemount", $args['id']);
		set_module_pref("carryed", $add, "prizemount", $args['id']);
		break;
	case "pre-newday":
		$days = get_module_pref("daysleft");
		if ($days == 0) {
			// We either have no prize ever, or we need to restore the old
			// mount
			$id = get_module_pref("oldmount");
			if ($id !== NULL) {
				// They had an old mount
				// Delete the marker
				$sql = "DELETE FROM " . db_prefix("module_userprefs") . " WHERE modulename='prizemount' AND setting='oldmount' AND userid='{$session['user']['acctid']}'";
				db_query($sql);
				// Give them back their old mount
				modulehook("loseprizemount");
				$session['user']['hashorse'] = $id;
				$playermount = getmount($session['user']['hashorse']);
				// Handle the renaming of named mounts
				modulehook("stable-mount");
			} else {
				// They didn't have a prize mount, do nothing
			}
		} else {
			$id = get_module_pref("oldmount");
			if ($id === NULL) {
				// This is first newday after getting a prize mount
				set_module_pref("oldmount", $session['user']['hashorse']);
				$prizemount=get_module_setting("mountid");
				// Args to a hook MUST be an array
				$args = array('prizemount'=>$prizemount);
				$args = modulehook("gainprizemount", $args);
				$session['user']['hashorse'] = $args['prizemount'];
				$playermount = getmount($session['user']['hashorse']);
				// Handle the renaming of named mounts
				modulehook("stable-mount");
			} else {
				// They have had the prize mount for a while, and it's
				// still valid, do nothing.
			}
			$days--;
			set_module_pref("daysleft", $days);
			if ($days == 0) {
				output("`n`&This is your last game day for your awarded mount.`0`n`n");
			} else {
				output("`n`&You have %s additional game %s left on your awarded mount.`0`n`n",
						$days, translate_inline($days == 1? "day" : "days"));
			}
		}
		break;
	}
	return $args;
}

function prizemount_run(){
}
?>
