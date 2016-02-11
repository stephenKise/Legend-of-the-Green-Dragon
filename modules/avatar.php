<?php

function avatar_getmoduleinfo()
{
	$info = array(
		"name" => "Bio Avatars",
		"version" => "1.0",
		"author" => "JT Traub",
		"category" => "Lodge",
		"download" => "core_module",
		"settings" => array(
			"Bio Avatar Settings,title",
			"cost"=>"What is the cost of having an avatar?,int|500",
			"changecost"=>"What is the cost of changing your avatar?,int|25",
		),
		"prefs"=>array(
			"Bio Avatar User Preferences,title",
			"bought"=>"Has the player bought an avatar yet?,bool,0",
			"setname"=>"Which set is the player using?|vixy1",
			"user_seeavatar"=>"Show avatars in user bios?,bool|1",
		),
	);
	return $info;
}

function avatar_install()
{
	module_addhook("lodge");
	module_addhook("pointsdesc");
	// Let's get our hook at the top.
	module_addhook_priority("biotop", 20);
	return true;
}

function avatar_uninstall()
{
	return true;
}

function avatar_getimage($race, $gender, $set)
{
	$usedefault = 0;
	$file = "modules/avatar/$set/$race-$gender.gif";
	if (!file_exists($file)) {
		$usedefault = 1;
		$file = "modules/avatar/default.gif";
	}
	$image = "<center><img align='center' src='$file'></center>";
	$l = translate_inline("Licensed for use in LoTGD");
	if (!$usedefault) {
		require("modules/avatar/$set/setinfo.php");
		$image .= "<br><center>$setcopy<br>$l</center>";
	}
	return $image;
}

function avatar_dohook($hookname, $args)
{
	$cost = get_module_setting("cost");
	$changecost = get_module_setting("changecost");
	switch($hookname) {
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$str = translate("For %s points, you will get an avatar picture to display on your bio page.  You can change it to a different avatar at a later time for %s additional points.");
		$str = sprintf($str, $cost, $changecost);
		output($format, $str, true);
		break;
	case "lodge":
		if (!get_module_pref("bought")) {
			addnav(array("Bio Avatar Picture (%s %s)", $cost,
					translate_inline($cost == 1 ? "point" : "points")),
				"runmodule.php?module=avatar&op=purchase&cost=$changecost");
		} else {
			addnav(array("Change Avatar Picture (%s %s)", $changecost,
					translate_inline($changecost == 1 ? "point" : "points")),
				"runmodule.php?module=avatar&op=purchase&cost=$changecost");
		}
		addnav("Gallery");
		addnav("View Bio Avatar Gallery", "runmodule.php?module=avatar&op=view");
		break;
	case "biotop":
		if (get_module_pref("user_seeavatar") &&
				get_module_pref("bought", "avatar", $args['acctid'])) {
			$set = get_module_pref("setname", "avatar", $args['acctid']);
			$race = strtolower($args['race']);
			if ($args['sex'] == SEX_MALE) {
				$gender = "male";
			} else {
				$gender = "female";
			}
			$image = avatar_getimage($race, $gender, $set);

			// Make the avatar image collapsible away.  Some people view the
			// game from work and having the avatar image makes it VERY
			// obviously a non-work site even in work-friendly skins
			// addnavheader("Avatar", false);
			addnavheader("Avatar");

			addnav("$image","!!!addraw!!!",true);
		}
		break;
	}
	return $args;
}

function avatar_showimages($set)
{
	$races = modulehook("racenames");
	rawoutput("<table cellpadding='0' cellspacing='0' border='0' bgcolor='#999999'>");
	$r = translate_inline("Race");
	$m = translate_inline("Male Image");
	$f = translate_inline("Female Image");
	rawoutput("<tr class='trhead'><th>$r</th><th>$m</th><th>$f</th></tr>");
	$i = 0;
	foreach ($races as $key=>$race) {
		$r = strtolower($race);
		$imm = avatar_getimage($r, "male", $set);
		$imf = avatar_getimage($r, "female", $set);
		rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
		rawoutput("<th>");
		output_notl('`^');
		output($race);
		output_notl("`0");
		rawoutput("</th><td>");
		rawoutput($imm);
		rawoutput("</td><td>");
		rawoutput($imf);
		rawoutput("</td>");
		rawoutput("</tr>");
		$i++;
	}
	rawoutput("</table>");
}

function avatar_showsets()
{
	$setnames = array();
	$setdirs = array();

	addnav("Image sets");
	$dir = "modules/avatar";
	$d = opendir($dir);
	while (($file = readdir($d)) !== false) {
		if ($file[0] == '.') continue;
		if (is_dir($dir . "/" . $file)) {
			// okay, this is a possible set
			$f = $dir . "/" . $file . "/" . "setinfo.php";
			if (file_exists($f)) {
				require($f);
				$setnames[$setindex] = $setname;
				$setdirs[$setindex] = $file;
			}
		}
	}
	closedir($d);

	// Now display the sets in order.
	ksort($setnames);
	ksort($setdirs);
	reset($setdirs);
	while(list($key, $val) = each($setdirs)) {
		addnav($setnames[$key],
				"runmodule.php?module=avatar&op=view&set=$val");
	}
}

