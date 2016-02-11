<?php
// addnews ready
// mail ready
// translator ready

// hilarious copy of mounts.php
require_once("common.php");
require_once("lib/http.php");
require_once("lib/showform.php");

check_su_access(SU_EDIT_MOUNTS);

tlschema("companions");

page_header("Companion Editor");

require_once("lib/superusernav.php");
superusernav();

addnav("Companion Editor");
addnav("Add a companion","companions.php?op=add");

$op = httpget('op');
$id = httpget('id');
if ($op=="deactivate"){
	$sql = "UPDATE " . db_prefix("companions") . " SET companionactive=0 WHERE companionid='$id'";
	db_query($sql);
	$op="";
	httpset("op", "");
	invalidatedatacache("companionsdata-$id");
} elseif ($op=="activate"){
	$sql = "UPDATE " . db_prefix("companions") . " SET companionactive=1 WHERE companionid='$id'";
	db_query($sql);
	$op="";
	httpset("op", "");
	invalidatedatacache("companiondata-$id");
} elseif ($op=="del") {
	//drop the companion.
	$sql = "DELETE FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	db_query($sql);
	module_delete_objprefs('companions', $id);
	$op = "";
	httpset("op", "");
	invalidatedatacache("companiondata-$id");
} elseif ($op=="take"){
	$sql = "SELECT * FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	$result = db_query($sql);
	if ($row = db_fetch_assoc($result)) {
		$row['attack'] = $row['attack'] + $row['attackperlevel'] * $session['user']['level'];
		$row['defense'] = $row['defense'] + $row['defenseperlevel'] * $session['user']['level'];
		$row['maxhitpoints'] = $row['maxhitpoints'] + $row['maxhitpointsperlevel'] * $session['user']['level'];
		$row['hitpoints'] = $row['maxhitpoints'];
		$row = modulehook("alter-companion", $row);
		$row['abilities'] = @unserialize($row['abilities']);
		require_once("lib/buffs.php");
		apply_companion($row['name'], $row);
		output("`\$Succesfully taken `^%s`\$ as companion.", $row['name']);
	}
	$op = "";
	httpset("op", "");
} elseif ($op=="save"){
	$subop = httpget("subop");
	if ($subop == "") {
		$companion = httppost('companion');
		if ($companion) {
			if (!isset($companion['allowinshades'])) {
				$companion['allowinshades'] = 0;
			}
			if (!isset($companion['allowinpvp'])) {
				$companion['allowinpvp'] = 0;
			}
			if (!isset($companion['allowintrain'])) {
				$companion['allowintrain'] = 0;
			}
			if (!isset($companion['abilities']['fight'])) {
				$companion['abilities']['fight'] = false;
			}
			if (!isset($companion['abilities']['defend'])) {
				$companion['abilities']['defend'] = false;
			}
			if (!isset($companion['cannotdie'])) {
				$companion['cannotdie'] = false;
			}
			if (!isset($companion['cannotbehealed'])) {
				$companion['cannotbehealed'] = false;
			}
			$sql = "";
			$keys = "";
			$vals = "";
			$i = 0;
			while(list($key, $val) = each($companion)) {
				if (is_array($val)) $val = addslashes(serialize($val));
				$sql .= (($i > 0) ? ", " : "") . "$key='$val'";
				$keys .= (($i > 0) ? ", " : "") . "$key";
				$vals .= (($i > 0) ? ", " : "") . "'$val'";
				$i++;
			}
			if ($id>""){
				$sql="UPDATE " . db_prefix("companions") .
					" SET $sql WHERE companionid='$id'";
			}else{
				$sql="INSERT INTO " . db_prefix("companions") .
					" ($keys) VALUES ($vals)";
			}
			db_query($sql);
			invalidatedatacache("companiondata-$id");
			if (db_affected_rows()>0){
				output("`^Companion saved!`0`n`n");
			}else{
//				if (strlen($sql) > 400) $sql = substr($sql,0,200)." ... ".substr($sql,strlen($sql)-200);
				output("`^Companion `\$not`^ saved: `\$%s`0`n`n", $sql);
			}
		}
	} elseif ($subop=="module") {
		// Save modules settings
		$module = httpget("module");
		$post = httpallpost();
		reset($post);
		while(list($key, $val) = each($post)) {
			set_module_objpref("companions", $id, $key, $val, $module);
		}
		output("`^Saved!`0`n");
	}
	if ($id) {
		$op="edit";
	} else {
		$op = "";
	}
	httpset("op", $op);
}

