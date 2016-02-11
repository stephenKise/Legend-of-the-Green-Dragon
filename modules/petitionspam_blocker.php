<?php
// addnews ready
// mail ready
// translator ready
function petitionspam_blocker_getmoduleinfo(){
	$message = "It looks like you are a spam bot.  Sorry, we don't like that sort around here.  If you're not actually a spam bot, please re-submit your petition using a different web browser (since your browser didn't submit this form correctly).";
	$info = array(
		"name"=>"Spam blocker - petition.php",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Administrative",
		"download"=>"core_module",
		"settings"=>array(
			'hidden_reqname'=>"Name of hidden field which must be populated,|".md5(getmicrotime(true).e_rand()),
			'hidden_reqvalue'=>"Value of hidden field which must be populated,|".md5(getmicrotime(true).e_rand()),
			'hidden_noname'=>"Name of hidden field which must NOT be populated,|".md5(getmicrotime(true).e_rand()),
			'hidden_novalue'=>"Value of hidden field which must NOT be populated,|".md5(getmicrotime(true).e_rand()),
			'spambottext'=>'Text to be displayed for potential spam bots:,textarea|'.$message,
		)
	);
	return $info;
}

function petitionspam_blocker_install(){
	module_addhook("addpetition");
	module_addhook("petitionform");

	return true;
}

function petitionspam_blocker_uninstall(){
	return true;
}

function petitionspam_blocker_dohook($hookname,$args){
	switch($hookname){
	case "petitionform":
		$input1 = "<input name='".get_module_setting("hidden_reqname")."' value='".get_module_setting("hidden_reqvalue")."'>";
		//disabled fields are not submitted with the form
		$input2 = "<input name='".get_module_setting("hidden_noname")."' value='".get_module_setting("hidden_novalue")."' disabled='true'>";
		if (e_rand(0,1)){
			$input3 = $input2;
			$input2 = $input1;
			$input1 = $input3; // which acctually means: $input1 = (old)$input2 ???
		}
		rawoutput("<p style='display: none;'><b>Please note, do not change these fields</b>, they are an anti-spam measure.  " .
				"Most users will not see them; if you are seeing them though, then you're using a browser that doesn't recognize " .
				"the attributes we use to hide them.  That's ok, just don't change their values!<br>" .
				$input1 .
				$input2 .
				"</p>");
		break;
	case "addpetition":
		$reqname = get_module_setting("hidden_reqname");
		$reqvalue = get_module_setting("hidden_reqvalue");
		$noname = get_module_setting("hidden_noname");
		if (!array_key_exists($reqname,$args) || array_key_exists($noname,$args) || $args[$reqname] != $reqvalue){
			$args['cancelpetition'] = true;
			$args['cancelreason'] = get_module_setting("spambottext");
		}
		break;
	}
	return $args;
}
?>