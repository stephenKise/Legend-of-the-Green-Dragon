<?php

/*
A non-core module used on Classic Server. Shows how to:
1) add to your Frequently Asked Questions
2) use a non-core module with Wen
3) add to the points description in the Hunter's Lodge
Please don't take this module as an example of how to add extra Lodge benefits.
There are extra steps to take when doing that. You're better off looking at core
modules to add Lodge benefits. The pointsdesc part in this case only
adds information to the Lodge, not actual benefits.
*/

function faq_central_server_getmoduleinfo(){
	$info = array(
		"name"=>"FAQ for Classic Server",
		"version"=>"2.0",
		"author"=>"Catscradler",
		"category"=>"General",
		"download"=>"core_module",
		"allowanonymous"=>true,
		"override_forced_nav"=>true,
	);
	return $info;
}

function faq_central_server_install(){
	module_addhook("faq-toc");			//show in the FAQ
	module_addhook_priority("everyhit-loggedin",24);	//show in Wen
//	module_addhook_priority("pointsdesc",99);	//Hunter's Lodge description
	return true;
}

function faq_central_server_uninstall(){
	return true;
}

function faq_central_server_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "everyhit-loggedin":
		//Allow this module to run in Wen, which blocks all modules by
		// default by also hooking into everyhit.
		if($session['user']['location'] ==
				get_module_setting("villagename", "newbieisland")){
			unblockmodule("faq_central_server");
		}
		break;
	case "faq-toc":
		$t = translate_inline("`@Customs on this Server`0");
		output_notl("&#149;<a href='runmodule.php?module=faq_central_server&op=faq'>$t</a><br/>", true);
		break;
	case "pointsdesc":
		output("`n`n");
		output("`Q`c-=-=-=-=-=-=-=-`c");
		output("`7You can gain points by finding bugs in the game.");
		output("Anything from misspellings to a new player gaining administrative powers counts.");
		output("If it doesn't work right, report it using the `QPetition for Help `7link.");
		output("Please be as descriptive as possible when telling us of the problem (copy and paste is your friend).`n");
		output("The more severe the problem is, the more points you'll get.");
		output("Only the first report of each problem will earn points.`n");
		output("Please note: if you just don't like the way something works or it doesn't seem \"fair\", it does not count as a bug, merely an opinion.");
		output("You will most likely not receive points for such reports.");
		output("`Q`c-=-=-=-=-=-=-=-`c");
		break;
	}
	return $args;
}

function faq_central_server_run(){
	//Allow those who aren't logged in to navigate to the extra FAQ page
	//allowanonymous and override_forced_nav must also be set in the module's info
	global $session;
	$op = httpget("op");
	if ($op != "faq") {
		require_once("lib/forcednavigation.php");
		do_forced_nav(false, false);
	} else {
		faq_central_server_faq();
	}
}

function faq_central_server_faq() {
	tlschema("faq");
	popup_header("Customs on this Server");
	$c = translate_inline("Return to Contents");
	rawoutput("<a href='petition.php?op=faq'>$c</a><hr>");
	output("`n`n`c`bRules on this Server`b`c`n");
	output("`^Welcome to the Classic Server located at `&http://lotgd.net`n`n");
	output("`@While you're here there's a few customs that we (the Staff) hope players will be aware of and abide by.");
	output("These customs are in place to keep the playing experience enjoyable for most (not all, unfortunately, since we can't please everybody) of the people who come across our little realm on the Internet.");
	output("Follow them and we can all have a good time.`n`n");
	output("Without further ado, here they are:`n");
	output("`^1. `#Don't be a jerk.`n");
	output("`^2. `#No circumventing the language filter.`n");
	output("`^3. `#Don't give away game secrets.");
	output("This is a game of exploration, so don't spoil it for everyone.");
	output("If it's covered in the FAQ, it's free knowledge of course, as well as a few obvious things that were added since the FAQ was written.`n");
	output("`^4. `#People of all ages play here, so keep that in mind.`n");
	output("`^5. `#Since you've read the rest of the FAQ up to this point there's no need to repeat the part about NO CHATSPEAK, is there?`n");
	output("`^6. `#Play along with the story at the top of the page (eg. only role-playing in the Gardens, only beta stuff in the Beta Pavilion, the others are mostly general.)`n");
	output("`^7. `#You may have more than one character, but they shouldn't interact.");
	output("Don't attack each other, talk to each other, place bounties on each other, or refer your own alts.");
	output("That sort of cheating is just in bad taste.");
	output("If you share a computer with another player we will assume there is only one person at the keyboard.`n");
	output("`^8. `#Listen to the admins and other staff.");
	output("If you don't, you are risking your access to the game.`n");
	output("`@That's about it for the summary.");
	output("All of the staff can delete comments, so if one of your posts disappears consider it a warning.`n");
	output("The answers to most questions not covered in this F.A.Q. can be found by clicking on <a href='http://lotgd.net/forum/' target='_blank'>this link.</a>`n`n", true);
	output("`^Once again, welcome, and good luck in your quest!`n`n");

	rawoutput("<hr><a href='petition.php?op=faq'>$c</a>");
	popup_footer();
}
?>
