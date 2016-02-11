<?php
output("`@`c`bSuperuser Accounts`b`c");
debug($logd_version, true);
$sql = "SELECT login, password FROM ".db_prefix("accounts")." WHERE superuser & ".SU_MEGAUSER;
$result = db_query($sql);
if (db_num_rows($result)==0){
	if (httppost("name")>""){
		$showform=false;
		if (httppost("pass1")!=httppost("pass2")){
			output("`\$Oops, your passwords don't match.`2`n");
			$showform=true;
		}elseif (strlen(httppost("pass1"))<6){
			output("`\$Whoa, that's a short password, you really should make it longer.`2`n");
			$showform=true;
		}else{
			// Give the superuser a decent set of privs so they can
			// do everything needed without having to first go into
			// the user editor and give themselves privs.
			$su = SU_MEGAUSER | SU_EDIT_MOUNTS | SU_EDIT_CREATURES |
			SU_EDIT_PETITIONS | SU_EDIT_COMMENTS | SU_EDIT_DONATIONS |
			SU_EDIT_USERS | SU_EDIT_CONFIG | SU_INFINITE_DAYS |
			SU_EDIT_EQUIPMENT | SU_EDIT_PAYLOG | SU_DEVELOPER |
			SU_POST_MOTD | SU_MODERATE_CLANS | SU_EDIT_RIDDLES |
			SU_MANAGE_MODULES | SU_AUDIT_MODERATION | SU_RAW_SQL |
			SU_VIEW_SOURCE | SU_NEVER_EXPIRE;
			$name = httppost("name");
			$pass = md5(md5(stripslashes(httppost("pass1"))));
			$sql = "DELETE FROM ".db_prefix("accounts")." WHERE login='$name'";
			db_query($sql);
			$sql = "INSERT INTO " .db_prefix("accounts") ." (login,password,superuser,name,ctitle,regdate) VALUES('$name','$pass',$su,'`%Admin `&$name`0','`%Admin', NOW())";
			db_query($sql);
			output("`^Your superuser account has been created as `%Admin `&$name`^!");
			savesetting("installer_version",$logd_version);
		}
	}else{
		$showform=true;
		savesetting("installer_version",$logd_version);
	}
	if ($showform){
		rawoutput("<form action='installer.php?stage=$stage' method='POST'>");
		output("Enter a name for your superuser account:");
		rawoutput("<input name='name' value=\"".htmlentities(httppost("name"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">");
		output("`nEnter a password: ");
		rawoutput("<input name='pass1' type='password'>");
		output("`nConfirm your password: ");
		rawoutput("<input name='pass2' type='password'>");
		$submit = translate_inline("Create");
		rawoutput("<br><input type='submit' value='$submit' class='button'>");
		rawoutput("</form>");
	}
}else{
	output("`#You already have a superuser account set up on this server.");
	savesetting("installer_version",$logd_version);
}
?>