<?php
// addnews ready
// translator ready
// mail ready

$buffreplacements = array();
$debuggedbuffs = array();
function calculate_buff_fields(){
	global $session, $badguy, $buffreplacements, $debuggedbuffs;
	if (!$session['bufflist']) return;

	//run temp stats
	reset($session['bufflist']);
	while (list($buffname,$buff)=each($session['bufflist'])){
		if (!isset($buff['tempstats_calculated'])){
			while (list($property,$value)=each($buff)){
				if (substr($property,0,9)=='tempstat-'){
					apply_temp_stat(substr($property,9),$value);
				}
			}//end while
			$session['bufflist'][$buffname]['tempstats_calculated']=true;
		}//end if
	}//end while

	//process calculated buff fields.
	reset($session['bufflist']);
	if (!is_array($buffreplacements)) $buffreplacements = array();
	while (list($buffname,$buff)=each($session['bufflist'])){
		if (!isset($buff['fields_calculated'])){
			while (list($property,$value)=each($buff)){
				//calculate dynamic buff fields
				$origstring = $value;
				//Simple <module|variable> replacements for get_module_pref('variable','module')
				$value = preg_replace("/<([A-Za-z0-9]+)\\|([A-Za-z0-9]+)>/","get_module_pref('\\2','\\1')",$value);
				//simple <variable> replacements for $session['user']['variable']
				$value = preg_replace("/<([A-Za-z0-9]+)>/","\$session['user']['\\1']",$value);

				if (!defined("OLDSU")) {
					define("OLDSU", $session['user']['superuser']);
				}
				if ($value != $origstring){
					if (strtolower(substr($value,0,6))=="debug:"){
						$errors="";
						$origstring = substr($origstring,6);
						$value = substr($value,6);
						if (!isset($debuggedbuffs[$buffname])) $debuggedbuffs[$buffname]=array();

						ob_start();
						$val = eval("return $value;");
						$errors = ob_get_contents();
						ob_end_clean();

						if (!isset($debuggedbuffs[$buffname][$property])){
							if ($errors==""){
								debug("Buffs[$buffname][$property] evaluates successfully to $val");
							}else{
								debug("Buffs[$buffname][$property] has an evaluation error<br>"
								.htmlentities($origstring, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))." becomes <br>"
								.htmlentities($value, ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."<br>"
								.$errors);
								$val="";
							}
							$debuggedbuffs[$buffname][$property]=true;
						}

						$origstring="debug:".$origstring;
						$value="debug".$value;
					}else{
						$val = eval("return $value;");
					}
				}else{
					$val = $value;
				}

				$session['user']['superuser'] = OLDSU;

				//Avoiding PHP bug 27646
				// (http://bugs.php.net/bug.php?id=27646&edit=2) -
				// Unserialize doesn't recognize NAN, -INF and INF
				if (function_exists('is_nan')) {
					if (is_numeric($val) &&
							(is_nan($val) || is_infinite($val)))
						$val=$value;
				} else {
					// We have an older version of PHP, so, let's try
					// something else.
					$l = strtolower("$val");
					if ((substr($l, 3) == "nan") || (substr($l, -3) == "inf"))
						$val = $value;
				}
				if (!isset($output)) $output = "";
				if ($output == "" && (string)$val != (string)$origstring){
					$buffreplacements[$buffname][$property] = $origstring;
					$session['bufflist'][$buffname][$property] = $val;
				}//end if
				unset($val);
			}//end while
			$session['bufflist'][$buffname]['fields_calculated']=true;
		}//end if
	}//end while

}//end function

function restore_buff_fields(){
	global $session, $buffreplacements;
	if (is_array($buffreplacements)){
		reset($buffreplacements);
		while (list($buffname,$val)=each($buffreplacements)){
			reset($val);
			while (list($property,$value)=each($val)){
				if (isset($session['bufflist'][$buffname])){
					$session['bufflist'][$buffname][$property] = $value;
					unset($session['bufflist'][$buffname]['fields_calculated']);
				}//end if
			}//end while
			unset($buffreplacements[$buffname]);
		}//end while
	}//end if

	//restore temp stats
	if (!is_array($session['bufflist'])) $session['bufflist'] = array();
	reset($session['bufflist']);
	while (list($buffname,$buff)=each($session['bufflist'])){
		if (array_key_exists("tempstats_calculated",$buff) && $buff['tempstats_calculated']){
			reset($buff);
			while (list($property,$value)=each($buff)){
				if (substr($property,0,9)=='tempstat-'){
					apply_temp_stat(substr($property,9),-$value);
				}
			}//end while
			unset($session['bufflist'][$buffname]['tempstats_calculated']);
		}//end if
	}//end while
}//end function

function apply_buff($name,$buff){
	global $session,$buffreplacements, $translation_namespace;

	if (!isset($buff['schema']) || $buff['schema'] == "") {
		$buff['schema'] = $translation_namespace;
	}

	if (isset($buffreplacements[$name])) unset($buffreplacements[$name]);
	if (isset($session['bufflist'][$name])){
		//we'll need to unapply buff fields before applying this buff since
		//it's already set.
		restore_buff_fields();
	}
	$buff = modulehook("modify-buff", array("name"=>$name, "buff"=>$buff));
	$session['bufflist'][$name] = $buff['buff'];
	calculate_buff_fields();
}

function apply_companion($name,$companion,$ignorelimit=false){
	global $session, $companions;
	if (!is_array($companions)) {
		$companions = @unserialize($session['user']['companions']);
	}
	$companionsallowed = getsetting("companionsallowed", 1);
	$args = modulehook("companionsallowed", array("maxallowed"=>$companionsallowed));
	$companionsallowed = $args['maxallowed'];
	$current = 0;
	foreach ($companions as $thisname=>$thiscompanion) {
		if (isset($companion['ignorelimit']) && $companion['ignorelimit'] == true) {
		} else {
			if ($thisname != $name)
			++$current;
		}
	}
	if ($current < $companionsallowed || $ignorelimit == true) {
		if (isset($companions[$name])) {
			unset($companions[$name]);
		}
		if (!isset($companion['ignorelimit']) && $ignorelimit == true) {
			$companion['ignorelimit'] = true;
		}
		$companions[$name] = $companion;
		$session['user']['companions'] = createstring($companions);
		return true; // success!
	} else {
		debug("Failed to add companion due to restrictions regarding the maximum amount of companions allowed.");
		return false;
	}
}


function strip_buff($name){
	global $session, $buffreplacements;
	restore_buff_fields();
	if (isset($session['bufflist'][$name]))
		unset($session['bufflist'][$name]);
	if (isset($buffreplacements[$name]))
		unset($buffreplacements[$name]);
	calculate_buff_fields();
}

function strip_all_buffs(){
	global $session;
	$thebuffs = $session['bufflist'];
	reset($thebuffs);
	while (list($buffname,$buff)=each($thebuffs)){
		strip_buff($buffname);
	}
}

function has_buff($name){
	global $session;
	if (isset($session['bufflist'][$name])) return true;
	return false;
}

?>
