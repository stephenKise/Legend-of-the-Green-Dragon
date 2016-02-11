<?php
// translator ready
// addnews ready
// mail ready
function grassyfield_getmoduleinfo(){
	$info = array(
		"name"=>"Grassy Field",
		"version"=>"1.1",
		"author"=>"Sean McKillion<br>modified by Eric Stevens & JT Traub",
		"category"=>"Forest Specials",
		"download"=>"core_module",
	);
	return $info;
}

function grassyfield_getrounds() {
	global $playermount, $session;
	$buff = unserialize($playermount['mountbuff']);
	$maxrounds = $buff['rounds'];
	$cur = isset($session['bufflist']['mount'])?$session['bufflist']['mount']['rounds']:0;
	return array($maxrounds, $cur);
}

function grassyfield_percent() {
	global $session;
	$ret = 40;
	if ($session['user']['hashorse']) {
		list($max, $cur) = grassyfield_getrounds();
		if ($cur > $max*.5) $ret = 100;
	}
	return $ret;
}

function grassyfield_install(){
	module_addeventhook("forest",
			"require_once(\"modules/grassyfield.php\");
			 return grassyfield_percent();");
	return true;
}

function grassyfield_uninstall(){
	return true;
}

function grassyfield_dohook($hookname,$args){
	return $args;
}

function grassyfield_runevent($type)
{
	require_once("lib/buffs.php");
	require_once("lib/commentary.php");
	addcommentary();

	global $session, $playermount;
	// We assume this event only shows up in the forest currently.
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:grassyfield";

	$op = httpget('op');
	if ($op=="return") {
		$session['user']['specialmisc']="";
		$session['user']['specialinc']="";
		redirect($from);
	}

	checkday();
	output("`n`c`#You Stumble Upon a Grassy Field`c`n`n");
	addnav("Return to the forest", $from . "op=return");

	require_once("lib/mountname.php");
	list($name, $lcname) = getmountname();

	if ($session['user']['specialmisc']!="Nothing to see here, move along.") {
		if ($session['user']['hashorse']>0){
			list($max, $cur) = grassyfield_getrounds();
			if ($cur > $max * .5) {
				// XXX: this message really should be a module objpref
				if ($playermount['partrecharge']) {
					tlschema("mounts");
					output($playermount['partrecharge']);
					tlschema();
				} else {
					output("`&You allow %s`& to frolic and gambol in the field.", $lcname);
				}
			} else {
				// XXX: this message really should be a module objpref
				if ($playermount['recharge']) {
					tlschema("mounts");
					output($playermount['recharge']);
					tlschema();
				} else {
					output("`&You allow %s`& to hunt and rest in the field.", $lcname);
				}
			}

			$buff = unserialize($playermount['mountbuff']);
			if (!isset($buff['schema']) || $buff['schema'] == "") $buff['schema']="mounts";
			apply_buff('mount',$buff);

			if ($session['user']['hitpoints'] <
					$session['user']['maxhitpoints']){
				output("`n`^Your nap leaves you completely healed!");
				$session['user']['hitpoints'] =
					$session['user']['maxhitpoints'];
			}
			$args=array(
				'soberval'=>0.8,
				'sobermsg'=>"`n`&Naps are quite a sobering experience!`n",
				'schema'=>"module-grassyfield",
			);
			modulehook("soberup", $args);
			$session['user']['turns']--;
			output("`n`n`^You lose a forest fight for today.");
		} else {
			output("`&Deciding to take a moment and a load off your poor weary feet you take a quick break from your ventures to take in the beautiful surroundings.");
			output("`n`n`^Your break leaves you completely healed!");
			if ($session['user']['hitpoints']<
					$session['user']['maxhitpoints'])
				$session['user']['hitpoints'] =
					$session['user']['maxhitpoints'];
		}
		$session['user']['specialmisc'] = "Nothing to see here, move along.";
	} else {
		output("`&You relax a while in the fields enjoying the sun and the shade.");
	}
	commentdisplay("`n`n`@Talk with the others lounging here.`n",
			"grassyfield","Speak lazily",10);
}

function grassyfield_run(){
}
?>
