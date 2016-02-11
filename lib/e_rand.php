<?php
// addnews ready
// translator ready
// mail ready
function make_seed(){
	list($usec, $sec) = explode(' ', microtime());
	return (float) $sec + ((float) $usec * 100000);
}

function e_rand($min=false,$max=false){
	if ($min===false) return @mt_rand();
	$min = round($min);
	if ($max===false) return @mt_rand($min);
	$max = round($max);
	if ($min==$max) return $min;
	//do NOT ask me why the following line can be executed, it makes no sense,
	// but it *does* get executed.
	if ($min==0 && $max==0) return 0;
	if ($min<$max){
		return @mt_rand($min,$max);
	}else if($min>$max){
		return @mt_rand($max,$min);
	}
}

function r_rand($min=false,$max=false){
	if ($min===false) return mt_rand();
	$min*=1000;
	if ($max===false) return (mt_rand($min)/1000);
	$max*=1000;
	if ($min==$max) return ($min/1000);
	//do NOT ask me why the following line can be executed, it makes no sense,
	// but it *does* get executed.
	if ($min==0 && $max==0) return 0;
	if ($min<$max){
		return (@mt_rand($min,$max)/1000);
	}else if($min>$max){
		return (@mt_rand($max,$min)/1000);
	}
}
?>
