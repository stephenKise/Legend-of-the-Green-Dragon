<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/http.php");

check_su_access(SU_EDIT_CREATURES);

tlschema("creatures");

$creaturestats = array();

require_once 'lib/creatures.php';
for ($i=1;$i<18;$i++){
	$creaturestats[$i] = creature_stats($i);
}

page_header("Creature Editor");

require_once("lib/superusernav.php");
superusernav();

$op = httpget("op");
$subop = httpget("subop");
if ($op == "save"){
	$forest = (int)(httppost('forest'));
	$grave = (int)(httppost('graveyard'));
	$id = httppost('creatureid');
	if (!$id) $id = httpget("creatureid");
	if ($subop == "") {
		$post = httpallpost();
		$lev = (int)httppost('creaturelevel');
		if ($id){
			$sql = "";
			reset($post);
			while (list($key,$val)=each($post)){
				if (substr($key,0,8)=="creature") $sql.="$key = '$val', ";
			}
			reset($creaturestats[$lev]);
			while (list($key,$val)=each($creaturestats[$lev])){
				if ( $key!="creaturelevel" && substr($key,0,8)=="creature"){
					$sql.="$key = \"".addslashes($val)."\", ";
				}
			}
			$sql.=" forest='$forest', ";
			$sql.=" graveyard='$grave' ";
			$sql="UPDATE " . db_prefix("creatures") . " SET " . $sql . " WHERE creatureid='$id'";
			db_query($sql) or output("`\$".db_error(LINK)."`0`n`#$sql`0`n");
		}else{
			$cols = array();
			$vals = array();

			reset($post);
			while (list($key,$val)=each($post)){
				if (substr($key,0,8)=="creature") {
					array_push($cols,$key);
					array_push($vals,$val);
				}
			}
			array_push($cols, "forest");
			array_push($vals, $forest);
			array_push($cols, "graveyard");
			array_push($vals, $grave);
			reset($creaturestats[$lev]);
			while (list($key,$val)=each($creaturestats[$lev])){
				if ($key!="creaturelevel"&& substr($key,0,8)=="creature"){
					array_push($cols,$key);
					array_push($vals,$val);
				}
			}
			$sql="INSERT INTO " . db_prefix("creatures") . " (".join(",",$cols).",createdby) VALUES (\"".join("\",\"",$vals)."\",\"".addslashes($session['user']['login'])."\")";
			db_query($sql);
			$id = db_insert_id();
		}
		if (db_affected_rows()) {
			output("`^Creature saved!`0`n");
		} else {
			output("`^Creature `\$not`^ saved!`0`n");
		}
	} elseif ($subop == "module") {
		// Save module settings
		$module = httpget("module");
		$post = httpallpost();
		reset($post);
		while(list($key, $val) = each($post)) {
			set_module_objpref("creatures", $id, $key, $val, $module);
		}
		output("`^Saved!`0`n");
	}
	// Set the httpget id so that we can do the editor once we save
	httpset("creatureid", $id, true);
	// Set the httpget op so we drop back into the editor
	httpset("op", "edit");
}