if ($op==""){
	$sql = "SELECT * FROM " . db_prefix("companions") . " ORDER BY category, name";
	$result = db_query($sql);

	$ops = translate_inline("Ops");
	$name = translate_inline("Name");
	$cost = translate_inline("Cost");

	$edit = translate_inline("Edit");
	$del = translate_inline("Del");
	$take = translate_inline("Take");
	$deac = translate_inline("Deactivate");
	$act = translate_inline("Activate");

	rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
	rawoutput("<tr class='trhead'><td nowrap>$ops</td><td>$name</td><td>$cost</td></tr>");
	$cat = "";
	$count=0;

	while ($row=db_fetch_assoc($result)) {
		if ($cat!=$row['category']){
			rawoutput("<tr class='trlight'><td colspan='5'>");
			output("Category: %s", $row['category']);
			rawoutput("</td></tr>");
			$cat = $row['category'];
			$count=0;
		}
		if (isset($companions[$row['companionid']])) {
			$companions[$row['companionid']] = (int)$companions[$row['companionid']];
		} else {
			$companions[$row['companionid']] = 0;
		}
		rawoutput("<tr class='".($count%2?"trlight":"trdark")."'>");
		rawoutput("<td nowrap>[ <a href='companions.php?op=edit&id={$row['companionid']}'>$edit</a> |");
		addnav("","companions.php?op=edit&id={$row['companionid']}");
		if ($row['companionactive']){
			rawoutput("$del |");
		}else{
			$mconf = sprintf($conf, $companions[$row['companionid']]);
			rawoutput("<a href='companions.php?op=del&id={$row['companionid']}'>$del</a> |");
			addnav("","companions.php?op=del&id={$row['companionid']}");
		}
		if ($row['companionactive']) {
			rawoutput("<a href='companions.php?op=deactivate&id={$row['companionid']}'>$deac</a> | ");
			addnav("","companions.php?op=deactivate&id={$row['companionid']}");
		}else{
			rawoutput("<a href='companions.php?op=activate&id={$row['companionid']}'>$act</a> | ");
			addnav("","companions.php?op=activate&id={$row['companionid']}");
		}
		rawoutput("<a href='companions.php?op=take&id={$row['companionid']}'>$take</a> ]</td>");
		addnav("", "companions.php?op=take&id={$row['companionid']}");
		rawoutput("<td>");
		output_notl("`&%s`0", $row['name']);
		rawoutput("</td><td>");
		output("`%%s gems`0, `^%s gold`0",$row['companioncostgems'], $row['companioncostgold']);
		rawoutput("</td></tr>");
		$count++;
	}
	rawoutput("</table>");
	output("`nIf you wish to delete a companion, you have to deactivate it first.");
}elseif ($op=="add"){
	output("Add a companion:`n");
	addnav("Companion Editor Home","companions.php");
	companionform(array());
}elseif ($op=="edit"){
	addnav("Companion Editor Home","companions.php");
	$sql = "SELECT * FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	$result = db_query_cached($sql, "companiondata-$id", 3600);
	if (db_num_rows($result)<=0){
		output("`iThis companion was not found.`i");
	}else{
		addnav("Companion properties", "companions.php?op=edit&id=$id");
		module_editor_navs("prefs-companions", "companions.php?op=edit&subop=module&id=$id&module=");
		$subop=httpget("subop");
		if ($subop=="module") {
			$module = httpget("module");
			rawoutput("<form action='companions.php?op=save&subop=module&id=$id&module=$module' method='POST'>");
			module_objpref_edit("companions", $module, $id);
			rawoutput("</form>");
			addnav("", "companions.php?op=save&subop=module&id=$id&module=$module");
		} else {
			output("Companion Editor:`n");
			$row = db_fetch_assoc($result);
			$row['abilities'] = @unserialize($row['abilities']);
			companionform($row);
		}
	}
}

