<?php
// translator ready
// addnews ready
// mail ready
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/is_email.php");
require_once("lib/checkban.php");
require_once("lib/http.php");

tlschema("create");

$trash = getsetting("expiretrashacct",1);
$new = getsetting("expirenewacct",10);
$old = getsetting("expireoldacct",45);

checkban();
$op = httpget('op');

if ($op=="val"){
	$id = httpget('id');
	$sql = "SELECT acctid,login,password,name FROM ". db_prefix("accounts") . " WHERE emailvalidation='$id' AND emailvalidation!=''";
	$result = db_query($sql);
	if (db_num_rows($result)>0) {
		$row = db_fetch_assoc($result);
		$sql = "UPDATE " . db_prefix("accounts") . " SET emailvalidation='' WHERE emailvalidation='$id';";
		db_query($sql);
		output("`#`cYour email has been validated.  You may now log in.`c`0");
		rawoutput("<form action='login.php' method='POST'>");
		rawoutput("<input name='name' value=\"{$row['login']}\" type='hidden'>");
		rawoutput("<input name='password' value=\"!md52!{$row['password']}\" type='hidden'>");
		rawoutput("<input name='force' value='1' type='hidden'>");
		output("Your email has been validated, your login name is `^%s`0.`n`n",
				$row['login']);
		$click = translate_inline("Click here to log in");
		rawoutput("<input type='submit' class='button' value='$click'></form>");
		output_notl("`n");
		if ($trash > 0) {
			output("`^Characters that have never been logged into will be deleted after %s day(s) of no activity.`n`0", $trash);
		}
		if ($new > 0) {
			output("`^Characters that have never reached level 2 will be deleted after %s days of no activity.`n`0", $new);
		}
		if ($old > 0) {
			output("`^Characters that have reached level 2 at least once will be deleted after %s days of no activity.`n`0", $old);
		}
		//only set this if they are not doing a forgotten password.
		if (substr($id,0,1)!="x") {
			savesetting("newestplayer", $row['acctid']);
			invalidatedatacache('newest');
		}
	}else{
		output("`#Your email could not be verified.");
		output("This may be because you already validated your email.");
		output("Try to log in, and if that doesn't help, use the petition link at the bottom of the page.");
	}
}
if ($op=="forgot"){
	$charname = httppost('charname');
	if ($charname!=""){
		$sql = "SELECT acctid,login,emailaddress,emailvalidation,password FROM " . db_prefix("accounts") . " WHERE login='$charname'";
		$result = db_query($sql);
		if (db_num_rows($result)>0){
			$row = db_fetch_assoc($result);
			if (trim($row['emailaddress'])!=""){
				if ($row['emailvalidation']==""){
					$row['emailvalidation']=substr("x".md5(date("Y-m-d H:i:s").$row['password']),0,32);
					$sql = "UPDATE " . db_prefix("accounts") . " SET emailvalidation='{$row['emailvalidation']}' where login='{$row['login']}'";
					db_query($sql);
				}
				$subj = translate_mail("LoGD Account Verification",$row['acctid']);
				$msg = translate_mail(array("Someone from %s requested a forgotten password link for your account.  If this was you, then here is your"
						." link, you may click it to log into your account and change your password from your preferences page in the village square.`n`n"
						."If you didn't request this email, then don't sweat it, you're the one who is receiving this email, not them."
						."`n`n  http://%s?op=val&id=%s `n`n Thanks for playing!",
						$_SERVER['REMOTE_ADDR'],
						($_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT'] == 80?"":":".$_SERVER['SERVER_PORT']).$_SERVER['SCRIPT_NAME']),
						$row['emailvalidation']
						),$row['acctid']);
				mail($row['emailaddress'],$subj,str_replace("`n","\n",$msg),translate_inline("From:").getsetting("gameadminemail","postmaster@localhost.com"));
				output("`#Sent a new validation email to the address on file for that account.");
				output("You may use the validation email to log in and change your password.");
			}else{
				output("`#We're sorry, but that account does not have an email address associated with it, and so we cannot help you with your forgotten password.");
				output("Use the Petition for Help link at the bottom of the page to request help with resolving your problem.");
			}
		}else{
			output("`#Could not locate a character with that name.");
			output("Look at the List Warriors page off the login page to make sure that the character hasn't expired and been deleted.");
		}
	}else{
		rawoutput("<form action='create.php?op=forgot' method='POST'>");
		output("`bForgotten Passwords:`b`n`n");
		output("Enter your character's name: ");
		rawoutput("<input name='charname'>");
		output_notl("`n");
		$send = translate_inline("Email me my password");
		rawoutput("<input type='submit' class='button' value='$send'>");
		rawoutput("</form>");
	}
}
page_header("Create A Character");
if (getsetting("allowcreation",1)==0){
	output("`\$Creation of new accounts is disabled on this server.");
	output("You may try it again another day or contact an administrator.");
}else{
	if ($op=="create"){
		$emailverification="";
		$shortname = sanitize_name(getsetting("spaceinname", 0), httppost('name'));

		if (soap($shortname)!=$shortname){
			output("`\$Error`^: Bad language was found in your name, please consider revising it.`n");
			$op="";
		}else{
			$blockaccount=false;
			$email = httppost('email');
			$pass1= httppost('pass1');
			$pass2= httppost('pass2');
			if (getsetting("blockdupeemail",0)==1 && getsetting("requireemail",0)==1){
				$sql = "SELECT login FROM " . db_prefix("accounts") . " WHERE emailaddress='$email'";
				$result = db_query($sql);
				if (db_num_rows($result)>0){
					$blockaccount=true;
					$msg.= translate_inline("You may have only one account.`n");
				}
			}

			$passlen = (int)httppost("passlen");
			if (substr($pass1, 0, 5) != "!md5!" &&
					substr($pass1, 0, 6) != "!md52!") {
				$passlen = strlen($pass1);
			}
			if ($passlen<=3){
					$msg.=translate_inline("Your password must be at least 4 characters long.`n");
				$blockaccount=true;
			}
			if ($pass1!=$pass2){
				$msg.=translate_inline("Your passwords do not match.`n");
				$blockaccount=true;
			}
			if (strlen($shortname)<3){
				$msg.=translate_inline("Your name must be at least 3 characters long.`n");
				$blockaccount=true;
			}
			if (strlen($shortname)>25){
				$msg.=translate_inline("Your character's name cannot exceed 25 characters.`n");
				$blockaccount=true;
			}
			if (getsetting("requireemail",0)==1 && is_email($email) || getsetting("requireemail",0)==0){
			}else{
				$msg.=translate_inline("You must enter a valid email address.`n");
				$blockaccount=true;
			}
			$args = modulehook("check-create", httpallpost());
			if(isset($args['blockaccount']) && $args['blockaccount']) {
				$msg .= $args['msg'];
				$blockaccount = true;
			}

			if (!$blockaccount){
				$sql = "SELECT name FROM " . db_prefix("accounts") . " WHERE login='$shortname'";
				$result = db_query($sql);
				if (db_num_rows($result)>0){
					output("`\$Error`^: Someone is already known by that name in this realm, please try again.");
					$op="";
				}else{
					$sex = (int)httppost('sex');
					// Inserted the following line to prevent hacking
					// Reported by Eliwood
					if ($sex <> SEX_MALE) $sex = SEX_FEMALE;
					require_once("lib/titles.php");
					$title = get_dk_title(0, $sex);
					if (getsetting("requirevalidemail",0)){
						$emailverification=md5(date("Y-m-d H:i:s").$email);
					}
					$refer = httpget('r');
					if ($refer>""){
						$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE login='$refer'";
						$result = db_query($sql);
						$ref = db_fetch_assoc($result);
						$referer=$ref['acctid'];
					}else{
						$referer=0;
					}
					$dbpass = "";
					if (substr($pass1, 0, 5) == "!md5!") {
						$dbpass = md5(substr($pass1, 5));
					} else {
						$dbpass = md5(md5($pass1));
					}
					$sql = "INSERT INTO " . db_prefix("accounts") . "
						(name, superuser, title, password, sex, login, laston, uniqueid, lastip, gold, emailaddress, emailvalidation, referer, regdate)
						VALUES
						('$title $shortname', '".getsetting("defaultsuperuser",0)."', '$title', '$dbpass', '$sex', '$shortname', '".date("Y-m-d H:i:s",strtotime("-1 day"))."', '".$_COOKIE['lgi']."', '".$_SERVER['REMOTE_ADDR']."', ".getsetting("newplayerstartgold",50).", '$email', '$emailverification', '$referer', NOW())";
					db_query($sql);
					if (db_affected_rows(LINK)<=0){
						output("`\$Error`^: Your account was not created for an unknown reason, please try again. ");
					}else{
						$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE login='$shortname'";
						$result = db_query($sql);
						$row = db_fetch_assoc($result);
						$args = httpallpost();
						$args['acctid'] = $row['acctid'];
						//insert output
						$sql_output = "INSERT INTO " . db_prefix("accounts_output") . " VALUES ({$row['acctid']},'');";
						db_query($sql_output);
						//end
						modulehook("process-create", $args);
						if ($emailverification!=""){
							$subj = translate_mail("LoGD Account Verification",0);
							 $msg = translate_mail(array("Login name: %s `n`nIn order to verify your account, you will need to click on the link below.`n`n http://%s?op=val&id=%s `n`nThanks for playing!",$shortname,
								($_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT'] == 80?"":":".$_SERVER['SERVER_PORT']).$_SERVER['SCRIPT_NAME']),
								$emailverification),
								0);
							mail($email,$subj,str_replace("`n","\n",$msg),"From: ".getsetting("gameadminemail","postmaster@localhost.com"));
							output("`4An email was sent to `\$%s`4 to validate your address.  Click the link in the email to activate your account.`0`n`n", $email);
						}else{
							rawoutput("<form action='login.php' method='POST'>");
							rawoutput("<input name='name' value=\"$shortname\" type='hidden'>");
							rawoutput("<input name='password' value=\"$pass1\" type='hidden'>");
							output("Your account was created, your login name is `^%s`0.`n`n", $shortname);
							$click = translate_inline("Click here to log in");
							rawoutput("<input type='submit' class='button' value='$click'>");
							rawoutput("</form>");
							output_notl("`n");
							if ($trash > 0) {
								output("`^Characters that have never been logged into will be deleted after %s day(s) of no activity.`n`0", $trash);
							}
							if ($new > 0) {
								output("`^Characters that have never reached level 2 will be deleted after %s days of no activity.`n`0",$new);
							}
							if ($old > 0) {
								output("`^Characters that have reached level 2 at least once will be deleted after %s days of no activity.`n`0", $old);
							}
							savesetting("newestplayer", $row['acctid']);
						}
					}
				}
			}else{
				output("`\$Error`^:`n%s", $msg);
				$op="";
			}
		}
	}
	if ($op==""){
		output("`&`c`bCreate a Character`b`c`0");
		$refer=httpget('r');
		if ($refer) $refer = "&r=".htmlentities($refer, ENT_COMPAT, getsetting("charset", "ISO-8859-1"));

		rawoutput("<script language='JavaScript' src='lib/md5.js'></script>");
		rawoutput("<script language='JavaScript'>
		<!--
		function md5pass(){
			// encode passwords
			var plen = document.getElementById('passlen');
			var pass1 = document.getElementById('pass1');
			plen.value = pass1.value.length;

			if(pass1.value.substring(0, 5) != '!md5!') {
				pass1.value = '!md5!'+hex_md5(pass1.value);
			}
			var pass2 = document.getElementById('pass2');
			if(pass2.value.substring(0, 5) != '!md5!') {
				pass2.value = '!md5!'+hex_md5(pass2.value);
			}

		}
		//-->
		</script>");
		rawoutput("<form action=\"create.php?op=create$refer\" method='POST' onSubmit=\"md5pass();\">");
		// this is the first thing a new player will se, so let's make it look
		// better
		rawoutput("<input type='hidden' name='passlen' id='passlen' value='0'>");
		rawoutput("<table><tr valign='top'><td>");
		output("How will you be known to this world? ");
		rawoutput("</td><td><input name='name'></td></tr><tr valign='top'><td>");
		output("Enter a password: ");
		rawoutput("</td><td><input type='password' name='pass1' id='pass1'></td></tr><tr valign='top'><td>");
		output("Re-enter it for confirmation: ");
		rawoutput("</td><td><input type='password' name='pass2' id='pass2'></td></tr><tr valign='top'><td>");
		output("Enter your email address: ");
		$r1 = translate_inline("`^(optional -- however, if you choose not to enter one, there will be no way that you can reset your password if you forget it!)`0");
		$r2 = translate_inline("`\$(required)`0");
		$r3 = translate_inline("`\$(required, an email will be sent to this address to verify it before you can log in)`0");
		if (getsetting("requireemail", 0) == 0) {
			$req = $r1;
		} elseif (getsetting("requirevalidemail", 0) == 0) {
			$req = $r2;
		} else {
			$req = $r3;
		}
		rawoutput("</td><td><input name='email'>");
		output_notl("%s", $req);
		rawoutput("</td></tr></table>");
		output("`nAnd are you a %s Female or a %s Male?`n",
				"<input type='radio' name='sex' value='1'>",
				"<input type='radio' name='sex' value='0' checked>",true);
		modulehook("create-form");
		$createbutton = translate_inline("Create your character");
		rawoutput("<input type='submit' class='button' value='$createbutton'>");
		output_notl("`n`n");
		if ($trash > 0) {
			output("`^Characters that have never been logged into will be deleted after %s day(s) of no activity.`n`0", $trash);
		}
		if ($new > 0) {
			output("`^Characters that have never reached level 2 will be deleted after %s days of no activity.`n`0",$new);
		}
		if ($old > 0) {
			output("`^Characters that have reached level 2 at least once will be deleted after %s days of no activity.`n`0", $old);
		}
		rawoutput("</form>");
	}
}
addnav("Login","index.php");
page_footer();
?>