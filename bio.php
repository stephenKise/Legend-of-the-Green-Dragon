<?php
// addnews ready
// translator ready
// mail ready
require_once("common.php");
require_once("lib/sanitize.php");

tlschema("bio");

checkday();

$ret = httpget('ret');
if ($ret==""){
	$return = "/list.php";
}else{
	$return = cmd_sanitize($ret);
}

$char = httpget('char');
//Legacy support
if (is_numeric($char)){
	$where = "acctid = $char";
} else {
	$where = "login = '$char'";
}
$sql = "SELECT login, name, level, sex, title, specialty, hashorse, acctid, resurrections, bio, dragonkills, race, clanname, clanshort, clanrank, ".db_prefix("accounts").".clanid, laston, loggedin FROM " . db_prefix("accounts") . " LEFT JOIN " . db_prefix("clans") . " ON " . db_prefix("accounts") . ".clanid = " . db_prefix("clans") . ".clanid WHERE $where";
$result = db_query($sql);
if ($target = db_fetch_assoc($result)) {
  $target['login'] = rawurlencode($target['login']);
  $id = $target['acctid'];
  $target['return_link']=$return;

  page_header("Character Biography: %s", full_sanitize($target['name']));

  tlschema("nav");
  addnav("Return");
  tlschema();

  if ($session['user']['superuser'] & SU_EDIT_USERS){
	  addnav("Superuser");
	  addnav("Edit User","user.php?op=edit&userid=$id");
  }

  modulehook("biotop", $target);

  output("`^Biography for %s`^.",$target['name']);
  $write = translate_inline("Write Mail");
  if ($session['user']['loggedin'])
	  rawoutput("<a href=\"mail.php?op=write&to={$target['login']}\" target=\"_blank\" onClick=\"".popup("mail.php?op=write&to={$target['login']}").";return false;\"><img src='images/newscroll.GIF' width='16' height='16' alt='$write' border='0'></a>");
  output_notl("`n`n");

  if ($target['clanname']>"" && getsetting("allowclans",false)){
	  $ranks = array(CLAN_APPLICANT=>"`!Applicant`0",CLAN_MEMBER=>"`#Member`0",CLAN_OFFICER=>"`^Officer`0",CLAN_LEADER=>"`&Leader`0", CLAN_FOUNDER=>"`\$Founder");
	  $ranks = modulehook("clanranks", array("ranks"=>$ranks, "clanid"=>$target['clanid']));
	  tlschema("clans"); //just to be in the right schema
	  array_push($ranks['ranks'],"`\$Founder");
	  $ranks = translate_inline($ranks['ranks']);
	  tlschema();
	  output("`@%s`2 is a %s`2 to `%%s`2`n", $target['name'], $ranks[$target['clanrank']], $target['clanname']);
  }

  output("`^Title: `@%s`n",$target['title']);
  output("`^Level: `@%s`n",$target['level']);
  $loggedin = false;
  if ($target['loggedin'] &&
		  (date("U") - strtotime($target['laston']) <
			getsetting("LOGINTIMEOUT", 900))) {
	  $loggedin = true;
  }
  $status = translate_inline($loggedin?"`#Online`0":"`\$Offline`0");
  output("`^Status: %s`n",$status);

  output("`^Resurrections: `@%s`n",$target['resurrections']);

  $race = $target['race'];
  if (!$race) $race = RACE_UNKNOWN;
  tlschema("race");
  $race = translate_inline($race);
  tlschema();
  output("`^Race: `@%s`n",$race);

  $genders = array("Male","Female");
  $genders = translate_inline($genders);
  output("`^Gender: `@%s`n",$genders[$target['sex']]);

  $specialties = modulehook("specialtynames",
		  array(""=>translate_inline("Unspecified")));
  if (isset($specialties[$target['specialty']])) {
		output("`^Specialty: `@%s`n",$specialties[$target['specialty']]);
  }
  $sql = "SELECT * FROM " . db_prefix("mounts") . " WHERE mountid='{$target['hashorse']}'";
  $result = db_query_cached($sql, "mountdata-{$target['hashorse']}", 3600);
  $mount = db_fetch_assoc($result);

  $mount['acctid']=$target['acctid'];
  $mount = modulehook("bio-mount",$mount);
  $none = translate_inline("`iNone`i");
  if (!isset($mount['mountname']) || $mount['mountname']=="")
		  $mount['mountname'] = $none;
  output("`^Creature: `@%s`0`n",$mount['mountname']);

  modulehook("biostat", $target);

  if ($target['dragonkills']>0)
	  output("`^Dragon Kills: `@%s`n",$target['dragonkills']);

  if ($target['bio']>"")
	  output("`^Bio: `@`n%s`n",soap($target['bio']));

  modulehook("bioinfo", $target);

  output("`n`^Recent accomplishments (and defeats) of %s`^",$target['name']);
  $result = db_query("SELECT * FROM " . db_prefix("news") . " WHERE accountid={$target['acctid']} ORDER BY newsdate DESC,newsid ASC LIMIT 100");

  $odate="";
  tlschema("news");
  while ($row = db_fetch_assoc($result)) {
	  tlschema($row['tlschema']);
	  if ($row['arguments'] > "") {
		  $arguments = array();
		  $base_arguments = unserialize($row['arguments']);
		  array_push($arguments, $row['newstext']);
		  while(list($key, $val) = each($base_arguments)) {
			  array_push($arguments, $val);
		  }
		  $news = call_user_func_array("sprintf_translate", $arguments);
		  rawoutput(tlbutton_clear());
	  } else {
		  $news = translate_inline($row['newstext']);
		  rawoutput(tlbutton_clear());
	  }
	  tlschema();
	  if ($odate!=$row['newsdate']){
		  output_notl("`n`b`@%s`0`b`n",
				  date("D, M d", strtotime($row['newsdate'])));
		  $odate=$row['newsdate'];
	  }
	  output_notl("`@$news`0`n");
  }
  tlschema();

  if ($ret==""){
	  $return = substr($return,strrpos($return,"/")+1);
	  tlschema("nav");
	  addnav("Return");
	  addnav("Return to the warrior list",$return);
	  tlschema();
  }else{
	  $return = substr($return,strrpos($return,"/")+1);
	  tlschema("nav");
	  addnav("Return");
	  if ($return=="list.php") {
		  addnav("Return to the warrior list",$return);
	  } else {
		  addnav("Return whence you came",$return);
	  }
	  tlschema();
  }

  modulehook("bioend", $target);
  page_footer();
} else {
	page_header("Character has been deleted");
	output("This character is already deleted.");
  if ($ret==""){
	  $return = substr($return,strrpos($return,"/")+1);
	  tlschema("nav");
	  addnav("Return");
	  addnav("Return to the warrior list",$return);
	  tlschema();
  }else{
	  $return = substr($return,strrpos($return,"/")+1);
	  tlschema("nav");
	  addnav("Return");
	  if ($return=="list.php") {
		  addnav("Return to the warrior list",$return);
	  } else {
		  addnav("Return whence you came",$return);
	  }
	  tlschema();
  }
	page_footer();
}
?>