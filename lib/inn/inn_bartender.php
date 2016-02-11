<?php
$act = httpget('act');
if ($act==""){
	output("%s`0 looks at you sort-of sideways like.",$barkeep);
	output("He never was the sort who would trust a man any farther than he could throw them, which gave dwarves a decided advantage, except in provinces where dwarf tossing was made illegal.");
	output("%s`0 polishes a glass, holds it up to the light of the door as another patron opens it to stagger out into the street.",$barkeep);
	output("He then makes a face, spits on the glass and goes back to polishing it.");
	output("\"`%What d'ya want?`0\" he asks gruffly.");
	addnav_notl(sanitize($barkeep));
	addnav("Bribe","inn.php?op=bartender&act=bribe");
	addnav("Drinks");
	modulehook("ale", array());
}elseif ($act=="bribe"){
	$g1 = $session['user']['level']*10;
	$g2 = $session['user']['level']*50;
	$g3 = $session['user']['level']*100;
	$type = httpget('type');
	if ($type==""){
		output("While you know that you won't always get what you want, sometimes the way to a man's information is through your purse.");
		output("It's also always been said that more is better.`n`n");
		output("How much would you like to offer him?");
		addnav("1 gem","inn.php?op=bartender&act=bribe&type=gem&amt=1");
		addnav("2 gems","inn.php?op=bartender&act=bribe&type=gem&amt=2");
		addnav("3 gems","inn.php?op=bartender&act=bribe&type=gem&amt=3");
		addnav(array("%s gold", $g1),"inn.php?op=bartender&act=bribe&type=gold&amt=$g1");
		addnav(array("%s gold", $g2),"inn.php?op=bartender&act=bribe&type=gold&amt=$g2");
		addnav(array("%s gold", $g3),"inn.php?op=bartender&act=bribe&type=gold&amt=$g3");
	}else{
		$amt = httpget('amt');
		if ($type=="gem"){
			if ($session['user']['gems']<$amt){
				$try=false;
				output("You don't have %s gems!", $amt);
			}else{
				$chance = $amt*30;
				$session['user']['gems']-=$amt;
				debuglog("spent $amt gems on bribing $barkeep");
				$try=true;
			}
		}else{
			if ($session['user']['gold']<$amt){
				output("You don't have %s gold!", $amt);
				$try=false;
			}else{
				$try=true;
				$sfactor = 50/90;
				$fact = $amt/$session['user']['level'];
				$chance = ($fact - 10)*$sfactor + 25;
					$session['user']['gold']-=$amt;
				debuglog("spent $amt gold bribing $barkeep");
			}
		}
		if ($try){
			if (e_rand(0,100)<$chance){
				output("%s`0 leans over the counter toward you.  \"`%What can I do for you, kid?`0\" he asks.",$barkeep);
				addnav("What do you want?");
				if (getsetting("pvp",1)) {
					addnav("Who's upstairs?","inn.php?op=bartender&act=listupstairs");
				}
				addnav("Tell me about colors","inn.php?op=bartender&act=colors");
				if (getsetting("allowspecialswitch", true))
					addnav("Switch specialty","inn.php?op=bartender&act=specialty");
			}else{
				output("%s`0 begins to wipe down the counter top, an act that really needed doing a long time ago.",$barkeep);
				if ($type == "gem") {
					if ($amt == 1) {
						output("When he's finished, your gem is gone.");
					} else{
						output("When he's finished, your gems are gone.");
					}
				} else {
					output("When he's finished, your gold is gone.");
				}
				output("You inquire about the loss, and he stares blankly back at you.");
				addnav(array("B?Talk to %s`0 again",$barkeep),"inn.php?op=bartender");
			}
		}else{
			output("`n`n%s`0 stands there staring at you blankly.",$barkeep);
			addnav(array("B?Talk to %s`0 the Barkeep",$barkeep),"inn.php?op=bartender");
		}
	}
}else if ($act=="listupstairs"){
	addnav("Refresh the list","inn.php?op=bartender&act=listupstairs");
	output("%s`0 lays out a set of keys on the counter top, and tells you which key opens whose room.  The choice is yours, you may sneak in and attack any one of them.",$barkeep);
	pvplist($iname,"pvp.php", "?act=attack&inn=1");
}else if($act=="colors"){
	output("%s`0 leans on the bar.  \"`%So you want to know about colors, do you?`0\" he asks.",$barkeep);
	output("You are about to answer when you realize the question was posed in the rhetoric.");
	output("%s`0 continues, \"`%To do colors, here's what you need to do.",$barkeep);
	output(" First, you use a &#0096; mark (found right above the tab key) followed by 1, 2, 3, 4, 5, 6, 7, !, @, #, $, %, ^, &.", true);
	output("Each of those corresponds with a color to look like this:");
	output_notl("`n`1&#0096;1 `2&#0096;2 `3&#0096;3 `4&#0096;4 `5&#0096;5 `6&#0096;6 `7&#0096;7 ",true);
	output_notl("`n`!&#0096;! `@&#0096;@ `#&#0096;# `\$&#0096;\$ `%&#0096;% `^&#0096;^ `&&#0096;& `n",true);
	output("`% Got it?`0\"  You can practice below:");
	rawoutput("<form action=\"$REQUEST_URI\" method='POST'>",true);
	$testtext = httppost('testtext');
	output("You entered %s`n", prevent_colors(HTMLEntities($testtext, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))), true);
	output("It looks like %s`n", $testtext);
	$try = translate_inline("Try");
	rawoutput("<input name='testtext' id='input'>");
	rawoutput("<input type='submit' class='button' value='$try'>");
	rawoutput("</form>");
	rawoutput("<script language='javascript'>document.getElementById('input').focus();</script>");
		output("`0`n`nThese colors can be used in your name, and in any conversations you have.");
	addnav("",$REQUEST_URI);
}else if($act=="specialty"){
	$specialty = httpget('specialty');
	if ($specialty==""){
		output("\"`2I want to change my specialty,`0\" you announce to %s`0.`n`n",$barkeep);
		output("With out a word, %s`0 grabs you by the shirt, pulls you over the counter, and behind the barrels behind him.",$barkeep);
		output("There, he rotates the tap on a small keg labeled \"Fine Swill XXX\"`n`n");
		output("You look around for the secret door that you know must be opening nearby when %s`0 rotates the tap back, and lifts up a freshly filled foamy mug of what is apparently his fine swill, blue-green tint and all.`n`n",$barkeep);
		output("\"`3What?  Were you expecting a secret room?`0\" he asks.  \"`3Now then, you must be more careful about how loudly you say that you want to change your specialty, not everyone looks favorably on that sort of thing.`n`n");
		output("`0\"`3What new specialty did you have in mind?`0\"");
		$specialities = modulehook("specialtynames");
		foreach($specialities as $key=>$name) {
			addnav($name,cmd_sanitize($REQUEST_URI)."&specialty=$key");
		}
	}else{
		output("\"`3Ok then,`0\" %s`0 says, \"`3You're all set.`0\"`n`n\"`2That's it?`0\" you ask him.`n`n",$barkeep);
		output("\"`3Yep.  What'd you expect, some sort of fancy arcane ritual???`0\"  %s`0 begins laughing loudly.",$barkeep);
		output("\"`3You're all right, kid... just don't ever play poker, eh?`0`n`n");
		output("\"`3Oh, one more thing.  Your old use points and skill level still apply to that skill, you'll have to build up some points in this one to be very good at it.`0\"");
		$session['user']['specialty']=$specialty;
	}
}
?>