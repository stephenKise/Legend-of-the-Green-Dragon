<?php
// mail ready
// addnews ready
// translator ready
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/systemmail.php");
require_once("lib/checkban.php");
require_once("lib/http.php");

tlschema("login");
translator_setup();
$op = httpget('op');
$name = httppost('name');
$iname = getsetting("innname", LOCATION_INN);
$vname = getsetting("villagename", LOCATION_FIELDS);

if ($name!=""){
	if ($session['loggedin']){
		redirect("badnav.php");
	}else{
		$password = httppost('password');
		$password = stripslashes($password);
		if (substr($password, 0, 5) == "!md5!") {
			$password = md5(substr($password, 5));
		} elseif (substr($password, 0, 6) == "!md52!") {// && strlen($password) == 38) {
			$force = httppost('force');
			if ($force) {
				$password = addslashes(substr($password, 6));
			} else {
				$password='no hax0rs for j00!';
			}
		} else {
			$password = md5(md5($password));
		}
		$sql = "SELECT * FROM " . db_prefix("accounts") . " WHERE login = '$name' AND password='$password' AND locked=0";
		$result = db_query($sql);
		if (db_num_rows($result)==1){
			$session['user']=db_fetch_assoc($result);
			$companions = @unserialize($session['user']['companions']);
			if (!is_array($companions)) $companions = array();
			$baseaccount = $session['user'];
			checkban($session['user']['login']); //check if this account is banned
			checkban(); //check if this computer is banned
			// If the player isn't allowed on for some reason, anything on
			// this hook should automatically call page_footer and exit
			// itself.
			modulehook("check-login");

			if ($session['user']['emailvalidation']!="" && substr($session['user']['emailvalidation'],0,1)!="x"){
				$session['user']=array();
				$session['message']=translate_inline("`4Error, you must validate your email address before you can log in.");
				echo appoencode($session['message']);
				exit();
			}else{
				$session['loggedin']=true;
				$session['laston']=date("Y-m-d H:i:s");
				$session['sentnotice']=0;
				$session['user']['dragonpoints']=unserialize($session['user']['dragonpoints']);
				$session['user']['prefs']=unserialize($session['user']['prefs']);
				$session['bufflist']=unserialize($session['user']['bufflist']);
				if (!is_array($session['bufflist']))
					$session['bufflist'] = array();
				if (!is_array($session['user']['dragonpoints'])) $session['user']['dragonpoints']=array();
				invalidatedatacache("charlisthomepage");
				invalidatedatacache("list.php-warsonline");
				$session['user']['laston'] = date("Y-m-d H:i:s");

				// Handle the change in number of users online
				translator_check_collect_texts();

				// Let's throw a login module hook in here so that modules
				// like the stafflist which need to invalidate the cache
				// when someone logs in or off can do so.
				modulehook("player-login");

				if ($session['user']['loggedin']){
					$session['allowednavs']=unserialize($session['user']['allowednavs']);
					$link = "<a href='" . $session['user']['restorepage'] . "'>" . $session['user']['restorepage'] . "</a>";

					$str = sprintf_translate("Sending you to %s, have a safe journey", $link);
					header("Location: {$session['user']['restorepage']}");
					saveuser();
					echo $str;
					exit();
				}

				db_query("UPDATE " . db_prefix("accounts") . " SET loggedin=".true.", laston='".date("Y-m-d H:i:s")."' WHERE acctid = ".$session['user']['acctid']);

				$session['user']['loggedin']=true;
				$location = $session['user']['location'];
				if ($session['user']['location']==$iname)
					$session['user']['location']=$vname;

				if ($session['user']['restorepage']>""){
					redirect($session['user']['restorepage']);
				}else{
					if ($location == $iname) {
						redirect("inn.php?op=strolldown");
					}else{
						redirect("news.php");
					}
				}
			}
		}else{
			$session['message']=translate_inline("`4Error, your login was incorrect`0");
			//now we'll log the failed attempt and begin to issue bans if
			//there are too many, plus notify the admins.
			$sql = "DELETE FROM " . db_prefix("faillog") . " WHERE date<'".date("Y-m-d H:i:s",strtotime("-".(getsetting("expirecontent",180)/4)." days"))."'";
			checkban();
			db_query($sql);
			$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE login='$name'";
			$result = db_query($sql);
			if (db_num_rows($result)>0){
				// just in case there manage to be multiple accounts on
				// this name.
				while ($row=db_fetch_assoc($result)){
					$post = httpallpost();
					$sql = "INSERT INTO " . db_prefix("faillog") . " VALUES (0,'".date("Y-m-d H:i:s")."','".addslashes(serialize($post))."','{$_SERVER['REMOTE_ADDR']}','{$row['acctid']}','{$_COOKIE['lgi']}')";
					db_query($sql);
					$sql = "SELECT " . db_prefix("faillog") . ".*," . db_prefix("accounts") . ".superuser,name,login FROM " . db_prefix("faillog") . " INNER JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid=" . db_prefix("faillog") . ".acctid WHERE ip='{$_SERVER['REMOTE_ADDR']}' AND date>'".date("Y-m-d H:i:s",strtotime("-1 day"))."'";
					$result2 = db_query($sql);
					$c=0;
					$alert="";
					$su=false;
					while ($row2=db_fetch_assoc($result2)){
						if ($row2['superuser']>0) {$c+=1; $su=true;}
						$c+=1;
						$alert.="`3{$row2['date']}`7: Failed attempt from `&{$row2['ip']}`7 [`3{$row2['id']}`7] to log on to `^{$row2['login']}`7 ({$row2['name']}`7)`n";
					}
					if ($c>=10){
						// 5 failed attempts for superuser, 10 for regular user
						$banmessage=translate_inline("Automatic System Ban: Too many failed login attempts.");
						$sql = "INSERT INTO " . db_prefix("bans") . " VALUES ('{$_SERVER['REMOTE_ADDR']}','','".date("Y-m-d H:i:s",strtotime("+".($c*3)." hours"))."','$banmessage','System','0000-00-00 00:00:00')";
						db_query($sql);
						if ($su){
							// send a system message to admins regarding
							// this failed attempt if it includes superusers.
							$sql = "SELECT acctid FROM " . db_prefix("accounts") ." WHERE (superuser&".SU_EDIT_USERS.")";
							$result2 = db_query($sql);
							$subj = translate_mail(array("`#%s failed to log in too many times!",$_SERVER['REMOTE_ADDR']),0);
							$number=db_num_rows($result2);
							for ($i=0;$i<$number;$i++){
								$row2 = db_fetch_assoc($result2);
								//delete old messages that
								$sql = "DELETE FROM " . db_prefix("mail") . " WHERE msgto={$row2['acctid']} AND msgfrom=0 AND subject = '".serialize($subj)."' AND seen=0";
								db_query($sql);
								if (db_affected_rows()>0) $noemail = true; else $noemail = false;
								$msg = translate_mail(array("This message is generated as a result of one or more of the accounts having been a superuser account.  Log Follows:`n`n%s",$alert),0);
								systemmail($row2['acctid'],$subj,$msg,0,$noemail);
							}//end for
						}//end if($su)
					}//end if($c>=10)
				}//end while
			}//end if (db_num_rows)
			redirect("index.php");
		}
	}
}else if ($op=="logout"){
	if ($session['user']['loggedin']){
		$location = $session['user']['location'];
		if ($location == $iname) {
			$session['user']['restorepage']="inn.php?op=strolldown";
		} else {
			$session['user']['restorepage']="news.php";
		}
		$sql = "UPDATE " . db_prefix("accounts") . " SET loggedin=0,restorepage='{$session['user']['restorepage']}' WHERE acctid = ".$session['user']['acctid'];
		db_query($sql);
		invalidatedatacache("charlisthomepage");
		invalidatedatacache("list.php-warsonline");

		// Handle the change in number of users online
		translator_check_collect_texts();

		// Let's throw a logout module hook in here so that modules
		// like the stafflist which need to invalidate the cache
		// when someone logs in or off can do so.
		modulehook("player-logout");
		saveuser();
	}
	$session=array();
	redirect("index.php");
}
// If you enter an empty username, don't just say oops.. do something useful.
$session=array();
$session['message']=translate_inline("`4Error, your login was incorrect`0");
redirect("index.php");
?>