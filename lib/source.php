<?php
// This needs to be a function so we can recerse through dirs
function return_legal_dirs($dirs, &$legal_dirs, $path="./") {
	global $select_dir;
	foreach ($dirs as $dir=>$value) {
		// If this is a dir to exclude, skip it
		if (!$value) continue;

		$sdir = $dir;
		$base = $path.$sdir;

		// If this is not a 'recursive' dir, add it and continue
		if (!strstr($base, "/*")) {
			array_push($legal_dirs, $base);
			continue;
		}

		// Strip of the /*
		$base = substr($base, 0, -2);
		array_push($legal_dirs, $base . "/");
		$d = dir("$base");
		$add_dirs = array();
		while($entry = $d->read()) {
			// Skip any . files
			if ($entry[0] == '.') continue;
			// skip any php files
			if (substr($entry,strrpos($entry, '.')) == ".php") continue;
			$ndir = $base . "/" . $entry;
			// Okay, check if it's a directory
			$test = preg_replace("!^\\./!", "", $ndir);
			if (is_dir($ndir)) {
				if ((!isset($dirs[$test]) ||
					$dirs[$test] != 0) && ((strpos($select_dir,$base) !== false)) && $select_dir != "./") {
						$add_dirs[$ndir."/*"] = 1;
				}
			}
		}
		if (count($add_dirs) > 0) {
			return_legal_dirs($add_dirs, $legal_dirs, "");
		}
	}
}
?>