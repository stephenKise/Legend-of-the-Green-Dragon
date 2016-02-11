<?php
// addnews ready
// mail ready
// translator ready

function suipcontrol_getmoduleinfo(){
	$block3IP = substr($_SERVER['REMOTE_ADDR'],0,strrpos($_SERVER['REMOTE_ADDR'],"."));
	$info = array(
		"name"=>"Superuser IP Access Control",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Administrative",
		"download"=>"core_module",
		"prefs"=>array(
			"SuperUser IP Control User Preferences,title",
			array("IP from which you might sign on.  Enter partial IP's for a subnet and separate ranges with commas; semicolons; or spaces. `nFor example; if you wanted to allow aaa.bbb.ccc.1 through aaa.bbb.ccc.255; enter aaa.bbb.ccc. and the entire subnet will match.`n`n You're on %s now; recommend using %s if this is you.`n `bCaution`b: entering bad data here might block you out of most superuser activities!,note", $_SERVER['REMOTE_ADDR'], $block3IP), //default to XXX.XXX.XXX. for the current user's IP address.
			"ips"=>"IPs|$block3IP",
			"tempauthorize"=>"Temporary authorization code,viewonly|",
			"tempip"=>"Temporarily authorized IP|",
			"authemail"=>"Temp authorization email address,|",
		),
	);
	return $info;
}

function suipcontrol_install(){
	module_addhook("check_su_access");
	module_addhook("prefs-save");
	return true;
}

function suipcontrol_uninstall(){
	return true;
}

function suipcontrol_dohook($hookname,$args){
	switch($hookname){
	case "check_su_access":
		$ips = split("[, \t;]",trim(get_module_pref("ips")));
		$args['enabled']=false;
		while (list($key,$val)=each($ips)){
			if (substr($_SERVER['REMOTE_ADDR'],0,strlen($val))==$val){
				$args['enabled']=true;
				// We no longer need the temp IP as they match an IP
				clear_module_pref("tempip");
				break;
			}
		}
		if ($_SERVER['REMOTE_ADDR']==get_module_pref("tempip")) $args['enabled']=true;
		if (!$args['enabled']) {
			suipcontrol_form();
		}else{
			global $session;
			if (get_module_pref("authemail")!=$session['user']['emailaddress']){
				output("`\$Warning:`4 The email address for your account (`@%s`4) does not match your temporary authorization email address (`@%s`4).", $session['user']['emailaddress'], get_module_pref("authemail"));
				output("Either edit your SU IP Control module preferences to fix this or ask an admin if you don't have those permissions yourself.`n`n");
			}
		}
		break;
	}
	return $args;
}

function suipcontrol_run(){
	switch(httpget("op")){
	case "checkcode":
		$code = get_module_pref("tempauthorize");
		if (httppost("code")==$code && $code!=""){
			set_module_pref("tempip",$_SERVER['REMOTE_ADDR']);
			set_module_pref("tempauthorize","");
			output("Successfully identified");
			redirect(httpget("return"));
		}else{
			output("I don't recognize that code.");
		}
		break;
	case "send":
		$code = get_module_pref("tempauthorize");
		if ($code==""){
			$text = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			for ($x=0; $x<32; $x++){
				$code.=substr($text,e_rand(0,strlen($text)),1);
			}
			set_module_pref("tempauthorize",$code);
		}
		global $session;
		if (get_module_pref("authemail")=="") set_module_pref("authemail",$session['user']['emailaddress']);
		$str = sprintf_translate("Here is your LoGD temporary authorization code: \n\n\t%s\n\nThis code will only work one time, you'll have to request a new one next time.", $code);
		mail(get_module_pref("authemail"),translate_inline("LoGD Temp Authorization Code"), $str);
		output("`^`bAn authorization code has been emailed to you (%s).`b`n`0",get_module_pref("authemail"));
		break;
	}
	page_header("SU IP Access Control");
	suipcontrol_form();
	addnav("M?Return to the Mundane","village.php");
	page_footer();
}

function suipcontrol_form(){
	global $REQUEST_URI;
	if (httpget("return")!="") $return = httpget("return");
	else $return = $REQUEST_URI;
	$return = rawurlencode($return);
	output("`nThis IP address was not found in your list of allowed administrative IP address ranges.");
	output("If this IP address should be added, you will need to do so after logging on from an already authorized ip address.");
	output("Just for your records, you're signing on from %s.`n", $_SERVER['REMOTE_ADDR']);
	rawoutput("<form action='runmodule.php?module=suipcontrol&op=checkcode&return=$return' method='POST'>");
	output("`n`nIf you'd like to email a temporary authorization code to yourself, use the link in the nav area, then enter it here:`n");
	rawoutput("<input name='code' maxlength='32'><br>");
	$check = translate_inline("Check");
	rawoutput("<input type='submit' class='button' value='$check'>");
	rawoutput("</form>");
	addnav("","runmodule.php?module=suipcontrol&op=checkcode&return=$return");
	addnav("Email temp authorization code","runmodule.php?module=suipcontrol&op=send&return=$return");

}
?>