function avatar_get_all_images($race, $gender, $selset, $button)
{
	$setnames = array();
	$setdirs = array();

	$dir = "modules/avatar";
	$d = opendir($dir);
	while (($file = readdir($d)) !== false) {
		if ($file[0] == '.') continue;
		if (is_dir($dir . "/" . $file)) {
			// okay, this is a possible set
			$f = $dir . "/" . $file . "/" . "setinfo.php";
			if (file_exists($f)) {
				require($f);
				$setnames[$setindex] = $setname;
				$setdirs[$setindex] = $file;
			}
		}
	}
	closedir($d);

	// Now display the sets in order.
	ksort($setnames);
	ksort($setdirs);
	reset($setdirs);
	$str = "<table border=0>";
	while(list($key, $val) = each($setdirs)) {
		$str .= "<tr>";
		// We are going to do three per row here
		$str .= "<td>" . $setnames[$key] .
			"<br /><input type='radio' name='set' value='" .
			$setdirs[$key] . "'";
		if ($setdirs[$key] == $selset) $str .= " checked";
		$str .= "></td><td>";
		$str .= avatar_getimage($race, $gender, $setdirs[$key]);
		$str .= "</td>";

		// second
		if(list($key, $val) = each($setdirs)) {
			$str .= "<td>" . $setnames[$key] .
				"<br /><input type='radio' name='set' value='" .
				$setdirs[$key]."'";
			if ($setdirs[$key] == $selset) $str .= " checked";
			$str .= "></td><td>";
			$str .= avatar_getimage($race, $gender, $setdirs[$key]);
			$str .= "</td>";
		} else {
			$str .= "<td>&nbsp;</td><td>&nbsp;</td>";
		}

		// third 
		if(list($key, $val) = each($setdirs)) {
			$str .= "<td>" . $setnames[$key] .
				"<br /><input type='radio' name='set' value='" .
				$setdirs[$key]."'";
			if ($setdirs[$key] == $selset) $str .= " checked";
			$str .= "></td><td>";
			$str .= avatar_getimage($race, $gender, $setdirs[$key]);
			$str .= "</td>";
		} else {
			$str .= "<td>&nbsp;</td><td>&nbsp;</td>";
		}

		$str .= "</tr>";
	}

	if ($button !== false) {
		$str .= "<tr><td colspan=6 align=center>";
		$str .= "<input type='submit' class='button' value='$button'>";
		$str .= "</td></tr>";
	}
	$str .= "</table>";
	return $str;
}

function avatar_run()
{
	global $session;
	page_header("Hunter's Lodge");
	$op = httpget("op");

	switch ($op) {
	case "purchase":
		$cost = httpget("cost");
		$pointsavail = $session['user']['donation'] -
			$session['user']['donationspent'];
		output("`7J. C. Petersen leads you into a back room filled with portraits, and guides you over to a group of images for your race.`n`n");

		output("`n`7J. C. Petersen smiles, \"`&Of course, each race has its own set of images, but these are the possible ones that you can choose right now.  Your image will always reflect your current race.`7\"`n`n");
		output("If you would like to see all of the images, feel free to look around the gallery.`n`n");
		addnav("Gallery", "runmodule.php?module=avatar&op=view");

		if ($pointsavail < $cost) {
			if (!get_module_pref("bought")) {
				output("`7He glances at hs ledger, \"`&Unfortunately, purchasing a portrait will cost you %s points, and you currently only have %s %s available to spend.`7\"", $cost, $pointsavail, translate_inline($pointsavail==1?"point":"points"));
			} else {
				output("`7He glances at hs ledger, \"`&Unfortunately, changing your portrait will cost you %s points, and you currently only have %s %s available to spend.`7\"", $cost, $pointsavail, translate_inline($pointsavail==1?"point":"points"));
			}
		} else {
			output("`7He steps back to let you admire the pictures for a moment, \"`&So, does one of these suit you?`7\"");
		}
		$race = strtolower($session['user']['race']);
		if ($session['user']['sex'] == SEX_MALE) {
			$gender = "male";
		} else {
			$gender = "female";
		}
		$button = false;
		if ($pointsavail >= $cost) {
			$button = "Purchase";
			if (get_module_pref("bought")) $button= "Change";
			$button = translate_inline($button);
		}

		$set = get_module_pref("setname");
		rawoutput("<form method='POST' action='runmodule.php?module=avatar&op=yes&cost=$cost'>");
		$image = avatar_get_all_images($race, $gender, $set, $button);
		rawoutput($image);
		rawoutput("</form>");
		addnav("", "runmodule.php?module=avatar&op=yes&cost=$cost");
		break;
	case "view":
		$set = httpget("set");
		$cost = get_module_setting("cost");
		$ccost = get_module_setting("changecost");
		addnav("Purchase");
		if (!get_module_pref("bought")) {
			addnav(array("Purchase Avatar (%s %s)", $cost,
					translate_inline($cost == 1 ? "point" : "points")),
				"runmodule.php?module=avatar&op=purchase&cost=$cost");
		} else {
			addnav(array("Change Avatar (%s %s)", $ccost,
					translate_inline($ccost == 1 ?  "point" : "points")),
					"runmodule.php?module=avatar&op=purchase&cost=$ccost");
		}
		if (!$set) {
			output("As you look around the room, you see different groups of images.");
			output("Which one would you like to look at?`n`n");
			avatar_showsets();
		} else {
			output("You step over to view the set of images which caught your eye.`n`n");
			avatar_showimages($set);
			avatar_showsets();
		}
		break;
	case "yes":
		$cost = httpget("cost");
		$set = httppost("set");
		output("`7J. C. Petersen grins broadly, \"`&Excellent.  I'll take care of that for you right now.`7\"");
		$session['user']['donationspent'] += $cost;
		set_module_pref("bought", 1);
		set_module_pref("setname", $set);
		break;
	}

	addnav("Return");
	addnav("L?Return to Lodge", "lodge.php");
	page_footer();
}

?>
