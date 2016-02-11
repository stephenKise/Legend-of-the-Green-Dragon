<?php
output("`@`c`bWelcome to Legend of the Green Dragon`b`c`0");
output("`2This is the installer script for Legend of the Green Dragon, by Eric Stevens & JT Traub.`n");
output("`nIn order to install and use Legend of the Green Dragon (LoGD), you must agree to the license under which it is deployed.`n");
output("`n`&This game is a small project into which we have invested a tremendous amount of personal effort, and we provide this to you absolutely free of charge.`2");
output("Please understand that if you modify our copyright, or otherwise violate the license, you are not only breaking international copyright law (which includes penalties which are defined in whichever country you live), but you're also defeating the spirit of open source, and ruining any good faith which we have demonstrated by providing our blood, sweat, and tears to you free of charge.  You should also know that by breaking the license even one time, it is within our rights to require you to permanently cease running LoGD forever.`n");
output("`nPlease note that in order to use the installer, you must have cookies enabled in your browser.`n");
if (DB_CHOSEN){
	$sql = "SELECT count(*) AS c FROM accounts WHERE superuser & ".SU_MEGAUSER;
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	if ($row['c'] == 0){
		$needsauthentication = false;
	}
	if (httppost("username")>""){
		debug(md5(md5(stripslashes(httppost("password")))), true);
		$version = getsetting("installer_version","-1");
		if ($version == "-1") {
			// Passwords weren't encrypted in these versions
			$sql = "SELECT * FROM ".db_prefix("accounts")." WHERE login='".mysql_real_escape_string(httppost("username"))."' AND password='".mysql_real_escape_string(httppost("password"))."' AND superuser & ".SU_MEGAUSER;
		}else $sql = "SELECT * FROM ".db_prefix("accounts")." WHERE login='".mysql_real_escape_string(httppost("username"))."' AND password='".md5(md5(stripslashes(httppost("password"))))."' AND superuser & ".SU_MEGAUSER;
		$result = db_query($sql);
		if (db_num_rows($result) > 0){
			$row = db_fetch_assoc($result);
			debug($row['password'], true);
			debug(httppost('password'), true);
			// Okay, we have a username with megauser, now we need to do
			// some hackery with the password.
			$needsauthentication=true;
			$p = stripslashes(httppost("password"));
			$p1 = md5($p);
			$p2 = md5($p1);
			debug($p2, true);

			if (getsetting("installer_version", "-1") == "-1") {
				debug("HERE I AM", true);
				// Okay, they are upgrading from 0.9.7  they will have
				// either a non-encrypted password, or an encrypted singly
				// password.
				if (strlen($row['password']) == 32 &&
				$row['password'] == $p1) {
					$needsauthentication = false;
				} elseif ($row['password'] == $p) {
					$needsauthentication = false;
				}
			} elseif ($row['password'] == $p2) {
				$needsauthentication = false;
			}
			if ($needsauthentication === false) {
				redirect("installer.php?stage=1");
			}
			output("`\$That username / password was not found, or is not an account with sufficient privileges to perform the upgrade.`n");
		}else{
			$needsauthentication=true;
			output("`\$That username / password was not found, or is not an account with sufficient privileges to perform the upgrade.`n");
		}
	}else{
		$sql = "SELECT count(*) AS c FROM ".db_prefix("accounts")." WHERE superuser & ".SU_MEGAUSER;
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		if ($row['c']>0){
			$needsauthentication=true;
		}else{
			$needsauthentication=false;
		}
	}
}else{
	$needsauthentication=false;
}
//if a user with appropriate privs is already logged in, let's let them past.
if ($session['user']['superuser'] & SU_MEGAUSER) $needsauthentication=false;
if ($needsauthentication){
	$session['stagecompleted']=-1;
	rawoutput("<form action='installer.php?stage=0' method='POST'>");
	output("`%In order to upgrade this LoGD installation, you will need to provide the username and password of a superuser account with the MEGAUSER privilege`n");
	output("`^Username: `0");
	rawoutput("<input name='username'><br>");
	output("`^Password: `0");
	rawoutput("<input type='password' name='password'><br>");
	$submit = translate_inline("Submit");
	rawoutput("<input type='submit' value='$submit' class='button'>");
	rawoutput("</form>");
}else{
	output("`nPlease continue on to the next page, \"License Agreement.\"");
}
?>