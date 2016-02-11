<?php
// translator ready
// addnews ready
// mail ready
require_once("lib/dump_item.php");

function showform($layout,$row,$nosave=false,$keypref=false){
	global $session;
 	static $showform_id=0;
 	static $title_id=0;
 	$showform_id++;
 	$formSections = array();
	$returnvalues = array();
	$extensions = array();
	$extensions = modulehook("showformextensions",$extensions);
	rawoutput("<table width='100%' cellpadding='0' cellspacing='0'><tr><td>");
	rawoutput("<div id='showFormSection$showform_id'></div>");
	rawoutput("</td></tr><tr><td>&nbsp;</td></tr><tr><td>");
	rawoutput("<table cellpadding='2' cellspacing='0'>");
	$i = 0;
	while(list($key,$val)=each($layout)){
		$pretrans = 0;
		if ($keypref !== false) $keyout = sprintf($keypref, $key);
		else $keyout = $key;
		if (is_array($val)) {
			$v = $val[0];
			$info = split(",", $v);
			$val[0] = $info[0];
			$info[0] = $val;
		} else {
			$info = split(",",$val);
		}
		if (is_array($info[0])) {
			$info[0] = call_user_func_array("sprintf_translate", $info[0]);
		} else {
			$info[0] = translate($info[0]);
		}
		if (isset($info[1])) $info[1] = trim($info[1]);
		else $info[1] = "";

		if ($info[1]=="title"){
		 	$title_id++;
		 	rawoutput("</table>");
		 	$formSections[$title_id] = $info[0];
		 	rawoutput("<table id='showFormTable$title_id' cellpadding='2' cellspacing='0'>");
			rawoutput("<tr><td colspan='2' class='trhead'>");
			output_notl("`b%s`b", $info[0], true);
			rawoutput("</td></tr>",true);
			$i=0;
		}elseif ($info[1]=="note"){
			rawoutput("<tr class='".($i%2?'trlight':'trdark')."'><td colspan='2'>");
			output_notl("`i%s`i", $info[0], true);
			$i++;
		}elseif($info[1]=="invisible"){
			// Don't show
		}else{
			if (isset($row[$key]))
				$returnvalues[$key] = $row[$key];
			rawoutput("<tr class='".($i%2?'trlight':'trdark')."'><td valign='top'>");
			output_notl("%s", $info[0],true);
			rawoutput("</td><td valign='top'>");
			$i++;
		}
		switch ($info[1]){
		case "title":
		case "note":
		case "invisible":
			break;
		case "theme":
			// A generic way of allowing a theme to be selected.
			$skins = array();
		    $handle = @opendir("templates");
			// Template directory open failed
			if (!$handle) {
				output("None available");
				break;
			}
			while (false != ($file = @readdir($handle))) {
				if (strpos($file,".htm") > 0) {
					array_push($skins, $file);
				}
			}
			// No templates installed!
			if (count($skins) == 0) {
				output("None available");
				break;
			}
			natcasesort($skins); //sort them in natural order
			rawoutput("<select name='$keyout'>");
			foreach($skins as $skin) {
				if ($skin == $row[$key]) {
					rawoutput("<option value='$skin' selected>".htmlentities(substr($skin, 0, strpos($skin, ".htm")), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
				} else {
					rawoutput("<option value='$skin'>".htmlentities(substr($skin, 0, strpos($skin, ".htm")), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
				}
			}
			rawoutput("</select>");
			break;
		case "location":
			// A generic way of allowing the location to be specified for
			// things which only want to be in one place.  There are other
			// things which would be good to do as well of course, such
			// as making sure to handle village name changes in the module
			// that cares about this or what not, but this at least gives
			// some support.
			$vloc = array();
			$vname = getsetting("villagename", LOCATION_FIELDS);
			$vloc[$vname]="village";
			$vloc['all'] = 1;
			$vloc = modulehook("validlocation", $vloc);
			unset($vloc['all']);
			reset($vloc);
			rawoutput("<select name='$keyout'>");
			foreach($vloc as $loc=>$val) {
				if ($loc == $row[$key]) {
					rawoutput("<option value='$loc' selected>".htmlentities($loc, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
				} else {
					rawoutput("<option value='$loc'>".htmlentities($loc, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
				}

			}
			rawoutput("</select>");
			break;
		case "checkpretrans":
			$pretrans = 1;
			// FALLTHROUGH
		case "checklist":
			reset($info);
			list($k,$v)=each($info);
			list($k,$v)=each($info);
			$select="";
			while (list($k,$v)=each($info)){
				$optval = $v;
				list($k,$v)=each($info);
				$optdis = $v;
				if (!$pretrans) $optdis = translate_inline($optdis);
				if (is_array($row[$key])){
					if ($row[$key][$optval]) {
						$checked=true;
					}else{
						$checked=false;
					}
				}else{
					//any other ways to represent this?
					debug("You must pass an array as the value when using a checklist.");
					$checked=false;
				}
				$select.="<input type='checkbox' name='{$keyout}[{$optval}]' value='1'".($checked==$optval?" checked":"").">&nbsp;".("$optdis")."<br>";
			}
			rawoutput($select);
			break;
		case "radiopretrans":
			$pretrans = 1;
			// FALLTHROUGH
		case "radio":
			reset($info);
			list($k,$v)=each($info);
			list($k,$v)=each($info);
			$select="";
			while (list($k,$v)=each($info)){
				$optval = $v;
				list($k,$v)=each($info);
				$optdis = $v;
				if (!$pretrans) $optdis = translate_inline($optdis);
				$select.=("<input type='radio' name='$keyout' value='$optval'".($row[$key]==$optval?" checked":"").">&nbsp;".("$optdis")."<br>");
			}
			rawoutput($select);
			break;
		case "dayrange":
			$start = strtotime(date("Y-m-d", strtotime("now")));
			$end = strtotime($info[2]);
			$step = $info[3];
			// we should really try to avoid an infinite loop here if
			// they define a time string which equates to 0 :/
			$cur = $row[$key];
			rawoutput("<select name='$keyout'>");
			if ($cur && $cur < date("Y-m-d H:i:s", $start))
				rawoutput("<option value='$cur' selected>".htmlentities($cur, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
			for($j = $start; $j < $end; $j = strtotime($step, $j)) {
				$d = date("Y-m-d H:i:s", $j);
				rawoutput("<option value='$d'".($cur==$d?" selected":"").">".HTMLEntities("$d", ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
			}
			if ($cur && $cur > date("Y-m-d H:i:s", $end))
				rawoutput("<option value='$cur' selected>".htmlentities($cur, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
			rawoutput("</select>");
			break;

		case "range":
			$min = (int)$info[2];
			$max = (int)$info[3];
			$step = (int)(isset($info[4])?$info[4]:false);
			if ($step == 0) $step = 1;
			rawoutput("<select name='$keyout'>");
			if ($min<$max && ($max-$min)/$step>300)
				$step=max(1,(int)(($max-$min)/300));
			for($j = $min; $j <= $max; $j += $step) {
				rawoutput("<option value='$j'".($row[$key]==$j?" selected":"").">".HTMLEntities("$j", ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
			}
			rawoutput("</select>");
			break;
		case "floatrange":
			$min = round((float)$info[2],2);
			$max = round((float)$info[3],2);
			$step = round((float)$info[4],2);
			if ($step==0) $step=1;
			rawoutput("<select name='$keyout'>", true);
			$val = round((float)$row[$key], 2);
			for($j = $min; $j <= $max; $j = round($j+$step,2)) {
				rawoutput("<option value='$j'".($val==$j?" selected":"").">".HTMLEntities("$j", ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>", true);
			}
			rawoutput("</select>", true);
			break;
		case "bitfieldpretrans":
			$pretrans = 1;
			// FALLTHROUGH
		case "bitfield":
			//format:
			//DisplayName,bitfield,disablemask,(highbit,display)+
			//1-26-03 added disablemask so this field type can be used
			// on bitfields other than superuser.
			reset($info);
			list($k,$v)=each($info);
			list($k,$v)=each($info);
			list($k,$disablemask)=each($info);
			rawoutput("<input type='hidden' name='$keyout"."[0]' value='1'>", true);
			while (list($k,$v)=each($info)){
				rawoutput("<input type='checkbox' name='$keyout"."[$v]'"
					.(isset($row[$key]) && (int)$row[$key] & (int)$v?" checked":"")
					.($disablemask & (int)$v?"":" disabled")
					." value='1'> ");
				list($k,$v)=each($info);
				if (!$pretrans) $v = translate_inline($v);
				output_notl("%s`n",$v,true);
			}
			break;
		case "datelength":
			// However, there was a bug with your translation code wiping
			// the key name for the actual form.  It's now fixed.
			// ok, I see that, but 24 hours and 1 day are the same
			// aren't they?
			$vals = array(
				"1 hour", "2 hours", "3 hours", "4 hours",
				"5 hours", "6 hours", "8 hours", "10 hours",
				"12 hours", "16 hours", "18 hours", "24 hours",
				"1 day", "2 days", "3 days", "4 days", "5 days",
				"6 days", "7 days",
				"1 week", "2 weeks", "3 weeks", "4 weeks",
				"1 month", "2 months", "3 months", "4 months",
				"6 months", "9 months", "12 months",
				"1 year"
			);
			tlschema("showform");
			while (list($k,$v)=each($vals)){
				$vals[$k]=translate($v);
				rawoutput(tlbutton_pop());
			}
			tlschema();
			reset($vals);
			rawoutput("<select name='$keyout'>");
			while(list($k,$v)=each($vals)) {
				rawoutput("<option value=\"".htmlentities($v, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\"".($row[$key]==$v?" selected":"").">".htmlentities($v, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
			}
			rawoutput("</select>");
			break;
		case "enumpretrans":
			$pretrans = 1;
		    // FALLTHROUGH
		case "enum":
			reset($info);
			list($k,$v)=each($info);
			list($k,$v)=each($info);
			$select="";
			$select.=("<select name='$keyout'>");
			while (list($k,$v)=each($info)){
				$optval = $v;
				list($k,$v)=each($info);
				$optdis = $v;
				if (!$pretrans) {
					$optdis = translate_inline($optdis);
				}
				$selected = 0;
				if (isset($row[$key]) && $row[$key] == $optval)
					$selected = 1;

				$select.=("<option value='$optval'".($selected?" selected":"").">".HTMLEntities("$optdis", ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</option>");
			}
			$select.="</select>";
			rawoutput($select);
			break;
		case "password":
			if (array_key_exists($key, $row)) $out = $row[$key];
			else $out = "";
			rawoutput("<input type='password' name='$keyout' value='".HTMLEntities($out, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."'>");
			break;
		case "bool":
			tlschema("showform");
			$yes = translate_inline("Yes");
			$no = translate_inline("No");
			tlschema();
			rawoutput("<select name='$keyout'>");
			rawoutput("<option value='0'".($row[$key]==0?" selected":"").">$no</option>");
			rawoutput("<option value='1'".($row[$key]==1?" selected":"").">$yes</option>");
			rawoutput("</select>", true);
			break;
		case "hidden":
			if(isset($row[$key])) rawoutput("<input type='hidden' name='$keyout' value=\"".HTMLEntities($row[$key], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">".HTMLEntities($row[$key], ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
			break;
		case "viewonly":
			unset($returnvalues[$key]);
			if (isset($row[$key]))
				output_notl(dump_item($row[$key]),true);
			break;
		case "textarearesizeable":
			$resize=true;
			//FALLTHROUGH
		case "textarea":
			$cols = 0;
			if (isset($info[2])) $cols = $info[2];
		    if (!$cols) $cols = 70;
			$text = "";
			if (isset($row[$key])) {
				$text = $row[$key];
			}
			if (isset($resize) && $resize) {
				rawoutput("<script type=\"text/javascript\">function increase(target, value){  if (target.rows + value > 3 && target.rows + value < 50) target.rows = target.rows + value;}</script>");
				rawoutput("<textarea id='textarea$key' class='input' name='$keyout' cols='$cols' rows='5'>".htmlentities(str_replace("`n", "\n", $text), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea>");
				rawoutput("<input type='button' onClick=\"increase(textarea$key,1);\" value='+' accesskey='+'><input type='button' onClick=\"increase(textarea$key,-1);\" value='-' accesskey='-'>");
			} else {
				rawoutput("<textarea class='input' name='$keyout' cols='$cols' rows='5'>".htmlentities(str_replace("`n", "\n", $text), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea>");
			}
			break;
		case "int":
			if (array_key_exists($key, $row)) $out = $row[$key];
			else $out = 0;
			rawoutput("<input name='$keyout' value=\"".HTMLEntities($out, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" size='5'>");
			break;
		case "float":
			rawoutput("<input name='$keyout' value=\"".htmlentities($row[$key], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" size='8'>");
			break;
		case "string":
			$len = 50;
			$minlen = 50;
			if (isset($info[2])) $len = (int)$info[2];
			if ($len < $minlen) $minlen = $len;
			if ($len > $minlen) $minlen = $len/2;
			if ($minlen > 70) $minlen = 70;
			if (array_key_exists($key, $row)) $val = $row[$key];
			else $val = "";
			rawoutput("<input size='$minlen' maxlength='$len' name='$keyout' value=\"".HTMLEntities($val, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">");
			break;
		default:
			if (array_key_exists($info[1],$extensions)){
				$func=$extensions[$info[1]];
				if (array_key_exists($key, $row)) $val = $row[$key];
				else $val = "";
				call_user_func($func, $keyout, $val, $info);
			}else{
				if (array_key_exists($key, $row)) $val = $row[$key];
				else $val = "";
				rawoutput("<input size='50' name='$keyout' value=\"".HTMLEntities($val, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">");
			}
		}
		rawoutput("</td></tr>",true);
	}
	rawoutput("</table><br>",true);
	if ($showform_id==1){
		$startIndex = (int)httppost("showFormTabIndex");
		if ($startIndex == 0){
			$startIndex = 1;
		}
		if (isset($session['user']['prefs']['tabconfig']) &&
				$session['user']['prefs']['tabconfig'] == 0) {
		} else {
		 	rawoutput("
		 	<script language='JavaScript'>
		 	function prepare_form(id){
		 		var theTable;
		 		var theDivs='';
		 		var x=0;
		 		var weight='';
		 		for (x in formSections[id]){
		 			theTable = document.getElementById('showFormTable'+x);
		 			if (x != $startIndex ){
			 			theTable.style.visibility='hidden';
			 			theTable.style.display='none';
			 			weight='';
			 		}else{
			 			theTable.style.visibility='visible';
			 			theTable.style.display='inline';
			 			weight='color: yellow;';
			 		}
			 		theDivs += \"<div id='showFormButton\"+x+\"' class='trhead' style='\"+weight+\"float: left; cursor: pointer; cursor: hand; padding: 5px; border: 1px solid #000000;' onClick='showFormTabClick(\"+id+\",\"+x+\");'>\"+formSections[id][x]+\"</div>\";
		 		}
		 		theDivs += \"<div style='display: block;'>&nbsp;</div>\";
				theDivs += \"<input type='hidden' name='showFormTabIndex' value='$startIndex' id='showFormTabIndex'>\";
		 		document.getElementById('showFormSection'+id).innerHTML = theDivs;
		 	}
		 	function showFormTabClick(formid,sectionid){
		 		var theTable;
		 		var theButton;
		 		for (x in formSections[formid]){
		 			theTable = document.getElementById('showFormTable'+x);
		 			theButton = document.getElementById('showFormButton'+x);
		 			if (x == sectionid){
		 				theTable.style.visibility='visible';
		 				theTable.style.display='inline';
		 				theButton.style.fontWeight='normal';
		 				theButton.style.color='yellow';
						document.getElementById('showFormTabIndex').value = sectionid;
		 			}else{
		 				theTable.style.visibility='hidden';
		 				theTable.style.display='none';
		 				theButton.style.fontWeight='normal';
		 				theButton.style.color='';
		 			}
		 		}
		 	}
		 	formSections = new Array();
			</script>");
		}
	}
	if (isset($session['user']['prefs']['tabconfig']) &&
			$session['user']['prefs']['tabconfig'] == 0) {
	} else {
		rawoutput("<script language='JavaScript'>");
		rawoutput("formSections[$showform_id] = new Array();");
		reset($formSections);
		while (list($key,$val)=each($formSections)){
			rawoutput("formSections[$showform_id][$key] = '".addslashes($val)."';");
		}
		rawoutput("
		prepare_form($showform_id);
		</script>");
	}
	rawoutput("</td></tr></table>");
	tlschema("showform");
	$save = translate_inline("Save");
	tlschema();
	if ($nosave) {}
	else rawoutput("<input type='submit' class='button' value='$save'>");
	return $returnvalues;
}
?>
