<?php
// addnews ready
// mail ready
// translator ready
function serversuspend_getmoduleinfo(){
	$info = array(
		"name"=>"Server Maintenance Suspension",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Administrative",
		"download"=>"core_module",
	);
	return $info;
}

function serversuspend_install(){
	module_addhook("everyhit");
	return true;
}

function serversuspend_uninstall(){
	return true;
}

function serversuspend_dohook($hookname,$args){
	switch($hookname){
	case "everyhit":
		$permithits = array(
			"home.php"=>true,
			"index.php"=>true,
			"login.php"=>true,
			"installer.php"=>true,
		);
		global $session;
		$script = substr($_SERVER['SCRIPT_NAME'],strrpos($_SERVER['SCRIPT_NAME'],"/")+1);
		if (isset($permithits[$script])
			|| $session['user']['superuser']&SU_MANAGE_MODULES
			|| $session['user']['superuser']&SU_MEGAUSER
			){
			output("`c`b<font size='+1'>`\$The server is currently suspended for maintenance, only superusers will be able to log in and perform any actions.`0</font>`b`c",true);
			//users get sent to the village or shades depending on their alive
			//status if they try to navigate.
			//This is actually a bug, but I haven't bothered to track it down,
			//and it seems somewhat reasonable given we warn them that it is
			//coming with a MOTD.
			if ($session['user']['loggedin']) {
				output("`\$This means YOU.");
				output("Get your upgrades done and deactivate the server suspension module you fool!`n`n");
				output("Users who attempt to navigate during the outtage will be returned to the village or shades, depending on their alive status.`n`n");
			}
		}else{
			//popup header and footer so we don't write the page output to the user's session, and their first badnav after we take out the module will be valid.
			popup_header("Down for Maintenance");
			output("`c`b<font size='+1'>The server is currently suspended for maintenance</font>`b`c",true);
			output("We apologise for any inconvenience, but for the moment, this server is undergoing maintenance.");
			output("It should be accessible again soon.`n`n",true);
			$link = $_SERVER['REQUEST_URI'];
			output("<a href='%s'>Click here to check on the server status</a>, or press Refresh in your browser.`n",$link,true);
			output("Since we're working on maintenance, maintenance will go quicker if you don't refresh or click that link as quick as you can, as this'll use fewer system resources.");
			output("So check once a minute or so, but don't kill us with checking every 2 seconds please.`n");
			addnav("Continue","$link");
			popup_footer();
		}
		break;
	}
	return $args;
}
?>
