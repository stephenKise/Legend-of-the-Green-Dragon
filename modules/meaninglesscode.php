<?php

// this module hides appo code sequences that have no special meaning
// when they are used in the commentary

function meaninglesscode_getmoduleinfo() {
	$info = array(
		"name"=>"Hide Meaningless Appo Codes",
		"version"=>"0.1",
		"author"=>"dying",
		"category"=>"Commentary",
		"download"=>"core_module",
		"description"=>"Hides any apostrophe codes which aren't valid.",
		"settings"=>array(
			"Hide Meaningless Appo Code Settings, title",
			"colorandgraveonly"=>"Strip all appo codes except for color codes and the grave accent,bool|0",
		),
	);
	return $info;
}

function meaninglesscode_install() {
	module_addhook("commentary");
	return true;
}

function meaninglesscode_uninstall() {
	return true;
}

function meaninglesscode_dohook($hookname, $args) {
	switch($hookname){
	case "commentary":
		$comment = $args['commentline'];
		$i = 0;
		while (($i+1)<strlen($comment)) {
				// if additional appo code meanings are added,
				// the value of $codes may need to be changed
			if (get_module_setting("colorandgraveonly")==1) {
				$codes = "01234567!@#\$%^&qQ)RVvgGTt~eEjJlLxXyYkKpPmM`";
			} else {
				$codes = "01234567!@#\$%^&qQ)RVvgGTt~eEjJlLxXyYkKpPmMct><Hbinw`";
			}

			if ($comment{$i}=='`') {
				if (strchr($codes, $comment{$i+1})==false) {
					$comment = substr_replace($comment, "", $i, 2);
				} else {
					$i += 2;
				}
			} else {
				$i++;
			}
		}

		$args['commentline'] = $comment;

		break;
	}
	return $args;
}

?>
