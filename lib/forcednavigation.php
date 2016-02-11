<?php
// translator ready
// addnews ready
// mail ready

$baseaccount = array();
function do_forced_nav($anonymous,$overrideforced){
	global $baseaccount, $session,$REQUEST_URI;
	rawoutput("<!--\nAllowAnonymous: ".($anonymous?"True":"False")."\nOverride Forced Nav: ".($overrideforced?"True":"False")."\n-->");
	if (isset($session['loggedin']) && $session['loggedin']){
		$sql = "SELECT *  FROM ".db_prefix("accounts")." WHERE acctid = '".$session['user']['acctid']."'";
		$result = db_query($sql);
		if (db_num_rows($result)==1){
			$session['user']=db_fetch_assoc($result);
			$baseaccount = $session['user'];
			$session['bufflist']=unserialize($session['user']['bufflist']);
			if (!is_array($session['bufflist'])) $session['bufflist']=array();
			$session['user']['dragonpoints']=unserialize($session['user']['dragonpoints']);
			$session['user']['prefs']=unserialize($session['user']['prefs']);
			if (!is_array($session['user']['dragonpoints'])) $session['user']['dragonpoints']=array();
			if (is_array(unserialize($session['user']['allowednavs']))){
				$session['allowednavs']=unserialize($session['user']['allowednavs']);
			}else{
				$session['allowednavs']=array($session['user']['allowednavs']);
			}
			if (!$session['user']['loggedin'] || ( (date("U") - strtotime($session['user']['laston'])) > getsetting("LOGINTIMEOUT",900)) ){
				$session=array();
				redirect("index.php?op=timeout","Account not logged in but session thinks they are.");
			}
		}else{
			$session=array();
			$session['message']=translate_inline("`4Error, your login was incorrect`0","login");
			redirect("index.php","Account Disappeared!");
		}
		db_free_result($result);
		if (isset($session['allowednavs'][$REQUEST_URI]) && $session['allowednavs'][$REQUEST_URI] && $overrideforced!==true){
			$session['allowednavs']=array();
		}else{
			if ($overrideforced!==true){
				redirect("badnav.php","Navigation not allowed to $REQUEST_URI");
			}
		}
	}else{
		if (!$anonymous){
			$session['message']=translate_inline("You are not logged in, this may be because your session timed out.","login");
			redirect("index.php?op=timeout","Not logged in: $REQUEST_URI");
		}
	}
}
?>
