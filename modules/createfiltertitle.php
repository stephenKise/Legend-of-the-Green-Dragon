<?php

// this is a module that filters out new names at character creation that
// either begin or end with a title awarded for dragon kills, or lack thereof

function createfiltertitle_getmoduleinfo()
{
	$info = array(
		"name"=>"Title Filter at Creation",
		"category"=>"Administrative",
		"author"=>"dying",
		"version"=> "0.1",
		"download"=>"core_module",
	);
   return $info;
}

function createfiltertitle_install()
{
   module_addhook("check-create");

   return true;
}

function createfiltertitle_uninstall()
{
   return true;
}

function createfiltertitle_dohook($hookname, $args)
{
	switch ($hookname) {
	case "check-create":
		$sql = "SELECT male,female FROM ".db_prefix("titles");
		$res = db_query($sql);

		$errmsg =
			translate_inline("Your name contains the in-game title \"%s\".");

		$name = str_replace(" ", "", $args['name']);

		while ($row = db_fetch_assoc($res)) {
			$tf = str_replace(" ", "", $row['female']);
			$f1 = "/^" . $tf . "/i";
			$f2 = "/" . $tf . "$/i";
			if ( (preg_match($f1, $name) > 0) ||
					(preg_match($f2, $name) > 0) ) {
				$args['blockaccount'] = 1;
				if($args['msg']) $args['msg'] .= "`n";
				$args['msg'] .= sprintf($errmsg, $row['female']);
			}

			$tm = str_replace(" ", "", $row['male']);
			if ($tm != $tf) {
				$m1 = "/^" . $tm . "/i";
				$m2 = "/" . $tm . "$/i";
				if ( (preg_match($m1, $name) > 0) ||
						(preg_match($m2, $name) > 0) ) {
					$args['blockaccount'] = 1;
					if($args['msg']) $args['msg'] .= "`n";
					$args['msg'] .= sprintf($errmsg, $row['male']);
				}
			}
		}
		break;
	}
	return $args;
}

?>
