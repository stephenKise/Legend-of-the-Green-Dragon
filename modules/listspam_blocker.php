<?php
// addnews ready
// mail ready
// translator ready
function listspam_blocker_getmoduleinfo(){
	$info = array(
		"name"=>"Spam blocker - list.php",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Administrative",
		"download"=>"core_module",
	);
	return $info;
}

function listspam_blocker_install(){
	module_addhook("header-list");
	return true;
}

function listspam_blocker_uninstall(){
	return true;
}

function listspam_blocker_dohook($hookname,$args){
	switch($hookname){
	case "header-list":
		$lists = get_module_setting("lists");
		if ($lists > '') {
			$lists = unserialize($lists);
		}
		if (!is_array($lists)) $lists = array();
		$classC = explode(".",$_SERVER['REMOTE_ADDR']);
		$classC = $classC[0].'.'.$classC[1].'.'.$classC[2];

		if (!array_key_exists($classC,$lists)){
			$lists[$classC] = array(
				'addtime'=>time(),
				'accesses'=>0,
				);
		}
		$parts = explode("-",$_GET['c']);
		if ($parts[0] == '1'){
			//they're probably not carrying sessions, each lists hit counts *5
			$lists[$classC]['accesses'] += 5;
		}else{
			++$lists[$classC]['accesses'];
		}
		if (e_rand(1,50) == 1){
			//Garbage Collection
			$expiretime = date("-1 day");
			foreach($lists as $subnet=>$info){
				if ($info['addtime'] < $expiretime){
					unset($lists[$subnet]);
				}
			}
		}
		set_module_setting("lists",serialize($lists));
		if ($lists[$classC]['accesses'] > 20){
			global $session;
			if (!$session['user']['loggedin']){
				global $template,$header;
				//rebuild the header
				$header = $template['header'];
				$header=str_replace("{title}","Legend of the Green Dragon",$header);
				$header.=tlbutton_pop();

				output("`\$`bToo many accesses:`b`# Your subnet has accessed this page too many times in the last 24 hours.");
				output("Sorry, but this page requires a huge amount of resources to generate, and we can't afford the resources to have people just crawling all over this page.");
				output("Try again tomorrow.");
				addnav("Return to Login","home.php");
				page_footer();
				exit();
			}
		}
		debug($lists);
		break;
	}
	return $args;
}
?>