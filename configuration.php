<?php
require_once("common.php");
require_once("lib/showform.php");
require_once("lib/http.php");
// translator ready
// addnews ready
// mail ready

check_su_access(SU_EDIT_CONFIG);

tlschema("configuration");

$op = httpget('op');
$module=httpget('module');
if ($op=="save"){
	include_once("lib/gamelog.php");
	//loadsettings();
	if ((int)httppost('blockdupemail') == 1 &&
			(int)httppost('requirevalidemail') != 1) {
		httppostset('requirevalidemail', "1");
		output("`brequirevalidemail has been set since blockdupemail was set.`b`n");
	}
	if ((int)httppost('requirevalidemail') == 1 &&
			(int)httppost('requireemail') != 1) {
		httppostset('requireemail', "1");
		output("`brequireemail has been set since requirevalidemail was set.`b`n");
	}
	$defsup = httppost("defaultsuperuser");
	if ($defsup != "") {
		$value = 0;
		while(list($k, $v)=each($defsup)) {
			if ($v) $value += (int)$k;
		}
		httppostset('defaultsuperuser', $value);
	}
	$tmp = stripslashes(httppost("villagename"));
	if ($tmp && $tmp != $settings['villagename']) {
		debug("Updating village name -- moving players");
		$sql = "UPDATE " . db_prefix("accounts") . " SET location='".
			httppost("villagename") . "' WHERE location='" .
			addslashes($settings['villagename']) . "'";
		db_query($sql);
		if ($session['user']['location'] == $settings['villagename'])
			$session['user']['location'] =
				stripslashes(httppost('villagename'));
		debug("Moving companions");
		$sql = "UPDATE " . db_prefix("companions") . " SET companionlocation = '".
			httppost("villagename") . "' WHERE companionlocation = '".
			addslashes($settings['villagename']) . "'";
		db_query($sql);
	}
	$tmp = stripslashes(httppost("innname"));
	if ($tmp && $tmp != $settings['innname']) {
		debug("Updating inn name -- moving players");
		$sql = "UPDATE " . db_prefix("accounts") . " SET location='".
			httppost("innname") . "' WHERE location='" .
			addslashes($settings['innname']) . "'";
		db_query($sql);
		if ($session['user']['location'] == $settings['innname'])
			$session['user']['location'] = stripslashes(httppost('innname'));
	}
	if (stripslashes(httppost("motditems")) != $settings['motditems']) {
		invalidatedatacache("motd");
	}
	$post = httpallpost();
	reset($post);
	$old=$settings;
	while (list($key,$val)=each($post)){
		if (!isset($settings[$key]) ||
				(stripslashes($val) != $settings[$key])) {
			if (!isset($old[$key]))
				$old[$key] = "";
			savesetting($key,stripslashes($val));
			output("Setting %s to %s`n", $key, stripslashes($val));
			gamelog("`@Changed core setting `^$key`@ from `#{$old[$key]}`@ to `&$val`0","settings");
			// Notify every module
			modulehook("changesetting",
					array("module"=>"core", "setting"=>$key,
						"old"=>$old[$key], "new"=>$val), true);
		}
	}
	output("`^Settings saved.`0");
	$op = "";
	httpset($op, "");
}elseif($op=="modulesettings"){
	include_once("lib/gamelog.php");
	if (injectmodule($module,true)){
		$save = httpget('save');
		if ($save!=""){
			load_module_settings($module);
			$old = $module_settings[$module];
			$post = httpallpost();
			$post = modulehook("validatesettings", $post, true, $module);
			if (isset($post['validation_error'])) {
				$post['validation_error'] =
					translate_inline($post['validation_error']);
				output("Unable to change settings:`\$%s`0",
						$post['validation_error']);
			} else {
				reset($post);
				while (list($key,$val)=each($post)){
					$key = stripslashes($key);
					$val = stripslashes($val);
					set_module_setting($key,$val);
					if (!isset($old[$key]) || $old[$key] != $val) {
						output("Setting %s to %s`n", $key, $val);
						// Notify modules
						if($key == "villagename") {
							debug("Moving companions");
							$sql = "UPDATE " . db_prefix("companions") . " SET companionlocation = '".
								addslashes($val) . "' WHERE companionlocation = '".
								addslashes($old[$key]) . "'";
							db_query($sql);
						}
						$oldval = "";
						if (isset($old[$key])) $oldval = $old[$key];
						gamelog("`@Changed module(`5$module`@) setting `^$key`@ from `#$oldval`@ to `&$val`0","settings");
						modulehook("changesetting",
								array("module"=>$module, "setting"=>$key,
									"old"=>$oldval, "new"=>$val), true);
					}
				}
				output("`^Module %s settings saved.`0`n", $module);
			}
			$save = "";
			httpset('save', "");
		}
		if ($save == "") {
			$info = get_module_info($module);
			if (count($info['settings'])>0){
				load_module_settings($mostrecentmodule);
				$msettings=array();
				while (list($key,$val)=each($info['settings'])){
					if (is_array($val)) {
						$v = $val[0];
						$x = explode("|", $v);
						$val[0] = $x[0];
						$x[0] = $val;
					} else {
						$x = explode("|",$val);
					}
					$msettings[$key]=$x[0];
					if (!isset($module_settings[$mostrecentmodule][$key]) &&
							isset($x[1])) {
						$module_settings[$mostrecentmodule][$key]=$x[1];
					}
				}
				$msettings = modulehook("mod-dyn-settings", $msettings);
				if (is_module_active($module)){
					output("This module is currently active: ");
					$deactivate = translate_inline("Deactivate");
					rawoutput("<a href='modules.php?op=deactivate&module={$module}&cat={$info['category']}'>");
					output_notl($deactivate);
					rawoutput("</a>");
					addnav("","modules.php?op=deactivate&module={$module}&cat={$info['category']}");
				}else{
					output("This module is currently deactivated: ");
					$deactivate = translate_inline("Activate");
					rawoutput("<a href='modules.php?op=activate&module={$module}&cat={$info['category']}'>");
					output_notl($deactivate);
					rawoutput("</a>");
					addnav("","modules.php?op=activate&module={$module}&cat={$info['category']}");
				}
				rawoutput("<form action='configuration.php?op=modulesettings&module=$module&save=1' method='POST'>",true);
				addnav("","configuration.php?op=modulesettings&module=$module&save=1");
				tlschema("module-$module");
				showform($msettings,$module_settings[$mostrecentmodule]);
				tlschema();
				rawoutput("</form>",true);
			}else{
				output("The %s module does not appear to define any module settings.", $module);
			}
		}
	}else{
		output("I was not able to inject the module %s. Sorry it didn't work out.", htmlentities($module, ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
	}
}

page_header("Game Settings");
require_once("lib/superusernav.php");
superusernav();
addnav("Module Manager", "modules.php");
if ($module) {
	$cat = $info['category'];
	addnav(array("Module Category - `^%s`0", translate_inline($cat)), "modules.php?cat=$cat");
}

addnav("Game Settings");
addnav("Standard settings", "configuration.php");
addnav("",$REQUEST_URI);

module_editor_navs('settings', 'configuration.php?op=modulesettings&module=');

if ($op == "") {
	$enum="enumpretrans";
	require_once("lib/datetime.php");
	$details = gametimedetails();
	$offset = getsetting("gameoffsetseconds",0);
	for ($i=0;$i<=86400 / getsetting("daysperday",4);$i+=300){
		$off = ($details['realsecstotomorrow'] - ($offset - $i));
		if ($off < 0) $off += 86400;
		$x = strtotime("+".$off." secs");
        $str = sprintf_translate("In %s at %s (+%s)",
                reltime($x), date("h:i a", $x),date("H:i",$i));
		$enum.=",$i,$str";
	}
	rawoutput(tlbutton_clear());
	$setup = array(
		"Game Setup,title",
		"loginbanner"=>"Login Banner (under login prompt: 255 chars)",
		"maxonline"=>"Max # of players online (0 for unlimited), int",
		"allowcreation"=>"Allow creation of new characters,bool",
		"gameadminemail"=>"Admin Email",
		"emailpetitions"=>"Should submitted petitions be emailed to Admin Email address?,bool",
		"Enter languages here like this: `i(shortname 2 chars) comma (readable name of the language)`i and continue as long as you wish,note",
		"serverlanguages"=>"Languages available on this server",
		"defaultlanguage"=>"Default Language,enum,".getsetting("serverlanguages","en,English,fr,Français,dk,Danish,de,Deutsch,es,Español,it,Italian"),
		"edittitles"=>"Should DK titles be editable in user editor,bool",
		"motditems"=>"How many items should be shown on the motdlist,int",

		"Main Page Display,title",
		"homeskinselect"=>"Should the skin selection widget be shown?,bool",
		"homecurtime"=>"Should the current realm time be shown?,bool",
		"homenewdaytime"=>"Should the time till newday be shown?,bool",
		"homenewestplayer"=>"Should the newest player be shown?,bool",
		"defaultskin"=>"What skin should be the default?,theme",
		"impressum"=>"Tell the world something about the person running this server. (e.g. name and address),textarea",

		"Beta Setup,title",
		"beta"=>"Enable beta features for all players?,bool",
		"betaperplayer"=>"Enable beta features per player?,bool",

		"Account Creation,title",
		"defaultsuperuser"=>
			"Flags automatically granted to new players,bitfield," .
			($session['user']['superuser'] | SU_ANYONE_CAN_SET)." ,".
			SU_INFINITE_DAYS.",Infinite Days,".
			SU_VIEW_SOURCE.",View Source Code,".
			SU_DEVELOPER.",Developer Super Powers (special inc list; god mode; auto defeat master; etc),".
			SU_DEBUG_OUTPUT. ",Debug Output",
		"newplayerstartgold"=>"Amount of gold to start a new character with,int",
		"maxrestartgold"=>"Maximum amount of gold a player will get after a dragonkill,int",
		"maxrestartgems"=>"Maximum number of gems a player will get after a dragonkill,int",
		"requireemail"=>"Require users to enter their email address,bool",
		"requirevalidemail"=>"Require users to validate their email address,bool",
		"blockdupeemail"=>"One account per email address,bool",
		"spaceinname"=>"Allow spaces in user names,bool",
		"allowoddadminrenames"=>"Allow admins to enter 'illegal' names in the user editor,bool",
		"selfdelete"=>"Allow player to delete their character,bool",

		"Commentary/Chat,title",
		"soap"=>"Clean user posts (filters bad language and splits words over 45 chars long),bool",
		"maxcolors"=>"Max # of color changes usable in one comment,range,5,40,1",
		"postinglimit"=>"Limit posts to let one user post only up to 50% of the last posts (else turn it off),bool",

		"Place names and People names,title",
		"villagename"=>"Name for the main village",
		"innname"=>"Name of the inn",
		"barkeep"=>"Name of the barkeep",
		"barmaid"=>"Name of the barmaid",
		"bard"=>"Name of the bard",
		"clanregistrar"=>"Name of the clan registrar",
		"deathoverlord"=>"Name of the death overlord",

		"Referral Settings,title",
		"refereraward"=>"How many points will be awarded for a referral?,int",
		"referminlevel"=>"What level does the referral need to reach to credit the referer?,int",

		"Random events,title",
		"forestchance"=>"Chance for Something Special in the Forest,range,0,100,1",
		"villagechance"=>"Chance for Something Special in any village,range,0,100,1",
		"innchance"=>"Chance for Something Special in the Inn,range,0,100,1",
		"gravechance"=>"Chance for Something Special in the Graveyard,range,0,100,1",
		"gardenchance"=>"Chance for Something Special in the Gardens,range,0,100,1",

		"Paypal,title",
		"paypalemail"=>"Email address of Admin's paypal account",
		"paypalcurrency"=>"Currency type",
		"paypalcountry-code"=>"What country's predominant language do you wish to have displayed in your PayPal screen?,enum
		,US,United States,DE,Germany,AI,Anguilla,AR,Argentina,AU,Australia,AT,Austria,BE,Belgium,BR,Brazil,CA,Canada
		,CL,Chile,C2,China,CR,Costa Rica,CY,Cyprus,CZ,Czech Republic,DK,Denmark,DO,Dominican Republic
		,EC,Ecuador,EE,Estonia,FI,Finland,FR,France,GR,Greece,HK,Hong Kong,HU,Hungary,IS,Iceland,IN,India
		,IE,Ireland,IL,Israel,IT,Italy,JM,Jamaica,JP,Japan,LV,Latvia,LT,Lithuania,LU,Luxembourg,MY,Malaysia
		,MT,Malta,MX,Mexico,NL,Netherlands,NZ,New Zealand,NO,Norway,PL,Poland,PT,Portugal,SG,Singapore,SK,Slovakia
		,SI,Slovenia,ZA,South Africa,KR,South Korea,ES,Spain,SE,Sweden,CH,Switzerland,TW,Taiwan,TH,Thailand,TR,Turkey
		,GB,United Kingdom,UY,Uruguay,VE,Venezuela",
		"paypaltext"=>"What text should be displayed as item name in the donations screen(player name will be added after it)?",
		"(standard: 'Legend of the Green Dragon Site Donation from',note",

		"General Combat,title",
		"autofight"=>"Allow fighting multiple rounds automatically,bool",
		"autofightfull"=>"Allow fighting until fight is over,enum,0,Never,1,Always,2,Only when not allowed to flee",

		"Training,title",
		"automaster"=>"Masters hunt down truant students,bool",
		"multimaster"=>"Can players gain multiple levels (challenge multiple masters) per game day?,bool",
		"displaymasternews"=>"Display news if somebody fought his master?,bool",

		"Clans,title",
		"allowclans"=>"Enable Clan System?,bool",
		"goldtostartclan"=>"Gold to start a clan,int",
		"gemstostartclan"=>"Gems to start a clan,int",
		"officermoderate"=>"Can clan officers who are also moderators moderate their own clan even if they cannot moderate all clans?,bool",

		"New Days,title",
		"daysperday"=>"Game days per calendar day,range,1,6,1",
		"specialtybonus"=>"Extra daily uses in specialty area,range,0,5,1",
		"newdaycron"=>"Let the newday-runonce run via a cronjob,bool",
		"The directory is necessary! Do not forget to set the correct one in cron.php in your main game folder!!! ONLY experienced admins should use cron jobbing here,note",
		"`bAlso make sure you setup a cronjob on your machine using confixx/plesk/cpanel or any other admin panel pointing to the cron.php file in your main folder`b,note",
		"If you do not know what a Cronjob is... leave it turned off. If you want to know more... check out: <a href='http://wiki.dragonprime.net/index.php?title=Cronjob'>http://wiki.dragonprime.net/index.php?title=Cronjob</a>,note",
		"resurrectionturns"=>"Modify (+ or -) the number of turns deducted after a resurrection as an absolute (number) or relative (number followed by %),text",

		"Forest,title",
		"turns"=>"Forest Fights per day,range,5,30,1",
		"dropmingold"=>"Forest Creatures drop at least 1/4 of max gold,bool",
		"suicide"=>"Allow players to Seek Suicidally?,bool",
		"suicidedk"=>"Minimum DKs before players can Seek Suicidally?,int",
		"forestgemchance"=>"Player will find a gem one in X times,range,10,100,1",
		"disablebonuses"=>"Should monsters which get buffed with extra HP/Att/Def get a gold+exp bonus?,bool",
		"forestexploss"=>"What percentage of experience should be lost?,range,10,100,1",

		"Multiple Enemies,title",
		"multifightdk"=>"Multiple monsters will attack players above which amount of dragonkills?,range,8,50,1",
		"multichance"=>"The chance for an attack from multiple enemies is,range,0,100,1",
		"addexp"=>"Additional experience (%) per enemy during multifights?,range,0,15",
		"instantexp"=>"During multi-fights hand out experience instantly?,bool",
		"maxattacks"=>"How many enemies will attack per round (max. value),range,1,10",
		"allowpackofmonsters"=>"Allow multiple monsters of the same type to appear in a battle?,bool",
		"Random values for type of seeking is added to random base.,note",
		"multibasemin"=>"The base number of multiple enemies at minimum is,range,1,100,2",
		"multibasemax"=>"The base number of multiple enemies at maximum is,range,1,100,3",
		"multislummin"=>"The number of multiple enemies at minimum for slumming is,range,0,100,0",
		"multislummax"=>"The number of multiple enemies at maximum for slumming is,range,0,100,1",
		"multithrillmin"=>"The number of multiple enemies at minimum for thrill seeking is,range,0,100,1",
		"multithrillmax"=>"The number of multiple enemies at maximum for thrill seeking is,range,0,100,2",
		"multisuimin"=>"The number of multiple enemies at minimum for suicide is,range,0,100,2",
		"multisuimax"=>"The number of multiple enemies at maximum for suicide is,range,0,100,4",

		"Stables,title",
		"allowfeed"=>"Does Merick have feed onhand for creatures,bool",

		"Companions/Mercenaries,title",
		"enablecompanions"=>"Enable the usage of companions,bool",
		"companionsallowed"=>"How many companions are allowed per player,int",
		"Modules my alter this value on a per player basis!,note",
		"companionslevelup"=>"Are companions allowed to level up?,bool",

		"Bank Settings,title",
		"fightsforinterest"=>"Max forest fights remaining to earn interest?,range,0,10,1",
		"maxinterest"=>"Max Interest Rate (%),range,5,10,1",
		"mininterest"=>"Min Interest Rate (%),range,0,5,1",
		"maxgoldforinterest"=>"Over what amount of gold does the bank cease paying interest? (0 for unlimited),int",
		"borrowperlevel"=>"Max player can borrow per level (val * level for max),range5,200,5",
		"allowgoldtransfer"=>"Allow players to transfer gold,bool",
		"transferperlevel"=>"Max player can receive from a transfer (val * level),range,5,100,5",
		"mintransferlev"=>"Min level a player (0 DK's) needs to transfer gold,range,1,5,1",
		"transferreceive"=>"Total transfers a player can receive in one day,range,0,5,1",
		"maxtransferout"=>"Amount player can transfer to others (val * level),range,5,100,5",
		"innfee"=>"Fee for express inn payment (x or x%),int",

		"Mail Settings,title",
		"mailsizelimit"=>"Message size limit per message,int",
		"inboxlimit"=>"Limit # of messages in inbox,int",
		"oldmail"=>"Automatically delete old messages after (days),int",
		"superuseryommessage"=>"Warning to give when attempting to YoM an admin?",
		"onlyunreadmails"=>"Only unread mail count towards the inbox limit?,bool",

		"PvP,title",
		"pvp"=>"Enable Slay Other Players,bool",
		"pvpday"=>"Player Fights per day,range,1,10,1",
		"pvpimmunity"=>"Days that new players are safe from PvP,range,1,5,1",
		"pvpminexp"=>"Experience below which player is safe from PvP,int",
		"pvpattgain"=>"Percent of victim experience attacker gains on win,floatrange,.25,20,.25",
		"pvpattlose"=>"Percent of experience attacker loses on loss,floatrange,.25,20,.25",
		"pvpdefgain"=>"Percent of attacker experience defender gains on win,floatrange,.25,20,.25",
		"pvpdeflose"=>"Percent of experience defender loses on loss,floatrange,.25,20,.25",

		"Content Expiration,title",
		"expirecontent"=>"Days to keep comments and news?  (0 = infinite),int",
		"expiretrashacct"=>"Days to keep never logged-in accounts? (0 = infinite),int",
		"expirenewacct"=>"Days to keep 1 level (0 dragon) accounts? (0 =infinite),int",
		"expireoldacct"=>"Days to keep all other accounts? (0 = infinite),int",
		"LOGINTIMEOUT"=>"Seconds of inactivity before auto-logoff,int",

		"High Load Optimization,title",
		"This has been moved to the dbconnect.php,note",
		/*
		"usedatacache"=>"Use Data Caching,bool",
		"datacachepath"=>"Path to store data cache information`n`iNote`i when using in an environment where Safe Mode is enabled; this needs to be a path that has the same UID as the web server runs.",
		//this has been put to the dbconnect.php
		*/

		"LoGDnet Setup,title",
		"(LoGDnet requires your PHP configuration to have file wrappers enabled!!),note",
		"logdnet"=>"Register with LoGDnet?,bool",
		"serverurl"=>"Server URL",
		"serverdesc"=>"Server Description (75 chars max)",
		"logdnetserver"=>"Master LoGDnet Server (default http://logdnet.logd.com/)",
		"curltimeout"=>"How long we wait for responses from logdnet.logd.com (in seconds),range,1,10,1|2",

		"Game day Setup,title",
		"dayduration"=>"Day Duration,viewonly",
		"curgametime"=>"Current game time,viewonly",
		"curservertime"=>"Current Server Time,viewonly",
		"lastnewday"=>"Last new day,viewonly",
		"nextnewday"=>"Next new day,viewonly",
		"gameoffsetseconds"=>"Real time to offset new day,$enum",

		"Translation Setup,title",
		"enabletranslation"=>"Enable the use of the translation engine,bool",
		"It is strongly recommended to leave this feature turned on.,note",
		"cachetranslations"=>"Cache the translations (datacache must be turned on)?,bool",
		"permacollect"=>"Permanently collect untranslated texts (overrides the next settings!),bool",
		"collecttexts"=>"Are we currently collecting untranslated texts?,viewonly",
		"tl_maxallowed"=>"Collect untranslated texts if you have fewer player than this logged in. (0 never collects),int",
		"charset"=>"Which charset should be used for htmlentities?",

		"Error Notification,title",
		"Note: you MUST have data caching turned on if you want to use this feature.  Also the first error within any 24 hour period will not generate a notice; I'm sorry: that's really just how it is for technical reasons.,note",
		"show_notices"=>"Show PHP Notice output?,bool",
		"notify_on_warn"=>"Send notification on site warnings?,bool",
		"notify_on_error"=>"Send notification on site errors?,bool",
		"notify_address"=>"Address to notify",
		"notify_every"=>"Only notify every how many minutes for each distinct error?,int",

		"Miscellaneous Settings,title",
		"allowspecialswitch"=>"The Barkeeper may help you to switch your specialty?,bool",
		"maxlistsize"=>"Maximum number of items to be shown in the warrior list,int",
	);
	$secstonewday = secondstonextgameday($details);
	$useful_vals = array(
		"dayduration"=>round(($details['dayduration']/60/60),0)." hours",
		"curgametime"=>getgametime(),
		"curservertime"=>date("Y-m-d h:i:s a"),
		"lastnewday"=>date("h:i:s a",
			strtotime("-{$details['realsecssofartoday']} seconds")),
		"nextnewday"=>date("h:i:s a",
			strtotime("+{$details['realsecstotomorrow']} seconds"))." (".date("H\\h i\\m s\\s",$secstonewday).")"
	);

	loadsettings();
	$vals = $settings + $useful_vals;

	rawoutput("<form action='configuration.php?op=save' method='POST'>");
	addnav("","configuration.php?op=save");
	showform($setup,$vals);
	rawoutput("</form>");
}
page_footer();
?>
