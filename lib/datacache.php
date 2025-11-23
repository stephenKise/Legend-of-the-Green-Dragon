<?php

//This is a data caching library intended to lighten the load on lotgd.net
//use of this library is not recommended for most installations as it raises
//the issue of some race conditions which are mitigated on high volume
//sites but which could cause odd behavior on low volume sites, with out
//offering much if any advantage.

//basically the idea behind this library is to provide a non-blocking
//storage mechanism for non-critical data.

$datacache = [];
$datacachefilepath = '';
define("DATACACHE_FILENAME_PREFIX", "cache_");

function datacache(string $name, $duration = 60): bool|array
{
	if (!file_exists('dbconnect.php')) return false;
	global $datacache;
    // @TODO: Remove the usedatacache setting.
	if (isset($datacache[$name])) {
		return $datacache[$name];
	} 
	$fileName = makecachetempname($name);
	if (
        file_exists($fileName)
        && filemtime($fileName) > strtotime("-$duration seconds")
    ) {
		$file = @file_get_contents($fileName);
        if ($file === "") return false;
		$datacache[$name] = @unserialize($file);
		return $datacache[$name];
	}
	return false;
}

//do NOT send simply a false value in to array or it will bork datacache in to
//thinking that no data is cached or we are outside of the cache period.
function updatedatacache(string $name, array $data): bool
{
	if (!file_exists('dbconnect.php')) return false;
	global $datacache;
	$fileName = makecachetempname($name);
	$datacache[$name] = $data;
	$file = fopen($fileName, 'w');
	if (!$file) return false;
    $written = fwrite($file, serialize($data));
	fclose($file);
	return $written !== false;
}

//we want to be able to invalidate data caches when we know we've done
//something which would change the data.
function invalidatedatacache(string $name): bool
{
	if (!file_exists('dbconnect.php')) return false;
	global $datacache;
	$fileName = makecachetempname($name);
	if (!file_exists($fileName)) return false;
    @unlink($fileName);
	unset($datacache[$name]);
    return true;
}


//Invalidates *all* caches, which contain $name at the beginning of their filename.
function massinvalidate(string $name): bool
{
	if (!file_exists('dbconnect.php')) return false;
	$fileName = DATACACHE_FILENAME_PREFIX . $name;
    // @TODO: Change this name of this setting.
	$path = getsetting('datacachepath', '/cache');
	$dir = @dir($path);
    if (!$dir) return false;
	while(false !== ($file = $dir->read())) {
		if (strpos($file, $name) !== false) {
			invalidatedatacache(str_replace(DATACACHE_FILENAME_PREFIX, '', $file));
		}
	}
	$dir->close();
    return true;
}


function makecachetempname(string $name): bool|string
{
	if (!file_exists('dbconnect.php')) return false;
    // @TODO: Change this name of this setting.
	$path = getsetting('datacachepath', '/cache');
    $name = preg_replace("'[^A-Za-z0-9_:.-]'", "", $name);
	$name = DATACACHE_FILENAME_PREFIX . $name;
    $filePath = "$path/$name";
	$filePath = preg_replace("'//'","/", $filePath);
	$filePath = preg_replace("'\\\\'","\\", $filePath);
	return $filePath;
}