$op = httpget('op');
$id = httpget('creatureid');
if ($op=="del"){
	$sql = "DELETE FROM " . db_prefix("creatures") . " WHERE creatureid = '$id'";
	db_query($sql);
	if (db_affected_rows()>0){
		output("Creature deleted`n`n");
	}else{
		output("Creature not deleted: %s", db_error(LINK));
	}
	$op="";
	httpset('op', "");
}
if ($op=="" || $op=="search"){
	$level = httpget("level");
	if (!$level) $level = 1;
	$q = httppost("q");
	if ($q) {
		$where = "creaturename LIKE '%$q%' OR creatureweapon LIKE '%$q%' OR creaturelose LIKE '%$q%' OR createdby LIKE '%$q%'";
	} else {
		$where = "creaturelevel='$level'";
	}
	$sql = "SELECT * FROM " . db_prefix("creatures") . " WHERE $where ORDER BY creaturelevel,creaturename";
	$result = db_query($sql);
	// Search form
	$search = translate_inline("Search");
	rawoutput("<form action='creatures.php?op=search' method='POST'>");
	output("Search by field: ");
	rawoutput("<input name='q' id='q'>");
	rawoutput("<input type='submit' class='button' value='$search'>");
	rawoutput("</form>");
	rawoutput("<script language='JavaScript'>document.getElementById('q').focus();</script>",true);
	addnav("","creatures.php?op=search");

	addnav("Levels");
	$sql1 = "SELECT count(creatureid) AS n,creaturelevel FROM " . db_prefix("creatures") . " group by creaturelevel order by creaturelevel";
	$result1 = db_query($sql1);
	while ($row = db_fetch_assoc($result1)) {
		addnav(array("Level %s: (%s creatures)", $row['creaturelevel'], $row['n']),
				"creatures.php?level={$row['creaturelevel']}");
	}
	// There is no reason to allow players to add creatures to level 17 and 18.
	// Players aren't supposed to stay at level 15 at all.
	if ($level <= 16) {
		addnav("Edit");
		addnav("Add a creature","creatures.php?op=add&level=$level");
	}
	$opshead = translate_inline("Ops");
	$idhead = translate_inline("ID");
	$name = translate_inline("Name");
	$lev = translate_inline("Level");
	$weapon = translate_inline("Weapon");
	$winmsg = translate_inline("Win");
	$diemsg = translate_inline("Die");
	$author = translate_inline("Author");
	$edit = translate_inline("Edit");
	$confirm = translate_inline("Are you sure you wish to delete this creature?");
	$del = translate_inline("Del");

	rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
	rawoutput("<tr class='trhead'>");
	rawoutput("<td>$opshead</td><td>$idhead</td><td>$name</td><td>$lev</td><td>$weapon</td><td>$winmsg</td><td>$diemsg</td><td>$author</td></tr>");
	addnav("","creatures.php");
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		rawoutput("<tr class='".($i%2==0?"trdark":"trlight")."'>", true);
		rawoutput("<td>[ <a href='creatures.php?op=edit&creatureid={$row['creatureid']}'>");
		output_notl("%s", $edit);
		rawoutput("</a> | <a href='creatures.php?op=del&creatureid={$row['creatureid']}&level={$row['creaturelevel']}' onClick='return confirm(\"$confirm\");'>");
		output_notl("%s", $del);
		rawoutput("</a> ]</td><td>");
		addnav("","creatures.php?op=edit&creatureid={$row['creatureid']}");
		addnav("","creatures.php?op=del&creatureid={$row['creatureid']}&level={$row['creaturelevel']}");
		output_notl("%s", $row['creatureid']);
		rawoutput("</td><td>");
		output_notl("%s", $row['creaturename']);
		rawoutput("</td><td>");
		output_notl("%s", $row['creaturelevel']);
		rawoutput("</td><td>");
		output_notl("%s", $row['creatureweapon']);
		rawoutput("</td><td>");
		output_notl("%s", $row['creaturewin']);
		rawoutput("</td><td>");
		output_notl("%s", $row['creaturelose']);
		rawoutput("</td><td>");
		output_notl("%s", $row['createdby']);
		rawoutput("</td></tr>");
	}
	rawoutput("</table>");
}else{
	$level = httpget('level');
	if (!$level) $level = 1;
	if ($op=="edit" || $op=="add"){
		require_once("lib/showform.php");
		addnav("Edit");
		addnav("Creature properties", "creatures.php?op=edit&creatureid=$id");
		addnav("Add");
		addnav("Add Another Creature", "creatures.php?op=add&level=$level");
		module_editor_navs("prefs-creatures", "creatures.php?op=edit&subop=module&creatureid=$id&module=");
		if ($subop == "module") {
			$module = httpget("module");
			rawoutput("<form action='creatures.php?op=save&subop=module&creatureid=$id&module=$module' method='POST'>");
			module_objpref_edit("creatures", $module, $id);
			rawoutput("</form>");
			addnav("", "creatures.php?op=save&subop=module&creatureid=$id&module=$module");
		} else {
			if ($op=="edit" && $id!=""){
				$sql = "SELECT * FROM " . db_prefix("creatures") . " WHERE creatureid=$id";
				$result = db_query($sql);
				if (db_num_rows($result)<>1){
					output("`4Error`0, that creature was not found!");
				}else{
					$row = db_fetch_assoc($result);
				}
				$level = $row['creaturelevel'];
			} else {
				$row = array("creatureid"=>0,"creaturelevel"=>$level);
			}
			$form = array(
				"Creature Properties,title",
				"creatureid"=>"Creature id,hidden",
				"creaturename"=>"Creature Name",
				"creatureweapon"=>"Weapon",
				"creaturewin"=>"Win Message (Displayed when the creature kills the player)",
				"creaturelose"=>"Death Message (Displayed when the creature is killed by the player)",
				// 18 to make a non-forest available monster
				// (ie, graveyard only)_
				"creaturelevel"=>"Level,range,1,18,1",
				"forest"=>"Creature is in forest?,bool",
				"graveyard"=>"Creature is in graveyard?,bool",
				"creatureaiscript"=>"Creature's A.I.,textarearesizeable",
			);
			rawoutput("<form action='creatures.php?op=save' method='POST'>");
			showform($form, $row);
			rawoutput("</form>");
			addnav("","creatures.php?op=save");
		}
	}else{
		$module = httpget("module");
		rawoutput("<form action='mounts.php?op=save&subop=module&creatureid=$id&module=$module' method='POST'>");
		module_objpref_edit("creatures", $module, $id);
		rawoutput("</form>");
		addnav("", "creatures.php?op=save&subop=module&creatureid=$id&module=$module");
	}
	addnav("Navigation");
	addnav("Return to the creature editor","creatures.php?level=$level");
}
page_footer();
?>