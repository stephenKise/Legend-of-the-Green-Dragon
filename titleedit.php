<?php
//Author: Lonny Luberts - 3/18/2005
//Heavily modified by JT Traub
require_once("common.php");
require_once("lib/http.php");

check_su_access(SU_EDIT_USERS);

tlschema("retitle");

page_header("Title Editor");
$op = httpget('op');
$id = httpget('id');
$editarray=array(
	"Titles,title",
	//"titleid"=>"Title Id,hidden",
	"dk"=>"Dragon Kills,int|0",
	// "ref"=>"Arbitrary Tag,int",
	"male"=>"Male Title,text|",
	"female"=>"Female Title,text|",
);
addnav("Other");
require_once("lib/superusernav.php");
superusernav();
addnav("Functions");

if ($op=="save") {
	$male = httppost('male');
	$female = httppost('female');
	$dk = httppost('dk');
	// Ref is currently unused
	// $ref = httppost('ref');
	$ref = '';

	if ((int)$id == 0) {
		$sql = "INSERT INTO ".db_prefix("titles")." (titleid,dk,ref,male,female) VALUES ($id,$dk,'$ref','$male','$female')";
		$note = "`^New title added.`0";
		$errnote = "`\$Unable to add title.`0";
	}else {
		$sql = "UPDATE " . db_prefix("titles") . " SET dk=$dk,ref='$ref',male='$male',female='$female' WHERE titleid=$id";
		$note = "`^Title modified.`0";
		$errnote = "`\$Unable to modify title.`0";
	}
	db_query($sql);
	if (db_affected_rows() == 0) {
		output($errnote);
		rawoutput(db_error());
	} else {
		output($note);
	}
	$op = "";
} elseif ($op == "delete") {
	$sql = "DELETE FROM ".db_prefix("titles")." WHERE titleid='$id'";
	db_query($sql);
	output("`^Title deleted.`0");
	$op = "";
}

if ($op == ""){
	$sql = "SELECT * FROM ".db_prefix("titles")." ORDER BY dk, titleid";
	$result = db_query($sql);
	if (db_num_rows($result)<1){
		output("");
	}else{
		$row = db_fetch_assoc($result);
	}
	output("`@`c`b-=Title Editor=-`b`c");
	$ops = translate_inline("Ops");
	$dks = translate_inline("Dragon Kills");
	// $ref is currently unused
	// $reftag = translate_inline("Reference Tag");
	$mtit = translate_inline("Male Title");
	$ftit = translate_inline("Female Title");
	$edit = translate_inline("Edit");
	$del = translate_inline("Delete");
	$delconfirm = translate_inline("Are you sure you wish to delete this title?");
	rawoutput("<table border=0 cellspacing=0 cellpadding=2 width='100%' align='center'>");
	// reference tag is currently unused
	// rawoutput("<tr class='trhead'><td>$ops</td><td>$dks</td><td>$reftag</td><td>$mtit</td><td>$ftit</td></tr>");
	rawoutput("<tr class='trhead'><td>$ops</td><td>$dks</td><td>$mtit</td><td>$ftit</td></tr>");
	$result = db_query($sql);
	$i = 0;
	while($row = db_fetch_assoc($result)) {
		$id = $row['titleid'];
		rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
		rawoutput("<td>[<a href='titleedit.php?op=edit&id=$id'>$edit</a>|<a href='titleedit.php?op=delete&id=$id' onClick='return confirm(\"$delconfirm\");'>$del</a>]</td>");
		addnav("","titleedit.php?op=edit&id=$id");
		addnav("","titleedit.php?op=delete&id=$id");
		rawoutput("<td>");
		output_notl("`&%s`0",$row['dk']);
		rawoutput("</td><td>");
		// reftag is currently unused
		// output("`^%s`0", $row['ref']);
		// output("</td><td>");
		output_notl("`2%s`0",$row['male']);
		rawoutput("</td><td>");
		output_notl("`6%s`0",$row['female']);
		rawoutput("</td></tr>");
		$i++;
	}
	rawoutput("</table>");
	//modulehook("titleedit", array());
	addnav("Functions");
	addnav("Add a Title", "titleedit.php?op=add");
	addnav("Refresh List", "titleedit.php");
	addnav("Reset Users Titles", "titleedit.php?op=reset");
	title_help();
} elseif ($op=="edit" || $op=="add") {
	require_once("lib/showform.php");
	if ($op=="edit"){
		$sql = "SELECT * FROM ".db_prefix("titles")." WHERE titleid='$id'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
	} elseif ($op=="add") {
		$row = array('titleid'=>0, 'male'=>'', 'female'=>'', 'dk'=>0);
		$id = 0;
	}
	rawoutput("<form action='titleedit.php?op=save&id=$id' method='POST'>");
	addnav("","titleedit.php?op=save&id=$id");
	showform($editarray,$row);
	rawoutput("</form>");
	addnav("Functions");
	addnav("Main Title Editor", "titleedit.php");
	title_help();
} elseif ($op == "reset") {
	require_once("lib/titles.php");
	require_once("lib/names.php");

	output("`^Rebuilding all titles.`0`n`n");
	$sql = "SELECT name,title,dragonkills,acctid,sex,ctitle FROM " . db_prefix("accounts");
	$result = db_query($sql);
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		$oname = $row['name'];
		$dk = $row['dragonkills'];
		$otitle = $row['title'];
		$dk = (int)($row['dragonkills']);
		if (!valid_dk_title($otitle, $dk, $row['sex'])) {
			$sex = translate_inline($row['sex']?"female":"male");
			$newtitle = get_dk_title($dk, (int)$row['sex']);
			$newname = change_player_title($newtitle, $row);
			$id = $row['acctid'];
			if ($oname != $newname) {
				output("`@Changing `^%s`@ to `^%s `@(%s`@ [%s,%s])`n",
						$oname,$newname,$newtitle,$dk,$sex);
				if ($session['user']['acctid']==$row['acctid']){
					$session['user']['title']=$newtitle;
					$session['user']['name']=$newname;
				}else{
					$sql = "UPDATE " . db_prefix("accounts") . " SET name='" .
						addslashes($newname)."', title='".
						addslashes($newtitle)."' WHERE acctid='$id'";
					db_query($sql);
				}
			}elseif ($otitle != $newtitle){
				output("`@Changing only the title (not the name) of `^%s`@ `@(%s`@ [%s,%s])`n",
						$oname,$newtitle,$dk,$sex);
				if ($session['user']['acctid']==$row['acctid']){
					$session['user']['title']=$newtitle;
				}else{
					$sql = "UPDATE " . db_prefix("accounts") .
						" SET title='".addslashes($newtitle) .
						"' WHERE acctid='$id'";
					db_query($sql);
				}
			}
		}
	}
	output("`n`n`^Done.`0");
	addnav("Main Title Editor", "titleedit.php");
}

function title_help()
{
	output("`#You can have multiple titles for a given dragon kill rank.");
	output("If you do, one of those titles will be chosen at random to give to the player when a title is assigned.`n`n");
	output("You can have gaps in the title order.");
	output("If you have a gap, the title given will be for the DK rank less than or equal to the players current number of DKs.`n");
}

page_footer();
?>