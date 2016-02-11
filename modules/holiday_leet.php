<?php
// addnews ready
// mail ready
function holiday_leet_getmoduleinfo(){
	$info = array(
		"name"=>"Holiday - JCP's Birthday (l33t speak)",
		"version"=>"1.0",
		"author"=>"JT Traub",
		"download"=>"core_module",
		"category"=>"Holiday Texts",
		"settings"=>array(
			"JCP's Birthday Settings,title",
			"activate"=>"Activation date (mm-dd)|4-28",
		),
		"prefs"=>array(
			"JCP's Birthday User Preferences,title",
			"user_ignore"=>"Ignore L33t speak (JCP's Birthday),bool|0",
		),
	);
	return $info;
}

function holiday_leet_install(){
	module_addhook("holiday");
	return true;
}

function holiday_leet_uninstall(){
	return true;
}

function holiday_leet_munge($intext) {
	$out = "";
	$in = preg_split("/([, .!?])/",$intext,-1,PREG_SPLIT_DELIM_CAPTURE);
	$replacement = array(
		"their"=>"they're",
		"there"=>"their",
		"they're"=>"there",
		"any"=>"ne",
		"one"=>array("oen","1","won"),
		"kill"=>array("pwn","ownzor"),
		"killed"=>"was pwnt",
		"died"=>"was pwnt",
		"lost"=>"was pwnt",
		"are"=>array("r","aer"),
		"two"=>array("too","two","to",2),
		"too"=>array("too","two","to",2),
		"to"=>array("too","two","to",2),
		"laugh"=>"LOL",
		"laughs"=>"LOLOL",
		"chuckle"=>"LOL",
		"dragon"=>"hax0r",
		"mightye"=>"Teh 1337 hax0r in da sky",
		"some"=>"sum",
		"says"=>"sez",
		"where"=>array("wer","were","ware"),
		"the"=>array("teh","da",""),
		"a"=>"an",
		"an"=>"a",
		"and"=>array("n","an","and"),
		"village"=>"viallge",
		","=>array(", like,",", you knwo,",", sortof,",","," LOL,"," WTF,"),
		"!"=>array(" OMG!!1!1"," WOOT!!11!","!!!","1!!"),
		"?"=>array("????","?? ne1??"),
		"."=>array("LOL.",". OMG.",". WTF.",". BRB.", ". AFK.","."),
	);
	while (list($key,$val)=each($in)){
		if ($val===" "){
			$out.=$val;
			continue;
		}
		$inval = $val;
		$lval = strtolower($val);
		reset($replacement);
		while (list($k,$v)=each($replacement)){
			if (!is_array($v) && $lval == $k) {
				$val = $v;
				break;
			}elseif ($lval == $k){
				$val = $v[e_rand(0,count($v)-1)];
				break;
			}
		}

		if ($inval==$val && strpos($val,"`")===false){
			switch(e_rand(0,10)){
			case 1:
				$val = str_replace(array("a","b","e","i","l","o","s","t"),array(4,6,3,1,1,0,5,7),$val);
				break;
			case 2:
				$val = strtoupper($val);
				break;
			}
		}
		$out.=$val;
	}
	//insert typeohs.
	$move = array(
		array("!","@","#","$","%","^","&","*","(",")","_","+"),
		array("1","2","3","4","5","6","7","8","9","0","-","="),
		array("q","w","e","r","t","y","u","i","o","p","[","]","\\"),
		array("a","s","d","f","g","h","j","k","l",';','\''),
		array('z','x','c','v','b','n','m',',','.','/'),
	);
	$mapx = array();
	$mapy = array();
	//reverse the $move map to x and y values for forward lookup.
	for ($x=0;$x<count($move);$x++){
		for ($y=0;$y<count($move[$x]);$y++){
			$mapx[$move[$x][$y]]=$x;
			$mapy[$move[$x][$y]]=$y;
		}
	}
	for ($x=0;$x<round(strlen($out)/5);$x++){
		$p = e_rand(0,strlen($out)-1);
		if (substr($out,$p,1)=="`" || ($p<=0?false:substr($out,$p-1,1)=="`")){
			//this is a color code, don't mess with it.
		}else{
			$c = substr($out,$p,1);
			if (isset($mapx[$c])){
				$mx = $mapx[$c];
				$my = $mapy[$c];
				$d = e_rand(0,4);
				if ($d == 0 && $mx>0) $mx--;
				if ($d == 1 && $mx<count($move)) $mx++;
				if ($d == 2 && $my>0) $my--;
				if ($d == 3 && $my<count($move[$mx])) $my++;
				if (isset($move[$mx][$my])) $c = $move[$mx][$my];
				$out = substr($out,0,$p).$c.substr($out,$p+1);
			}
		}
	}
	return $out;
}

function holiday_leet_dohook($hookname,$args){
	switch($hookname){
	case "holiday":
		if(get_module_pref("user_ignore")) break;
		$mytime = get_module_setting("activate");
		list($amonth,$aday) = split("-", $mytime);
		$amonth = (int)$amonth;
		$aday = (int)$aday;
		$month = (int)date("m");
		$day = (int)date("d");
		if ($month == $amonth && $day == $aday) {
			$args['text'] = holiday_leet_munge($args['text']);
		}
		break;
	}
	return $args;
}

function holiday_leet_run(){
}
?>
