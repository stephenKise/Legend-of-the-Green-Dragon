<?php
// translator ready
// addnews ready
// mail ready
// Changelog:
// 1.1	by Catscradler
//		Placed the javascript inside HTML comment blocks.  It will still parse as javascript,
//		but will never come out as raw text in the output (used to happen when page load was
//		interrupted)
//		by kickme
//		Can now disable collaspe handles on a system wide level
function collapse_getmoduleinfo(){
	$info = array(
		"name"=>"Collapsible Content Sections",
		"author"=>"Eric Stevens",
		"download"=>"core_module",
		"category"=>"General",
		"version"=>"1.1",
		"override_forced_nav"=>true,
		"prefs"=>array(
			"Collapsible Region User Prefs,title",
			"check_navs"=>"Should there be collapse handles on navs?,bool|0",
			"check_text"=>"Should there be collapse handles on text blocks?,bool|0",
		),
		"settings"=>array(
			"Collapsible Region Settings,title",
			"navs"=>"Should there be collapse handles on navs?,bool|0",
			"text"=>"Should there be collapse handles on text blocks?,bool|0",
		),
	);
	return $info;
}

function collapse_install(){
	module_addhook("collapse{");
	module_addhook("}collapse");
	module_addhook("collapse-nav{");
	module_addhook("}collapse-nav");
	module_addhook("template-collapse");
	module_addhook("checkuserpref");
	return true;
}

function collapse_uninstall(){
	return true;
}

function collapse_sectionopen($name, $title="", $returnnotdisplay=false) {
 	static $mod_collapse_counter=0;
	//take out characters that might have caused javascript problemo's.
	$name = preg_replace("/[^a-zA-Z0-9 _-]/","_",$name);
	if (get_module_pref($name)){
		$className="modCollapseHidden";
		$tool="[+]";
	}else{
		$className="modCollapse";
		$tool="[-]";
	}

	$name = htmlentities($name, ENT_QUOTES, getsetting("charset", "ISO-8859-1"));
	$title = htmlentities($title, ENT_QUOTES, getsetting("charset", "ISO-8859-1"));
	$return = "";
	$return .= "<a class='modCollapseToolbox' href='#' onClick='doCollapse(\"$mod_collapse_counter\",\"$name\"); return false;' id='modCollapseTool$mod_collapse_counter' onMouseOver=\"setTitle(this,'$mod_collapse_counter');\" title='{$title}'>$tool</a>\n";
	$return .= "<div class='$className' id='modCollapse$mod_collapse_counter'>\n";

	if (!$returnnotdisplay) {
		rawoutput($return);
	}

 	$mod_collapse_counter++;
 	return $return;
}


function collapse_sectionclose($returnnotdisplay=false) {
	$return = "</div>";
	if (!$returnnotdisplay) {
		rawoutput($return);
	}

	return $return;
}

