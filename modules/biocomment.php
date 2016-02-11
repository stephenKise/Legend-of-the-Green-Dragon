<?php
// translator ready
// addnews ready
// mail ready

function biocomment_getmoduleinfo(){
	$info = array(
		"name"=>"Bio Commentary",
		"version"=>"1.1",
		"author"=>"Sneakabout",
		"category"=>"Administrative",
		"download"=>"core_module",
		"prefs"=>array(
			"Bio Commentary Preferences,title",
			"canaddcomments"=>"Can this person add comments?,bool|0",
		),
	);
	return $info;
}
function biocomment_install(){
	module_addhook("bioinfo");
	//added by Red to fix it up
	$sql="SELECT version FROM ".db_prefix("modules")." WHERE modulename='biocomment'";
	$result=db_query($sql);
	$row=db_fetch_assoc($result);
	$oldver=$row['version'];
	if ($oldver=="1.0"){
		debug("Updating from version 1.0, changing over commentary.");
		$sql="SELECT commentid, section FROM ".db_prefix("commentary")." WHERE section LIKE 'pet-bio-%'";
		$result=db_query($sql);
		$count=db_num_rows($result);
		for ($i; $i<$count; $i++){
			$row=db_fetch_assoc($result);
			$oldsection=$row['section'];
			$login=substr($oldsection,8);
			$selsql="SELECT acctid FROM ".db_prefix("accounts")." WHERE login LIKE '$login%'";
			$selresult=db_query($selsql);
			if (db_num_rows($selresult)>1){
				$delsql="DELETE FROM ".db_prefix("commentary")." WHERE commentid={$row['commentid']}";
				db_query($delsql);
			}else{
				$selrow=db_fetch_assoc($selresult);
				$updatesql="UPDATE ".db_prefix("commentary")." SET section='pet-bio-".$selrow['acctid']."' WHERE section='$oldsection'";
				db_query($updatesql);
			}
		}
	}else{
		debug("Ok version, not changing over commentary.");
	}
	//not Red anymore
	return true;
}
function biocomment_uninstall(){
	return true;
}
function biocomment_dohook($hookname,$args){
	switch($hookname) {
	case "bioinfo":
		global $session;
/*
//	Out moded for changeover - Red
		$char = httpget('char');
		$char=strtolower($char);
		if (strlen($char) > 12) {
			$char = substr_replace($char, "", 12); // Booger's code!
		}
*///replaced with next line
		$acctid=$args['acctid'];
		require_once("lib/sanitize.php");
		require_once("lib/commentary.php");
		$canadd=get_module_pref("canaddcomments");
		if ($canadd==1) {
			addcommentary();
			commentdisplay("`n`^Commentary from others:`0`n",
				"pet-bio-{$acctid}","Add a comment");
			output_notl("`n");
		}
		$args['nocollapse']=1;
		break;
	}
	return $args;
}
?>
