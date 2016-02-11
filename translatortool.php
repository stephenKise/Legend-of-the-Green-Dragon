<?php
// addnews ready
// translator ready
// mail ready
define("OVERRIDE_FORCED_NAV",true);
require_once("common.php");
tlschema("translatortool");

check_su_access(SU_IS_TRANSLATOR);
$op=httpget("op");
if ($op==""){
	popup_header("Translator Tool");
	$uri = rawurldecode(httpget('u'));
	$text = stripslashes(rawurldecode(httpget('t')));
	
	$translation = translate_loadnamespace($uri);
	if (isset($translation[$text]))
		$trans = $translation[$text];
	else
		$trans = "";
	$namespace = translate_inline("Namespace:");
	$texta = translate_inline("Text:");
	$translation = translate_inline("Translation:");
	$saveclose = htmlentities(translate_inline("Save & Close"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
	$savenotclose = htmlentities(translate_inline("Save No Close"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
	rawoutput("<form action='translatortool.php?op=save' method='POST'>");
	rawoutput("$namespace <input name='uri' value=\"".htmlentities(stripslashes($uri), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" readonly><br/>");
	rawoutput("$texta<br>");
	rawoutput("<textarea name='text' cols='60' rows='5' readonly>".htmlentities($text, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br/>");
	rawoutput("$translation<br>");
	rawoutput("<textarea name='trans' cols='60' rows='5'>".htmlentities(stripslashes($trans), ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br/>");
	rawoutput("<input type='submit' value=\"$saveclose\" class='button'>");
	rawoutput("<input type='submit' value=\"$savenotclose\" class='button' name='savenotclose'>");
	rawoutput("</form>");
	popup_footer();
}elseif ($_GET['op']=='save'){
	$uri = httppost('uri');
	$text = httppost('text');
	$trans = httppost('trans');

	$page = $uri;
	if (strpos($page,"?")!==false) $page = substr($page,0,strpos($page,"?"));

	if ($trans==""){
		$sql = "DELETE ";
	}else{
		$sql = "SELECT * ";
	}
	$sql .= "
		FROM ".db_prefix("translations")."
		WHERE language='".LANGUAGE."'
			AND intext='$text'
			AND (uri='$page' OR uri='$uri')";
	if ($trans>""){
		$result = db_query($sql);
		invalidatedatacache("translations-".$uri."-".$language);
		//invalidatedatacache("translations-".$namespace."-".$language);
		if (db_num_rows($result)==0){
			$sql = "INSERT INTO ".db_prefix("translations")." (language,uri,intext,outtext,author,version) VALUES ('".LANGUAGE."','$uri','$text','$trans','{$session['user']['login']}','$logd_version ')";
			$sql1 = "DELETE FROM " . db_prefix("untranslated") .
				" WHERE intext='$text' AND language='" . LANGUAGE .
				"' AND namespace='$url'";
			db_query($sql1);
		}elseif(db_num_rows($result)==1){
			$row = db_fetch_assoc($result);
			// MySQL is case insensitive so we need to do it here.
			if ($row['intext'] == $text){
				$sql = "UPDATE ".db_prefix("translations")." SET author='{$session['user']['login']}', version='$logd_version', uri='$uri', outtext='$trans' WHERE tid={$row['tid']}";
			}else{
				$sql = "INSERT INTO " . db_prefix("translations") . " (language,uri,intext,outtext,author,version) VALUES ('" . LANGUAGE . "','$uri','$text','$trans','{$session['user']['login']}','$logd_version ')";
				$sql1 = "DELETE FROM " . db_prefix("untranslated") . " WHERE intext='$text' AND language='" . LANGUAGE . "' AND namespace='$url'";
				db_query($sql1);
			}
		}elseif(db_num_rows($result)>1){
			$rows = array();
			while ($row = db_fetch_assoc($result)){
				// MySQL is case insensitive so we need to do it here.
				if ($row['intext'] == $text){
					$rows['tid']=$row['tid'];
				}
			}
			$sql = "UPDATE ".db_prefix("translations")." SET author='{$session['user']['login']}', version='$logd_version', uri='$page', outtext='$trans' WHERE tid IN (".join(",",$rows).")";
		}
	}
	db_query($sql);
	if (httppost("savenotclose")>""){
		header("Location: translatortool.php?op=list&u=$page");
		exit();
	}else{
		popup_header("Updated");
		rawoutput("<script language='javascript'>window.close();</script>");
		popup_footer();
	}
}elseif($op=="list"){
	popup_header("Translation List");
	$sql = "SELECT uri,count(*) AS c FROM " . db_prefix("translations") . " WHERE language='".LANGUAGE."' GROUP BY uri ORDER BY uri ASC";
	$result = db_query($sql);
	rawoutput("<form action='translatortool.php' method='GET'>");
	rawoutput("<input type='hidden' name='op' value='list'>");
	output("Known Namespaces:");
	rawoutput("<select name='u'>");
	while ($row = db_fetch_assoc($result)){
		rawoutput("<option value=\"".rawurlencode(htmlentities($row['uri'], ENT_COMPAT, getsetting("charset", "ISO-8859-1")))."\">".htmlentities($row['uri'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))." ({$row['c']})</option>",true);
	}
	rawoutput("</select>");
	$show = translate_inline("Show");
	rawoutput("<input type='submit' class='button' value=\"$show\">");
	rawoutput("</form>");
	$ops = translate_inline("Ops");
	$from = translate_inline("From");
	$to = translate_inline("To");
	$version = translate_inline("Version");
	$author = translate_inline("Author");
	$norows = translate_inline("No rows found");
	rawoutput("<table border='0' cellpadding='2' cellspacing='0'>");
	rawoutput("<tr class='trhead'><td>$ops</td><td>$from</td><td>$to</td><td>$version</td><td>$author</td></tr>");
	$sql = "SELECT * FROM " . db_prefix("translations") . " WHERE language='".LANGUAGE."' AND uri='".httpget("u")."'";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		$i=0;
		while ($row = db_fetch_assoc($result)){
			$i++;
			rawoutput("<tr class='".($i%2?"trlight":"trdark")."'><td>");
			$edit = translate_inline("Edit");
			rawoutput("<a href='translatortool.php?u=".rawurlencode(htmlentities($row['uri'], ENT_COMPAT, getsetting("charset", "ISO-8859-1")))."&t=".rawurlencode(htmlentities($row['intext']))."'>$edit</a>");
			rawoutput("</td><td>");
			rawoutput(htmlentities($row['intext'], ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
			rawoutput("</td><td>");
			rawoutput(htmlentities($row['outtext'], ENT_COMPAT, getsetting("charset", "ISO-8859-1")));
			rawoutput("</td><td>");
			rawoutput($row['version']);
			rawoutput("</td><td>");
			rawoutput($row['author']);
			rawoutput("</td></tr>");
		}
	}else{
		rawoutput("<tr><td colspan='5'>$norows</td></tr>");
	}
	rawoutput("</table>");
	popup_footer();
}
?>