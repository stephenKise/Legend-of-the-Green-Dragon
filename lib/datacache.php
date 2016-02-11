<?php
// translator ready
// addnews ready
// mail ready
//This is a data caching library intended to lighten the load on lotgd.net
//use of this library is not recommended for most installations as it raises
//the issue of some race conditions which are mitigated on high volume
//sites but which could cause odd behavior on low volume sites, with out
//offering much if any advantage.

//basically the idea behind this library is to provide a non-blocking
//storage mechanism for non-critical data.

$datacache = array();
$datacachefilepath = "";
$checkedforolddatacaches = false;
define("DATACACHE_FILENAME_PREFIX","datacache_");

function datacache($name,$duration=60){
	global $datacache;
	if (getsetting("usedatacache",0)){
		if (isset($datacache[$name])){
			// we've already loaded this data cache this page hit and we
			// can simply return it.
			return $datacache[$name];
		}else{
			//we haven't loaded this data cache this page hit.
			$fullname = makecachetempname($name);
			if (file_exists($fullname) &&
					filemtime($fullname) > strtotime("-$duration seconds")){
				//the cache file *does* exist, and is not overly old.
				$fullfile = @file_get_contents($fullname);
				if ($fullfile > ""){
					$datacache[$name] = @unserialize($fullfile);
					return $datacache[$name];
				}else{
					return false;
				}
			}
		}
	}
	// The field didn't exist, or it was too old.
	return false;
}

//do NOT send simply a false value in to array or it will bork datacache in to
//thinking that no data is cached or we are outside of the cache period.
function updatedatacache($name,$data){
	global $datacache;
	if (getsetting("usedatacache",0)){
		$fullname = makecachetempname($name);
		$datacache[$name] = $data; //serialize($array);
		$fp = fopen($fullname,"w");
		if ($fp){
			if (!fwrite($fp,serialize($data))){
			}else{
			}
			fclose($fp);
		}else{
		}
		return true;
	}
	//debug($datacache);
	return false;
}

//we want to be able to invalidate data caches when we know we've done
//something which would change the data.
function invalidatedatacache($name,$full=false){
	global $datacache;
	if (getsetting("usedatacache",0)){
		if(!$full) $fullname = makecachetempname($name);
		else $fullname = $name;
		if (file_exists($fullname)) @unlink($fullname);
		unset($datacache[$name]);
	}
}


//Invalidates *all* caches, which contain $name at the beginning of their filename.
function massinvalidate($name) {
	if (getsetting("usedatacache",0)){
		$name = DATACACHE_FILENAME_PREFIX.$name;
		global $datacachefilepath;
		if ($datacachefilepath=="")
			$datacachefilepath = getsetting("datacachepath","/tmp");
		$dir = @dir($datacachefilepath);
		if(is_object($dir)) {
			while(false !== ($file = $dir->read())) {
				if (strpos($file, $name) !== false) {
					invalidatedatacache($dir->path."/".$file,true);
				}
			}
			$dir->close();
		}
	}
}


function makecachetempname($name){
	//one place to sanitize names for data caches.
	global $datacache, $datacachefilepath,$checkedforolddatacaches;
	if ($datacachefilepath=="")
		$datacachefilepath = getsetting("datacachepath","/tmp");
	//let's make sure that someone can't trick us in to
	$name = DATACACHE_FILENAME_PREFIX.preg_replace("'[^A-Za-z0-9.-]'","",$name);
	$fullname = $datacachefilepath."/".$name;
	//clean out double slashes (this also blocks file wrappers woot)
	$fullname = preg_replace("'//'","/",$fullname);
	$fullname = preg_replace("'\\\\'","\\",$fullname);


	if ($checkedforolddatacaches==false){
		$checkedforolddatacaches=true;
		// we want this to be 1 in 100 chance per page hit, not per data
		// cache call.
		// Once a hundred page hits, we want to clean out old caches.
//		if (mt_rand(1,100)<2){
//			$handle = opendir($datacachefilepath);
//			while (($file = readdir($handle)) !== false) {
//				if (substr($file,0,strlen(DATACACHE_FILENAME_PREFIX)) ==
//						DATACACHE_FILENAME_PREFIX){
//					$fn = $datacachefilepath."/".$file;
//					$fn = preg_replace("'//'","/",$fn);
//					$fn = preg_replace("'\\\\'","\\",$fn);
//					if (is_file($fn) &&
//							filemtime($fn) < strtotime("-24 hours")){
//						unlink($fn);
//					}else{
//					}
//				}
//			}
//		}
	}
	return $fullname;
}

?>