function companionform($companion){
	// Let's sanitize the data
	if (!isset($companion['companionactive'])) $companion['companionactive'] = "";
	if (!isset($companion['name'])) $companion['name'] = "";
	if (!isset($companion['companionid'])) $companion['companionid'] = "";
	if (!isset($companion['description'])) $companion['description'] = "";
	if (!isset($companion['dyingtext'])) $companion['dyingtext'] = "";
	if (!isset($companion['jointext'])) $companion['jointext'] = "";
	if (!isset($companion['category'])) $companion['category'] = "";
	if (!isset($companion['companionlocation'])) $companion['companionlocation']  = 'all';
	if (!isset($companion['companioncostdks'])) $companion['companioncostdks']  = 0;

	if (!isset($companion['companioncostgems'])) $companion['companioncostgems']  = 0;
	if (!isset($companion['companioncostgold'])) $companion['companioncostgold']  = 0;

	if (!isset($companion['attack'])) $companion['attack'] = "";
	if (!isset($companion['attackperlevel'])) $companion['attackperlevel'] = "";
	if (!isset($companion['defense'])) $companion['defense'] = "";
	if (!isset($companion['defenseperlevel'])) $companion['defenseperlevel'] = "";
	if (!isset($companion['hitpoints'])) $companion['hitpoints'] = "";
	if (!isset($companion['maxhitpoints'])) $companion['maxhitpoints'] = "";
	if (!isset($companion['maxhitpointsperlevel'])) $companion['maxhitpointsperlevel'] = "";

	if (!isset($companion['abilities']['fight'])) $companion['abilities']['fight'] = 0;
	if (!isset($companion['abilities']['defend'])) $companion['abilities']['defend'] =  0;
	if (!isset($companion['abilities']['heal'])) $companion['abilities']['heal'] =  0;
	if (!isset($companion['abilities']['magic'])) $companion['abilities']['magic'] =  0;

	if (!isset($companion['cannotdie'])) $companion['cannotdie'] = 0;
	if (!isset($companion['cannotbehealed'])) $companion['cannotbehealed'] = 1;
	if (!isset($companion['allowinshades'])) $companion['allowinshades'] = 0;
	if (!isset($companion['allowinpvp'])) $companion['allowinpvp'] = 0;
	if (!isset($companion['allowintrain'])) $companion['allowintrain'] = 0;

	rawoutput("<form action='companions.php?op=save&id={$companion['companionid']}' method='POST'>");
	rawoutput("<input type='hidden' name='companion[companionactive]' value=\"".$companion['companionactive']."\">");
	addnav("","companions.php?op=save&id={$companion['companionid']}");
	rawoutput("<table width='100%'>");
	rawoutput("<tr><td nowrap>");
	output("Companion Name:");
	rawoutput("</td><td><input name='companion[name]' value=\"".htmlentities($companion['name'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" maxlength='50'></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Companion Dyingtext:");
	rawoutput("</td><td><input name='companion[dyingtext]' value=\"".htmlentities($companion['dyingtext'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Companion Description:");
	rawoutput("</td><td><textarea cols='25' rows='5' name='companion[description]'>".htmlentities($companion['description'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Companion join text:");
	rawoutput("</td><td><textarea cols='25' rows='5' name='companion[jointext]'>".htmlentities($companion['jointext'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Companion Category:");
	rawoutput("</td><td><input name='companion[category]' value=\"".htmlentities($companion['category'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" maxlength='50'></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Companion Availability:");
	rawoutput("</td><td nowrap>");
	// Run a modulehook to find out where camps are located.  By default
	// they are located in 'Degolburg' (ie, getgamesetting('villagename'));
	// Some later module can remove them however.
	$vname = getsetting('villagename', LOCATION_FIELDS);
	$locs = array($vname => sprintf_translate("The Village of %s", $vname));
	$locs = modulehook("camplocs", $locs);
	$locs['all'] = translate_inline("Everywhere");
	ksort($locs);
	reset($locs);
	rawoutput("<select name='companion[companionlocation]'>");
	foreach($locs as $loc=>$name) {
		rawoutput("<option value='$loc'".($companion['companionlocation']==$loc?" selected":"").">$name</option>");
	}

	rawoutput("<tr><td nowrap>");
	output("Maxhitpoints / Bonus per level:");
	rawoutput("</td><td><input name='companion[maxhitpoints]' value=\"".htmlentities($companion['maxhitpoints'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[maxhitpointsperlevel]' value=\"".htmlentities($companion['maxhitpointsperlevel'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Attack / Bonus per level:");
	rawoutput("</td><td><input name='companion[attack]' value=\"".htmlentities($companion['attack'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[attackperlevel]' value=\"".htmlentities($companion['attackperlevel'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Defense / Bonus per level:");
	rawoutput("</td><td><input name='companion[defense]' value=\"".htmlentities($companion['defense'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[defenseperlevel]' value=\"".htmlentities($companion['defenseperlevel'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"></td></tr>");

	rawoutput("<tr><td nowrap>");
	output("Fighter?:");
	rawoutput("</td><td><input id='fighter' type='checkbox' name='companion[abilities][fight]' value='1'".($companion['abilities']['fight']==true?" checked":"")." onClick='document.getElementById(\"defender\").disabled=document.getElementById(\"fighter\").checked; if(document.getElementById(\"defender\").disabled==true) document.getElementById(\"defender\").checked=false;'></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Defender?:");
	rawoutput("</td><td><input id='defender' type='checkbox' name='companion[abilities][defend]' value='1'".($companion['abilities']['defend']==true?" checked":"")." onClick='document.getElementById(\"fighter\").disabled=document.getElementById(\"defender\").checked; if(document.getElementById(\"fighter\").disabled==true) document.getElementById(\"fighter\").checked=false;'></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Healer level:");
	rawoutput("</td><td valign='top'><select name='companion[abilities][heal]'>");
	for($i=0;$i<=30;$i++) {
		rawoutput("<option value='$i'".($companion['abilities']['heal']==$i?" selected":"").">$i</option>");
	}
	rawoutput("</select></td></tr>");
	rawoutput("<tr><td colspan='2'>");
	output("`iThis value determines the maximum amount of HP healed per round`i");
	rawoutput("</td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Magician?:");
	rawoutput("</td><td valign='top'><select name='companion[abilities][magic]'>");
	for($i=0;$i<=30;$i++) {
		rawoutput("<option value='$i'".($companion['abilities']['magic']==$i?" selected":"").">$i</option>");
	}
	rawoutput("</select></td></tr>");
	rawoutput("<tr><td colspan='2'>");
	output("`iThis value determines the maximum amount of damage caused per round`i");
	rawoutput("</td></tr>");

	rawoutput("<tr><td nowrap>");
	output("Companion cannot die:");
	rawoutput("</td><td><input type='checkbox' name='companion[cannotdie]' value='1'".($companion['cannotdie']==true?" checked":"")."></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Companion cannot be healed:");
	rawoutput("</td><td><input type='checkbox' name='companion[cannotbehealed]' value='1'".($companion['cannotbehealed']==true?" checked":"")."></td></tr>");
	rawoutput("<tr><td nowrap>");

	output("Companion Cost (DKs):");
	rawoutput("</td><td><input name='companion[companioncostdks]' value=\"".htmlentities((int)$companion['companioncostdks'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Companion Cost (Gems):");
	rawoutput("</td><td><input name='companion[companioncostgems]' value=\"".htmlentities((int)$companion['companioncostgems'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Companion Cost (Gold):");
	rawoutput("</td><td><input name='companion[companioncostgold]' value=\"".htmlentities((int)$companion['companioncostgold'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Allow in shades:");
	rawoutput("</td><td><input type='checkbox' name='companion[allowinshades]' value='1'".($companion['allowinshades']==true?" checked":"")."></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Allow in PvP:");
	rawoutput("</td><td><input type='checkbox' name='companion[allowinpvp]' value='1'".($companion['allowinpvp']==true?" checked":"")."></td></tr>");
	rawoutput("<tr><td nowrap>");
	output("Allow in train:");
	rawoutput("</td><td><input type='checkbox' name='companion[allowintrain]' value='1'".($companion['allowintrain']==true?" checked":"")."></td></tr>");
	rawoutput("</table>");
	$save = translate_inline("Save");
	rawoutput("<input type='submit' class='button' value='$save'></form>");
}

page_footer();
?>
