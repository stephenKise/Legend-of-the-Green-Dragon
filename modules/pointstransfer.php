<?php
/*
Points Tranfser
File:   pointstransfer.php
Author: Red Yates aka Deimos
Date:   03/12/2005

Idea from Macarn of dragoncat and JT.
A module to allow players to transfer their donation points to other players.
Allows for an anonymous transfer, and notification via YOM with an optional
note.

Version 1.0 - initial version by Red Yates
Version 1.1 - Small modifications by Sixf00t4 to work with modifications
              to titlechange that actually charge points.
*/

function pointstransfer_getmoduleinfo(){
	$info = array(
		"name"=>"Points Transfer",
		"version"=>"1.1",
		"author"=>"`\$Red Yates",
		"category"=>"Lodge",
		"download"=>"core_module",
		"settings"=>array(
			"Points Transfer Settings,title",
			"mint"=>"Minimum transfer,int|25",
		),
		"prefs"=>array(
			"Points Transfer Form Holders, title",
			"amount"=>"Amount sending,int|",
			"target"=>"Recipient of points|",
			"anon"=>"Send these points anonymously,bool|",
			"note"=>"Note to send|",
		),
	);
	return $info;
}

function pointstransfer_install(){
	module_addhook("lodge");
	return true;
}

function pointstransfer_uninstall(){
	return true;
}

function pointstransfer_dohook($hookname, $args){
	global $session;
	switch ($hookname){
	case "lodge":
		addnav("Transfer Points");
		addnav("Transfer Points","runmodule.php?module=pointstransfer");
		set_module_pref("amount","");
		set_module_pref("target","");
		set_module_pref("anon",0);
		set_module_pref("note","");
	break;
	}
	return $args;
}

function pointstransfer_pointscheck(){
	global $session;
	$tpoints=0;
	if (is_module_active("titlechange") && !get_module_setting("take", "titlechange")){ // modification by sixf00t4
		$titles=get_module_pref("timespurchased","titlechange");
		if ($titles) $tpoints=get_module_setting("initialpoints","titlechange")+($titles-1)*get_module_setting("extrapoints","titlechange");
	}
	return min($session['user']['donation']-$session['user']['donationspent'],$session['user']['donation']-$tpoints); //Thanks Booger.
}