function collapse_dohook($hookname,$args) {
 	global $session;
 	if (!$session['user']['loggedin']){
 		return $args;
	}

	if (!defined("MOD_COLLAPSE_STYLE_CLASSIC")) {
		define("MOD_COLLAPSE_STYLE_CLASSIC",2);
	}

	if (!defined("MOD_COLLAPSE_STYLE_DEFAULT")) {
		define("MOD_COLLAPSE_STYLE_DEFAULT",1);
	}

	static $templatestyle=MOD_COLLAPSE_STYLE_DEFAULT;

 	if (!defined("MOD_COLLAPSE_SENTCSS")){
 		define("MOD_COLLAPSE_SENTCSS",true);
 		rawoutput("<style type='text/css'>
 		div.modCollapse {
 			visibility: visible;
 			display: inline;
 		}
 		div.modCollapseHidden {
 			visibility: hidden;
 			display: none;
 		}
 		.modCollapseToolbox {
 			float: right;
 		}
 		.modCollapseToolbox {
 			font-family: Courier New, Courier, fixed-width;
 		}
 		</style><script language='JavaScript'><!--
 		function doCollapse(id,name){
 			var div = document.getElementById('modCollapse'+id);
 			var tool = document.getElementById('modCollapseTool'+id);
 			var setting=0;
 			if (div.className=='modCollapseHidden'){
 				tool.innerHTML = '[-]';
				//tool.style.clear = 'both';
 				div.className='modCollapse';
 			}else{
 				tool.innerHTML = '[+]';
				//tool.style.clear = 'none';
 				div.className='modCollapseHidden';
 				setting=1;
 			}
 			var url = 'runmodule.php?module=collapse&area='+escape(name)+'&value='+setting;
 			//prompt('',url);
			fetchDOMasync(url,function(){});
 		}
 		function setTitle(a,id){
 			var div = document.getElementById('modCollapse'+id);
 			if (a.title == ''){
 				a.title = div.innerHTML.replace(/<(?!br)[^>]*>/gi,'').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&').replace(/[^.,?<>;:'\"\\[\\]\\{\\}+=`~\\\\\\/ \ta-zA-Z0-9!-)_-]/g,'').replace(/<br>/gi,'\\n').substr(0,300);
 			}
 		}
		// -->
 		</script>");
 		require_once("lib/e_dom.php");
	}
	if (!array_key_exists('name',$args)) $args['name'] = 'none';
	$name = substr($args['name'],0,20);

	if (isset($args['title'])) {
		$title = "";
		if (is_array($args['title'])) {
			$title = call_user_func_array("sprintf_translate", $args['title']);
		} else {
			$title = $args['title'];
		}
		$title = substr($title,0,100);
	} else {
		if ($hookname == "collapse-nav{") $title = substr($name,0,100);
		else $title = "";
	}
	switch($hookname){
	case "checkuserpref":
		debug ($args);
		if ($args['name'] == "check_navs") {
			if (!get_module_setting("navs")) {
				$args['pref'] =
					str_replace(",bool", ",invisible", $args['pref']);
				$args['allow'] = 0;
			} else $args['allow'] = 1;
		} elseif ($args['name'] == "check_text") {
			if (!get_module_setting("text")) {
				$args['pref'] =
					str_replace(",bool", ",invisible", $args['pref']);
				$args['allow'] = 0;
			} else $args['allow'] = 1;
		}
		break;
	case "template-collapse":
		if (isset($args['content'])) {
			$parts = explode(":",$args['content']);
			if (trim($parts[0])=="style") {
				switch (trim($parts[1])) {
				case "classic":
					$templatestyle = MOD_COLLAPSE_STYLE_CLASSIC;
					break;
				default:
					$templatestyle = MOD_COLLAPSE_STYLE_DEFAULT;
					break;
				}
			}
		}
		$args['nocollapse'] = 1;
		break;

	case "collapse-nav{":
		if (get_module_pref("check_navs") && get_module_setting("navs")) {
			$line = collapse_sectionopen($name, $title, true);
			$args['content']=$line;
			if ($templatestyle == MOD_COLLAPSE_STYLE_CLASSIC) {
				$args['style']="classic";
			} else {
				$args['style']="default";
			}
		}
		break;
	case "}collapse-nav":
		if (get_module_pref("check_navs") && get_module_setting("navs")) {
			$line = collapse_sectionclose(true);
			$args['content']=$line;
			if ($templatestyle == MOD_COLLAPSE_STYLE_CLASSIC) {
				$args['style']="classic";
			} else {
				$args['style']="default";
			}
		}
		break;
	case "collapse{":
		if (get_module_pref("check_text") && get_module_setting("text")) {
			collapse_sectionopen($name, $title);
		}
		break;
	case "}collapse":
		if (get_module_pref("check_text") && get_module_setting("text")) {
			collapse_sectionclose();
		}
		break;
	}
	return $args;
}

function collapse_run(){
 	if(httpget('value')){
		set_module_pref(httpget('area'),1);
	}else{
		clear_module_pref(httpget('area'));
	}
	header("Content-Type: text/xml");
	echo '<setting area="'.htmlentities(httpget('area'), ENT_COMPAT, getsetting("charset", "ISO-8859-1")).'" value="'.htmlentities(httpget('value'), ENT_COMPAT, getsetting("charset", "ISO-8859-1")).'"/>';
	exit();
}
?>
