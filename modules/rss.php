<?php
// addnews ready
// mail ready
// translator ready

// ver 1.0 by Eric Stevens
// Original release

// ver 1.1 by Catscradler
// added explanation page
// moved feed links to the explanation page (people without readers didn't know why they were getting pages of RSS code)

function rss_getmoduleinfo(){
	$info = array(
		"name"=>"RSS News Feeds",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"General",
		"download"=>"core_module",
		"allowanonymous"=>true,
		"override_forced_nav"=>true,
		"settings"=>array(
			"Feeds,title",
			"do_news"=>"RSS feed for daily news?,bool|1",
			"do_online"=>"RSS feed for who's online?,bool|1",
			"do_motd"=>"RSS feed for MoTD's?,bool|1",

			"Visibility,title",
			"show_on_about"=>"Show RSS feeds on the About page,bool|1",
			"include_page_meta"=>"Link RSS feeds in the page meta data,bool|1",
			"This enables browsers like Firefox to attach to the feeds automatically.,note",

			"Timeout,title",
			"cache_timeout"=>"Update RSS feeds how often (minutes)?,int|10",
			"ttl"=>"Feed Time to Live (how often should RSS clients update the feed in minutes)?,int|60",
		),
	);
	return $info;
}

function rss_install(){
	module_addhook("everyfooter");
	module_addhook("about");
	return true;
}
function rss_uninstall(){
	return true;
}
function rss_dohook($hookname,$args){
	switch($hookname){
	case "everyfooter":
		if (get_module_setting("include_page_meta")){
			if (!isset($args['headscript'])) $args['headscript'] = array();
			if (!is_array($args['headscript'])) $args['headscript'] = array($args['headscript']);
			if (get_module_setting("do_news")){
				array_push($args['headscript'],"<LINK REL=\"alternate\" TITLE=\"Daily News\" TYPE=\"application/rss+xml\" HREF=\"runmodule.php?module=rss&feed=news\">");
			}
			if (get_module_setting("do_online")){
				array_push($args['headscript'],"<LINK REL=\"alternate\" TITLE=\"Who's Online\" TYPE=\"application/rss+xml\" HREF=\"runmodule.php?module=rss&feed=online\">");
			}
			if (get_module_setting("do_motd")){
				array_push($args['headscript'],"<LINK REL=\"alternate\" TITLE=\"MoTD\" TYPE=\"application/rss+xml\" HREF=\"runmodule.php?module=rss&feed=motd\">");
			}
		}
		break;
	case "about":
		if (get_module_setting("show_on_about")){
			addnav("RSS News Feeds","runmodule.php?module=rss&op=describe");
		}
		break;
	}
	return $args;
}
function rss_run(){

	if (httpget("op")=="describe"){
		global $session;
		page_header("RSS Feed Information");
		output("This site offers RSS news feeds for periodically updated information about various aspects of the game.");
		output("Click %shere%s for more information about the RSS format.`n`n","<a href='http://www.google.com/search?q=rss+information' target='_blank'>", "</a>", true);

		output("Feeds offered on this site:`n");
		$format="`#&#149;`7 %s`n";
		addnav("Get RSS News Feeds");
		if (get_module_setting("do_news")){
			addnav("Daily News","runmodule.php?module=rss&feed=news",false,true);
			output($format,"Daily News",true);
		}
		if (get_module_setting("do_online")){
			addnav("Who's Online","runmodule.php?module=rss&feed=online",false,true);
			output($format,"Who's Online",true);
		}
		if (get_module_setting("do_motd")){
			addnav("MoTD","runmodule.php?module=rss&feed=motd",false,true);
			output($format,"Message of the Day (MoTD)",true);
		}

		addnav("Other");
		addnav("About LoGD","about.php");
		if ($session['user']['loggedin']) {
		    addnav("Return to the news","news.php");
		}else{
		    addnav("Login Page","index.php");
		}
		page_footer();
		return;
	}

	$items = array();
	$feedtitle = "";
	$pubtime = date("Y-m-d H:i:s");

	$link = getsetting("serverurl",
			"http://".$_SERVER['SERVER_NAME'] .
			($_SERVER['SERVER_PORT']==80?"":":".$_SERVER['SERVER_PORT']) .
			dirname($_SERVER['SCRIPT_NAME']));
	if (!preg_match("/\\/$/", $link)) {
		$link = $link . "/";
		savesetting("serverurl", $link);
	}

	$feed = httpget("feed");

	//filter out turned-off feeds
	if ($feed=="news" && !get_module_setting("do_news")) $feed="";
	if ($feed=="motd" && !get_module_setting("do_motd")) $feed="";
	if ($feed=="online" && !get_module_setting("do_online")) $feed="";

	switch($feed){
	case "news":
		$feedtitle = "LoGD News";
		$sql = "SELECT newstext,arguments,newsdate,now() AS currenttime,tlschema FROM " . db_prefix("news") . " ORDER BY newsid DESC LIMIT 10";
		$result = db_query_cached($sql,"mod_rss_news",get_module_setting("cache_timeout"));
		while ($row = db_fetch_assoc($result)){
			$pubtime = $row['currenttime'];
			$arguments = array();
			$base_arguments = @unserialize($row['arguments']);
			array_push($arguments,$row['newstext']);
			while ($base_arguments && list($key,$val)=each($base_arguments)){
				array_push($arguments,$val);
			}
			tlschema($row['tlschema']);
			$title = call_user_func_array("sprintf_translate",$arguments);
			tlschema();
			array_push(
				$items,
				array(
					"title"=>$title,
					"description"=>$title,
					"pubDate"=>$row['newsdate'],
					"link"=>$link."news.php",
				)
			);
		}
		if (count($items)==0){
			$items = array(array("title"=>sprintf_translate("There are no news items",array()),"description"=>"","pubDate"=>date("Y-m-d H:i:s"),"link"=>$link."news.php"));
		}
		break;
	case "online":
		$feedtitle = "LoGD Who's Online";
		$sql="SELECT name,alive,location,sex,level,race,now() AS currenttime FROM " . db_prefix("accounts") . " WHERE locked=0 AND loggedin=1 AND laston>'".date("Y-m-d H:i:s",strtotime("-".getsetting("LOGINTIMEOUT",900)." seconds"))."' ORDER BY level DESC";
		$result = db_query_cached($sql,"mod_rss_online",get_module_setting("cache_timeout"));
		while ($row = db_fetch_assoc($result)){
			$pubtime = $row['currenttime'];
			array_push(
				$items,
				array(
					"title"=>$row['name'],
					"description"=>sprintf_translate("Level %s ".($row['sex']?"female":"male")." %s in %s",$row['level'],strtolower($row['race']),$row['location']),
					"pubDate"=>$row['currenttime'],
					"link"=>$link,
				)
			);
		}

		if (count($items)==0){
			$items = array(array("title"=>sprintf_translate("There are no characters online at this time.",array()),"description"=>"","pubDate"=>date("Y-m-d H:i:s"),"link"=>$link));
		}

		db_free_result($result);
		if ($onlinecount==0) $ret.=appoencode("`iNone`i");

		break;
	case "motd":
		$sql = "SELECT motddate,motdbody,motdtitle,name AS motdauthorname,now() AS currenttime FROM " . db_prefix("motd") . " LEFT JOIN " . db_prefix("accounts") . " ON " . db_prefix("accounts") . ".acctid = " . db_prefix("motd") . ".motdauthor ORDER BY motddate DESC limit 10";
		$result = db_query_cached($sql,"mod_rss_motd",get_module_setting("cache_timeout"));
		$feedtitle = "Message of the Day";
		while ($row = db_fetch_assoc($result)){
			$pubdate = $row['currenttime'];
			array_push(
				$items,
				array(
					"title"=>$row['motdtitle'],
					"description"=>"By {$row['motdauthorname']}\n{$row['motdbody']}",
					"pubDate"=>$row['motddate'],
					"link"=>$link."motd.php#motd".date("YmdHis",strtotime($row['motddate'])),
				)
			);

		}
		break;
	default:
		$feedtitle = "No such feed exists";
		$items = array(
			array(
				"title"=>"You have requested a news feed that does not exist.",
				"desription"=>"The news feed you requested does not exist.  This could be because you are using an outdated feed.  Please visit the site at $link to try again."),
				"pubDate"=>date("Y-m-d H:i:s"),
				"link"=>$link,
		);
	}

	//build the RSS feed.
	header("Content-Type: text/xml",true);
	echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>";
	echo "<rss version=\"2.0\">";
	echo "<channel>";
	echo "<title>".rss_xmlencode($feedtitle)."</title>";
	echo "<link>".rss_xmlencode($link)."</link>";
	echo "<description>Legend of the Green Dragon -- a browser based text roleplaying game based loosely on the old BBS game Legend of the Red Dragon (LoRD)</description>";
	echo "<language>en-us</language>";
	echo "<copyright>Copyright 2002-".date("Y").", Eric Stevens &amp; JT Traub</copyright>";
	echo "<pubDate>".date("r",strtotime($pubdate))."</pubDate>";
	echo "<lastBuildDate>".date("r",strtotime($pubdate))."</lastBuildDate>";
	echo "<category>LotGD: ".rss_xmlencode($feedtitle)."</category>";
	echo "<ttl>".get_module_setting("ttl")."</ttl>";
	echo "<image>";
	echo "<title>Legend of the Green Dragon</title>";
	echo "<url>".rss_xmlencode($link)."images/title.gif</url>";
	echo "<link>".rss_xmlencode($link)."</link>";
	echo "</image>";
	reset($items);
	while (list($key,$val)=each($items)){
		echo "<item>";
		echo "<title>".rss_xmlencode($val['title'])."</title>";
		echo "<link>".rss_xmlencode($val['link'])."</link>";
		echo "<guid isPermaLink=\"false\">".rss_xmlencode($val['link'])."</guid>";
		echo "<description>".rss_xmlencode($val['description'])."</description>";
		echo "<pubDate>".date("r",strtotime($val['pubDate']))."</pubDate>";
		echo "</item>";
	}
	echo "</channel>";
	echo "</rss>";
	exit();
}
function rss_xmlencode($input){
	require_once("lib/sanitize.php");
	return str_replace(array("&","<",">"),array("&amp;","&lt;","&gt;"),full_sanitize($input));
}
?>
