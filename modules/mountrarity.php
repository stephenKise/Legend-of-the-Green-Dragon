<?php

/*
Mount Rarity
File: mountrarity.php
Author:  Red Yates aka Deimos
Date:    1/10/2005
Version: 1.1 (1/16/2005)

Attaches a setting to each mount for rarity percentage.
Each game day the module roles for each mount to be available or not.
Done by request of the jcp.

v1.02
Fixed stupid error wherein nothing actually happened.

v1.1
Made changes so that it blocks the navs on every stables page, not just the
main.
Flipped the available/unavailable pref for more sensible boolean operating.

*/
// translator ready
// addnews ready
// mail ready

function mountrarity_getmoduleinfo(){
	$info=array(
		"name"=>"Mount Rarity",
		"version"=>"1.1",
		"author"=>"`\$Red Yates",
		"category"=>"Mounts",
		"download"=>"core_module",
		"settings"=>array(
			"Mount Rarity settings,title",
			"showout"=>"Show missing mounts list,bool|0",
		),
		"prefs-mounts"=>array(
			"Mount Rarity Mount Preferences,title",
			"rarity"=>"Percentage chance of mount being available each day,range,1,100,1|100",
			"unavailable"=>"Is mount unavailable today,bool|0",
		),
	);
	return $info;
}

function mountrarity_install(){
	module_addhook("newday-runonce");
	module_addhook("mountfeatures");
	module_addhook("stables-desc");
	module_addhook("stables-nav");
	return true;
}

function mountrarity_uninstall(){
	return true;
}

function mountrarity_dohook($hookname, $args){
	switch($hookname){
	case "newday-runonce":
		$sql="SELECT mountid FROM ".db_prefix("mounts")." WHERE mountactive=1";
		$result=db_query($sql);
		while($row=db_fetch_assoc($result)) {
			$id=$row['mountid'];
			$rarity=get_module_objpref("mounts",$id,"rarity");
			if (e_rand(1,100)>$rarity) {
				set_module_objpref("mounts", $id, "unavailable", 1);
			} else {
				// You need to reset the availability if it's not unavailable
				// otherwise, it never becomes available again!
				set_module_objpref("mounts", $id, "unavailable", 0);
			}
		}
		break;
	case "mountfeatures":
		$rarity=get_module_objpref("mounts",$args['id'],"rarity");
		$args['features']['Rarity']=$rarity;
		break;
	case "stables-desc":
		if (get_module_setting("showout")){
			$sql="SELECT mountid, mountname FROM ".db_prefix("mounts")." WHERE mountactive=1";
			$result=db_query($sql);
			output("`nA sign by the door proclaims that the following mounts are out of stock for today:");
			while ($row=db_fetch_assoc($result)) {
				$out=get_module_objpref("mounts",$row['mountid'],"unavailable");
				if ($out){
					output("`n%s",$row['mountname']);
				}
			}
		}else{
			output("`nIf you don't see something you like today, perhaps you should check again tomorrow.");
		}
		break;
	case "stables-nav":
		$sql="SELECT mountid FROM ".db_prefix("mounts")." WHERE mountactive=1";
		$result=db_query($sql);
		while($row=db_fetch_assoc($result)) {
			$id=$row['mountid'];
			$out=get_module_objpref("mounts",$id,"unavailable");
			if ($out) blocknav("stables.php?op=examine&id=$id");
		}
		break;
	}
	return $args;
}

function mountrarity_run(){
}

?>