function pointstransfer_run(){
	global $session;
	require_once("lib/systemmail.php");
	page_header("Hunter's Lodge");
	$op = httpget("op");
	$mint=get_module_setting("mint");
	addnav("L?Return to the Lodge","lodge.php");
	if ($op==""){
		$allowed=pointstransfer_pointscheck();
		if($allowed<$mint){
			output("`7.J. C. Petersen smiles at your generosity, but leaves the forms where they are.");
			$sallowed=($allowed>0?"`@":"`\$").$allowed;
			if (is_module_active("titlechange")){
				output("`n`n\"`&I'm sorry, but counting any points used towards title changes, you have %s`& points available, which isn't enough for a transfer.",$sallowed);
			}else{
				output("`n`n\"`&I'm sorry, but you have %s`& points available, which isn't enough for a transfer.",$sallowed);
			}
			if ($mint){
				output("You need at least `@%s`& points available.`7\"",$mint);
			}else{
				output_notl("`7\"");
			}
		}else{
			output("`7J. C. Petersen smiles at your generosity, and pulls out a form.");
			if (is_module_active("titlechange")){
				output("`n`n\"`&Including any points used towards title changes, you have `@%s`& points available.",$allowed);
			}else{
				output("`n`n\"`&You have `@%s`& points available.",$allowed);
			}
			if ($mint) output("You have the `@%s`& points needed for a minimum transfer.",$mint);
			output("How many points would you like to transfer, and to whom?`7\"");
			$amount=get_module_pref("amount");
			$target=get_module_pref("target");
			$anon=get_module_pref("anon");
			$note=get_module_pref("note");
			$target=color_sanitize($target);
			rawoutput("<form action='runmodule.php?module=pointstransfer&op=confirm' method='POST'>");
			addnav("","runmodule.php?module=pointstransfer&op=confirm");
			output("`n`nPoints: ");
			rawoutput("<input name='amount' width='8' value=$amount>");
			output("`n`nRecipient: ");
			rawoutput("<input name='target' value=$target>");
			output("`n`nAnonymous Transfer: ");
			rawoutput("<select name='anon'>");
			$no=translate_inline("No");
			$yes=translate_inline("Yes");
			rawoutput("<option value='0'".($anon==0?" selected":"").">$no</option>");
			rawoutput("<option value='1'".($anon==1?" selected":"").">$yes</option>");
			rawoutput("</select>");
			output("`n`nOptional Note:");
			rawoutput("<input size='75' name='note' value=$note>");
			output_notl("`n`n");
			$click = translate_inline("Confirm");
			rawoutput("<input type='submit' class='button' value='$click'>");
			rawoutput("</form>");
		}
	}elseif ($op=="confirm"){
		$amount = abs((int)httppost("amount"));
		$target=httppost("target");
		$anon=httppost("anon");
		$note=preg_replace("/[`][bic]/", "",stripslashes(httppost("note")));
		set_module_pref("amount",$amount);
		set_module_pref("target",$target);
		set_module_pref("anon",$anon);
		set_module_pref("note",$note);
		if (!$amount){
			output("`7J. C. Petersen gives you an odd look.");
			output("`n`n\"`&Why would you give someone zero points?");
			output("Perhaps you should try again when you're thinking more clearly?`7\"");
			addnav("Try Again","runmodule.php?module=pointstransfer");
		}elseif ($amount < $mint){
			output("`7J. C. Petersen gives you an odd look.");
			output("`n`n\"`&I'm sorry, but you need to donate at least `@%s`& points.", $mint);
			output("Perhaps you should try again, giving more?`7\"");
			addnav("Try Again","runmodule.php?module=pointstransfer");
		}elseif ($amount > pointstransfer_pointscheck()){
			output("`7J. C. Petersen gives you an odd look.");
			output("`n`n\"`&I'm sorry, but you don't have `@%s`& points to give.", $amount);
			output("Perhaps you should try again with less, or donate more?`7\"");
			addnav("Try Again","runmodule.php?module=pointstransfer");
		}else{
			$newtarget = "";
			for ($x=0; $x<strlen($target); $x++) {
				$newtarget.=substr($target,$x,1)."%"; //Eric rocks.
			}
			$sql="SELECT name FROM ".db_prefix("accounts")." WHERE name LIKE '%".addslashes($newtarget)."' AND locked=0";
			$result=db_query($sql);
			if (!db_num_rows($result)){
				output("`7J. C. Petersen gives you an odd lock.");
				output("`n`n\"`&I'm sorry, but I don't know anyone by that name.");
				output("Perhaps you should try again?`7\"");
				addnav("Try Again","runmodule.php?module=pointstransfer");
			}elseif (db_num_rows($result)>50){
				output("`7J. C. Petersen gives you an odd lock.");
				output("`n`n\"`&I'm sorry, but there's way too many people who might go by that name.");
				output("Perhaps you should narrow it down, next time?`7\"");
				addnav("Try Again","runmodule.php?module=pointstransfer");
			}elseif (db_num_rows($result)>1){
				rawoutput("<form action='runmodule.php?module=pointstransfer&op=send' method='POST'>");
				addnav("","runmodule.php?module=pointstransfer&op=send");
				addnav("Start Over","runmodule.php?module=pointstransfer");
				output("`7J. C. Petersen looks at you.");
				output("`n`n\"`&There's a few people I know by that name.");
				output("Tell me which one you mean, and I'll send those points right off.`7\"");
				output("`n`nPoints: `@%s`7",$amount);
				output("`n`nRecipient: ");
				rawoutput("<select name='target'>");
				for ($i=0;$i<db_num_rows($result);$i++){
					$row=db_fetch_assoc($result);
					$name=$row['name'];
					rawoutput("<option value='$name'>".full_sanitize($name)."</option>");
				}
				rawoutput("</select>");
				output("`n`nAnonymous Transfer: `&%s`7",($anon?"Yes":"No"));
				output("`n`nOptional Note: `&%s`7",$note);
				output_notl("`n`n");
				$send=translate_inline("Send");
				rawoutput("<input type='submit' class='button' value='$send'>");
				rawoutput("</form>");
			}else{
				addnav("Start Over","runmodule.php?module=pointstransfer");
				$row=db_fetch_assoc($result);
				$name=$row['name'];
				output("`7J. C. Petersen smiles at you.");
				output("`n`n\"`&This all looks to be in order to me.");
				output("This is what you meant, right?`7\"");
				output("`n`nPoints: `@%s`7",$amount);
				output("`n`nRecipient: `&%s`7",$name);
				output("`n`nAnonymous Transfer: `&%s`7",($anon?"Yes":"No"));
				output("`n`nOptional Note: `&%s`7",($note?$note:"`inone`i"));
				output_notl("`n`n");
				rawoutput("<form action='runmodule.php?module=pointstransfer&op=send' method='POST'>");
				addnav("","runmodule.php?module=pointstransfer&op=send");
				rawoutput("<input type='hidden' value='$name' name='target'>");
				$send=translate_inline("Send");
				rawoutput("<input type='submit' class='button' value='$send'>");
				rawoutput("</form>");
			}
		}
	}elseif ($op=="send"){
		addnav("Send To Someone Else","runmodule.php?module=pointstransfer");
		$amount=get_module_pref("amount");
		$target=httppost("target");
		if ($target==$session['user']['name']){
			output("`7J. C. Petersen gives you a weird look and puts down his pen.");
			output("`n`n\"`&Why would you ever want to transfer points to yourself?");
			output("Perhaps you should try again when you're thinking more clearly?`7\"");
		}else{
			$anon=get_module_pref("anon");
			$note=get_module_pref("note");
			$note=($note?"`n`nThey also added this note:`n".$note:"");
			$sql="SELECT acctid FROM ".db_prefix("accounts")." WHERE name='$target'";
			$result=db_query($sql);
			$row=db_fetch_assoc($result);
			$targetid=$row['acctid'];
			$sql="UPDATE ".db_prefix("accounts")." SET donation=donation+$amount WHERE acctid=$targetid";
			db_query($sql);
			$session['user']['donation']-=$amount;
			if ($anon){
				systemmail($targetid, array("`@Donator Points Transfer`0"),array("`2Someone has gifted you with `@%s`2 donator points. %s", $amount, $note));
			}else{
				systemmail($targetid,array("`@Donator Points Transfer`0"),array("`&%s`2 has gifted you with `@%s`2 donator points. %s", $session['user']['name'], $amount, $note));
			}
			debuglog($session['user']['name']." sent $amount donator points to $target".($anon?" anonymously.":"."));
			debuglog($session['user']['name']." sent $amount donator points to $target".($anon?" anonymously.":"."),false,$targetid);
			output("`7J. C. Petersen finishes recording the transfer.");
			output("`n`n\"`&Okay, the points have been sent.");
			output("Have a nice day.`7\"");
		}
	}
	page_footer();
}
?>
