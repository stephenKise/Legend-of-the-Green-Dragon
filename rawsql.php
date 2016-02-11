<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/http.php");

tlschema("rawsql");

check_su_access(SU_RAW_SQL);

page_header("Raw SQL/PHP execution");
require_once("lib/superusernav.php");
superusernav();
addnav("Execution");
addnav("SQL","rawsql.php");
addnav("PHP","rawsql.php?op=php");

$op = httpget("op");
if ($op=="" || $op=="sql"){
	$sql = httppost('sql');
	if ($sql != "") {
		$sql = stripslashes($sql);
		modulehook("rawsql-execsql",array("sql"=>$sql));
		$r = db_query($sql, false);
		debuglog('Ran Raw SQL: ' . $sql);
		if (!$r) {
			output("`\$SQL Error:`& %s`0`n`n",db_error($r));
		} else {
			if (db_affected_rows() > 0) {
				output("`&%s rows affected.`n`n",db_affected_rows());
			}
			rawoutput("<table cellspacing='1' cellpadding='2' border='0' bgcolor='#999999'>");
			$number = db_num_rows($r);
			for ($i = 0; $i < $number; $i++) {
				$row = db_fetch_assoc($r);
				if ($i == 0) {
					rawoutput("<tr class='trhead'>");
					$keys = array_keys($row);
					foreach ($keys as $value) {
						rawoutput("<td>$value</td>");
					}
					rawoutput("</tr>");
				}
				rawoutput("<tr class='".($i%2==0?"trlight":"trdark")."'>");
				foreach ($keys as $value) {
					rawoutput("<td valign='top'>{$row[$value]}</td>");
				}
				rawoutput("</tr>");
			}
			rawoutput("</table>");
		}
	}

	output("Type your query");
	$execute = translate_inline("Execute");
	$ret = modulehook("rawsql-modsql",array("sql"=>$sql));
	$sql = $ret['sql'];
	rawoutput("<form action='rawsql.php' method='post'>");
	rawoutput("<textarea name='sql' class='input' cols='60' rows='10'>".htmlentities($sql, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br>");
	rawoutput("<input type='submit' class='button' value='$execute'>");
	rawoutput("</form>");
	addnav("", "rawsql.php");
}else{
	$php = stripslashes(httppost("php"));
	$source = translate_inline("Source:");
	$execute = translate_inline("Execute");
	if ($php>""){
		rawoutput("<div style='background-color: #FFFFFF; color: #000000; width: 100%'><b>$source</b><br>");
		rawoutput(highlight_string("<?php\n$php\n?>",true));
		rawoutput("</div>");
		output("`bResults:`b`n");
		modulehook("rawsql-execphp",array("php"=>$php));
		ob_start();
		eval($php);
		output_notl(ob_get_contents(),true);
		ob_end_clean();
		debuglog('Ran Raw PHP: ' . $php);
	}
	output("`n`nType your code:");
	$ret = modulehook("rawsql-modphp",array("php"=>$php));
	$php = $ret['php'];
	rawoutput("<form action='rawsql.php?op=php' method='post'>");
	rawoutput("&lt;?php<br><textarea name='php' class='input' cols='60' rows='10'>".htmlentities($php, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br>?&gt;<br>");
	rawoutput("<input type='submit' class='button' value='$execute'>");
	rawoutput("</form>");
	addnav("", "rawsql.php?op=php");
}
page_footer();
?>