<?php
// translator ready
// addnews ready
// mail ready
$thispage_superuser_level=0;
function check_su_access($level){
	global $session,$thispage_superuser_level;
	$thispage_superuser_level = $thispage_superuser_level | $level;
	rawoutput("<!--Su_Restricted-->");
	if ($session['user']['superuser'] & $level) {
		//they have appropriate levels, let's see if there's a module that
		// restricts access beyond this point.
		$return = modulehook("check_su_access",
				array("enabled"=>true,"level"=>$level));
		if ($return['enabled']){
			$session['user']['laston'] = date("Y-m-d H:i:s");
		}else{
			page_header("Oops.");
			output("Looks like you're probably an admin with appropriate permissions to perform this action, but a module is preventing you from doing so.");
			output("Sorry about that!");
			tlschema("nav");
			addnav("M?Return to the Mundane","village.php");
			tlschema();
			page_footer();
		}
	}else{
		clearnav();
		$session['output']="";
		page_header("INFIDEL!");
		// This buff is useless because the graveyard (rightly, really)
		// wipes all buffs when you enter it.  This means that you never really
		// have this effect unless you log out without going to the graveyard
		// for some odd reason.
//		apply_buff('angrygods',
//			array(
//				"name"=>"`^The gods are angry!",
//				"rounds"=>10,
//				"wearoff"=>"`^The gods have grown bored with teasing you.",
//				"minioncount"=>$session['user']['level'],
//				"maxgoodguydamage"=> 2,
//				"effectmsg"=>"`7The gods curse you, causing `\${damage}`7 damage!",
//				"effectnodmgmsg"=>"`7The gods have elected not to tease you just now.",
//				"allowinpvp"=>1,
//				"survivenewday"=>1,
//				"newdaymessage"=>"`6The gods are still angry with you!",
//				"schema"=>"superuser",
//				)
//		);
		output("For attempting to defile the gods, you have been smitten down!`n`n");
		output("%s`\$, Overlord of Death`) appears before you in a vision, seizing your mind with his, and wordlessly telling you that he finds no favor with you.`n`n",getsetting('deathoverlord','`$Ramius'));
		addnews("`&%s was smitten down for attempting to defile the gods (they tried to hack superuser pages).",$session['user']['name']);
		debuglog("Lost {$session['user']['gold']} and ".($session['user']['experience']*0.25)." experience trying to hack superuser pages.");
		$session['user']['hitpoints']=0;
		$session['user']['alive']=0;
		$session['user']['soulpoints']=0;
		$session['user']['gravefights']=0;
		$session['user']['deathpower']=0;
		$session['user']['gold']=0;
		$session['user']['experience']*=0.75;
		addnav("Daily News","news.php");
		$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE (superuser&".SU_EDIT_USERS.")";
		$result = db_query($sql);
		require_once("lib/systemmail.php");
		while ($row = db_fetch_assoc($result)) {
			$subj = "`#%s`# tried to hack the superuser pages!";
			$subj = sprintf($subj, $session['user']['name']);
			$body = "Bad, bad, bad %s, they are a hacker!`n`nTried to access %s from %s.";
			$body = sprintf($body, $session['user']['name'], $_SERVER['REQUEST_URI'], $SERVER['HTTP_REFERER']);
			systemmail($row['acctid'],$subj,$body);
		}
		page_footer();
	}
}
?>
