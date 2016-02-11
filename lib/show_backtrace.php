<?php
function show_no_backtrace() {
	return "";
}

function show_backtrace(){
	static $sent_css = false;
	if (!function_exists("debug_backtrace")) {
		return show_no_backtrace();
	}

	$bt = debug_backtrace();

	$return = "";
	if (!$sent_css){
		$return .= "<style type='text/css'>
		.stacktrace { background-color: #FFFFFF; color: #000000; }
		.stacktrace .function { color: #0000FF; }
		.stacktrace .number { color: #FF0000; }
		.stacktrace .string { color: #009900; }
		.stacktrace .bool { color: #000099; font-weight: bold; }
		.stacktrace .null { color: #999999; font-weight: bold; }
		.stacktrace .object { color: #009999; font-weight: bold; }
		.stacktrace .array { color: #990099; }
		.stacktrace .unknown { color: #669900; font-weight: bold; }
		.stacktrace blockquote { padding-top: 0px; padding-bottom: 0px; margin-top: 0px; margin-bottom: 0px; }
		</style>";
	}
	$return .= "<div class='stacktrace'><b>Call Stack:</b><br>";
	reset($bt);
	$x=0;
	while(list($key,$val)=each($bt)){
		if ($x > 0 && $val['function'] != 'logd_error_handler'){
			$return .= "<b>$x:</b> <span class='function'>{$val['function']}(";
			$y=0;
			if ($val['args'] && is_array($val['args'])) {
				reset($val['args']);
				while (list($k,$v) = each($val['args'])){
					if ($y > 0) $return.=", ";
					$return.=backtrace_getType($v);
					$y++;
				}
			} elseif ($val['args']) {
				// If for some reason it's not an array, don't barf.
				$return.=backtrace_getType($val['args']);
			}
			$return.=")</span>&nbsp;called from <b>{$val['file']}</b> on line <b>{$val['line']}</b><br>";
		}
		$x++;
	}
	$return.="</div>";
	return $return;
}
function backtrace_getType($in){
	$return = "";
	if (is_string($in)){
		$return.="<span class='string'>\"";
		if (strlen($in) > 25){
			$return.=htmlentities(substr($in,0,25)."...", ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
		}else{
			$return.=htmlentities($in, ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
		}
		$return.="\"</span>";
	}elseif (is_bool($in)){
		$return.="<span class='bool'>".($in?"true":"false")."</span>";
	}elseif (is_int($in)){
		$return.="<span class='number'>{$in}</span>";
	}elseif (is_float($in)){
		$return.="<span class='number'>".round($in,3)."</span>";
	}elseif (is_object($in)){
		$return.="<span class='object'>".get_class($in)."</span>";
	}elseif (is_null($in)){
		$return.="<span class='null'>NULL</span>";
	}elseif (is_array($in)){
		if (count($in)>0){
			$return.="<span class='array'>Array(<blockquote>";
			reset($in);
			$x=0;
			while (list($key,$val)=each($in)){
				if ($x>0) $return.=", ";
				$return.=backtrace_getType($key)."=>".backtrace_getType($val);
				$x++;
			}
			$return.="</blockquote>)</span>";
		}else{
			$return.="<span class='array'>Array()</span>";
		}
	}else{
		$return.="<span class='unknown'>Unknown[".gettype($in)."]</span>";
	}
	return $return;
}
?>
