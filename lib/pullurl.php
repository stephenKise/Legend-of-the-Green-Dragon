<?php
// addnews ready
// translator ready
// mail ready

function _curl($url)
{
	$ch = curl_init();
	if (!$ch) return false;

	// set URL and other appropriate options
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$val = 5;
	if (defined("DB_CONNECTED") && DB_CONNECTED==true) {
		require_once("lib/settings.php");
		$val = getsetting("curltimeout", 5);
	}
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $val);
	curl_setopt($ch, CURLOPT_TIMEOUT, $val);

	// grab URL and pass it to the browser
	$ret = curl_exec($ch);

	// close curl resource, and free up system resources
	curl_close($ch);

	$val = explode("\n", $ret);
	$total = count($val);
	$cur = 0;
	foreach($val as $k=>$a) {
		$cur++;
		$done[] = $a.($cur != $total ? "\n" : "");
	}
	return $done;
}

function _sock($url)
{
	$a = preg_match("!http://([^/:]+)(:[0-9]+)?(/.*)!", $url, $matches);
	if (!$a) return false;

	$host = $matches[1];
	$port = (int) $matches[2];
	if ($port == 0) $port = 80;
	$path = $matches[3];

	$f = @fsockopen($host, $port, $errno, $errstr, 1);
	if (!$f) return false;

	if (function_exists("stream_set_timeout")) stream_set_timeout($f, 1);

	$out = "GET $path HTTP/1.1\r\n";
	$out .= "Host: $host\r\n";
	$out .= "Connection: Close\r\n\r\n";

	fwrite($f, $out);
	$skip = 1;
	$done = array();

	while(!feof($f)) {
		$buf = fgets($f, 8192);
		if ($buf == "\r\n" && $skip) { $skip = 0; continue; }
		if (!$skip) $done[] = $buf;
	}
	$info = stream_get_meta_data($fp);
	fclose($f);
	if ($info['timed_out']) {
		debug("Call to $url timed out!");
		$done = false;
	}
	return $done;
}

function pullurl($url)
{
	if (function_exists("curl_init")) return _curl($url);
	// For sume reason the socket code isn't working
	//if (function_exists("fsockopen")) return _sock($url);
	return @file($url);
}
?>
