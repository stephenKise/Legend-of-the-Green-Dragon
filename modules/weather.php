<?php
// translator ready
// addnews ready
// mail ready

/*Weather, version 2.5
- Added weather display in gardens
- Added climate for shades
*/
function weather_getmoduleinfo(){
	$info = array(
		"name"=>"Weather",
		"author"=>"`4Talisman",
		"version"=>"2.5",
		"category"=>"General",
		"download"=>"core_module",
		"settings"=>array(
			"Normal Weather Settings,title",
			"wxreport"=>"Village weather message|`n`&Today's weather is expected to be `^%s`&.`n",
			"weather"=>"Current Weather|sunny",
			"weather1"=>"Weather Condition 1|overcast and cool, with sunny periods",
			"weather2"=>"Weather Condition 2|warm and sunny",
			"weather3"=>"Weather Condition 3|rainy",
			"weather4"=>"Weather Condition 4|foggy",
			"weather5"=>"Weather Condition 5|cool with blue skies",
			"weather6"=>"Weather Condition 6|hot and sunny",
			"weather7"=>"Weather Condition 7|high winds with scattered showers",
			"weather8"=>"Weather Condition 8|thundershowers",
			"Micro Climate Settings, title",
			"enablemicro"=>"Enable Unique Climate Location,bool|0",
			"microloc"=>"Unique Climate Location,location|".getsetting("villagename", LOCATION_FIELDS),
			"microwxreport"=>"Unique Climate weather message|`n`&The weather elf is predicting `^%s`& today.`n",
			"microwx"=>"Current Weather|snow flurries",
			"microwx1"=>"Custom Weather 1|snow flurries",
			"microwx2"=>"Custom Weather 2|clear and cold",
			"microwx3"=>"Custom Weather 3|snow blizzards",
			"microwx4"=>"Custom Weather 4|frost",
			"microwx5"=>"Custom Weather 5|soft falling snow",
			"microwx6"=>"Custom Weather 6|some great skiing weather",
			"microwx7"=>"Custom Weather 7|a possibility of snow",
			"microwx8"=>"Custom Weather 8|fog and frost",
			"Shades Weather Settings,title",
			"enableshades"=>"Enable Shades Climate Conditions,bool|1",
			"shadeswxreport"=>"Shades weather message|`n`7The atmosphere in Shades is currently `^%s`&.`n`n",
			"shadeswx"=>"Current Weather|`Qraining fire and brimstone",
			"shadeswx1"=>"Weather Condition 1|`7a thick, bitter fog",
			"shadeswx2"=>"Weather Condition 2|`Qraining fire and brimstone",
			"shadeswx3"=>"Weather Condition 3|`7dark, dank and depressingly dismal",
			"shadeswx4"=>"Weather Condition 4|`#suffering from acidic rainfall",
			"shadeswx5"=>"Weather Condition 5,viewonly|`#frozen over",
			"shadeswx6"=>"Weather Condition 6|`qprone to cyclonic dust devils",
			"shadeswx7"=>"Weather Condition 7|`6oppresively hot and uncomfortably muggy",
			"shadeswx8"=>"Weather Condition 8|`^blowing winds of hellfire",
			"counter"=>"Hell Has Frozen over counter,int|0",
			),
		"prefs"=>array(
			"Weather in Shades User Setting, title",
			"gotfight"=>"Received extra torment today,int|0",
		)
	);
	return $info;
}

function weather_test() {
	global $session;
	$city = get_module_setting("specwx", "weather");
	if ($city != $session['user']['location']) return 0;
	return 1;
}

function weather_install(){
	module_addhook("newday-runonce");
	module_addhook("newday");
 	module_addhook("village");
 	module_addhook("gardens");
 	module_addhook("shades");
	module_addhook("index");
   return true;
}

function weather_uninstall(){
	return true;
}

function weather_dohook($hookname,$args){

	switch($hookname){
	case "newday-runonce":
		$wx=(e_rand(1,8));
		$fetchwx="weather$wx";
		$gotwx = get_module_setting("$fetchwx", "weather");

		$custwx=(e_rand(1,8));
		$fetchwxa="microwx$custwx";
		$gotmwx = get_module_setting("$fetchwxa", "weather");

		$shadeswx=(e_rand(1,8));
		if ($shadeswx==5){
			$counter=get_module_setting("counter");
			$counter++;
			set_module_setting("counter",$counter);
		}
		$fetchwxb="shadeswx$shadeswx";
		$gotswx = get_module_setting("$fetchwxb", "weather");

		set_module_setting("weather",$gotwx);
		set_module_setting("microwx",$gotmwx);
		set_module_setting("shadeswx",$gotswx);

		break;

	case "newday":
		$clouds = get_module_setting("weather");
		$tclouds = translate_inline($clouds);
		$snow = get_module_setting("microwx");
		$tsnow = translate_inline($snow);

		output("`n`@From the ache in your battle weary bones, you know today's weather will be `^%s`@.`n", $tclouds);
		if ((get_module_setting("shadeswx")=="`#frozen over.") && (get_module_setting("counter") > get_module_pref("gotfight"))){
			output("`nShades has frozen over, and you may find the energy for an extra torment should you visit %s!`n", getsetting("deathoverlord", '`$Ramius'));
			$counter=get_module_setting("counter");
			set_module_pref("gotfight",$counter);
		}

		break;

	case "gardens":
  		global $session;
			$clouds = translate_inline(get_module_setting("weather"));
			$wxtext = get_module_setting("wxreport");
			output($wxtext, $clouds);
			// the gardens wants more whitespace.
			output_notl("`n");
			break;

	case "shades":
  		global $session;
  			$clouds = translate_inline(get_module_setting("shadeswx"));
			$wxtext = get_module_setting("shadeswxreport");
			output($wxtext, $clouds);
			break;

	case "village":
  		global $session;
  		$enablemicro = get_module_setting("enablemicro", "weather");
		$microloc = get_module_setting("microloc", "weather");
		if (($microloc==$session['user']['location']) && ($enablemicro==1)){
			$snow = translate_inline(get_module_setting("microwx"));
			$wxtext = get_module_setting("microwxreport");
			output($wxtext, $snow);
			break;
		}else{
			$clouds = translate_inline(get_module_setting("weather"));
			$wxtext = get_module_setting("wxreport");
			output($wxtext, $clouds);
			break;
		}

	case "index":
		$clouds = translate_inline(get_module_setting("weather"));
		$wxtext = get_module_setting("wxreport");
		output($wxtext, $clouds);
		output("`n");
		break;
	}

	return $args;
}

function weather_runevent($type){
}

function weather_run(){
}

?>
