<?php

function funddriverewards_getmoduleinfo(){
	$info = array(
		"name"=>"Fund Drive Rewards",
		"version"=>"1.0",
		"author"=>"Eric Stevens",
		"category"=>"Administrative",
		"download"=>"core_module",
		"settings"=>array(
			"Extra Forest Fights,title",
			"giveff"=>"Give extra forest fights?,bool|1",
			"ffstartat"=>"Starting at what percent of objective?,int|100",
			"ffperpct"=>"Give 1 fight per how many percent over start point?,int|10",
			"maxff"=>"Give no more than how many forest fights?,int|10",

			"Reduced Healing Cost,title",
			"giveheal"=>"Give reduced healing cost?,bool|1",
			"healstartat"=>"Starting at what percent of objective?,int|100",
			"healperpct"=>"Percent to reduce cost per percent over start point?,int|1",
			"maxheal"=>"Max percent to reduce healing cost?,int|10",
		),
		"requires"=>array(
			"funddrive"=>"1.1|Fund Drive Indicator by Eric Stevens, not available for public release."
		),
	);
	return $info;
}

function funddriverewards_install(){
	module_addhook("newday");
	module_addhook("healmultiply");
	return true;
}

function funddriverewards_uninstall(){
	return true;
}

function funddriverewards_dohook($hookname,$args){
	$result = modulehook("funddrive_getpercent");
	$percent = $result['percent'];
	switch($hookname){
	case "newday":
		//Do forest fights.
		if (get_module_setting("giveff")){
			output_notl("`@`c***`c`0");
			$addedFights = 0;
			if ($percent >= get_module_setting("ffstartat")){
				$above = $percent - get_module_setting("ffstartat");
				$addedFights = ceil($above / get_module_setting("ffperpct"));
				$addedFights = (int)min($addedFights,get_module_setting("maxff"));
				if ($addedFights > 0){
					global $session;
					$session['user']['turns'] += $addedFights;
					$args['turnstoday'] .= ", funddriverewards: $addedFights";
				}
			}
			output("`2As a reward for donating, all players will receive an extra forest fight every game day for each `@%s`2%% over `@%s`2%%, up to `@%s`2 total fights."
				,get_module_setting("ffperpct")
				,get_module_setting("ffstartat")
				,get_module_setting("maxff"));
			output("`nYou receive `^%s`2 extra %s!", $addedFights, translate_inline($addedFights == 1?"fight":"fights"));
			output_notl("`@`c***`c`0");
		}
		break;
	case "healmultiply":
		if (get_module_setting("giveheal")){
			if ((float)get_module_setting("healperpct") >= 1){
				output("`2As a reward for donating, all players will receive a discount on healing costs of `@%s`2%% for each percent donated over `@%s`2%%, up to `@%s`2%% total."
					,get_module_setting("healperpct")
					,get_module_setting("healstartat")
					,get_module_setting("maxheal"));
			}else{
				$divider = funddriverewards_gcf(100,round(get_module_setting("healperpct")*100));
				output("`2As a reward for donating, all players will receive a discount on healing costs of `@%s`2%% for each `@%s`2%% donated over `@%s`2%%, up to `@%s`2%% total."
					,get_module_setting("healperpct")*$divider
					,$divider
					,get_module_setting("healstartat")
					,get_module_setting("maxheal"));
			}
			$pctoff = 0;
			if ($percent > get_module_setting("healstartat")){
				$above = $percent - get_module_setting("healstartat");
				$pctoff = round($above * get_module_setting("healperpct"), 2);
				$pctoff = min(get_module_setting("maxheal"),$pctoff);
			}
			output("`nAs a result, your costs are reduced by `^%s`2%%!`n`n",$pctoff);
			$args['alterpct'] *= ((100-$pctoff)/100);
		}
		break;
	}
	return $args;
}
function funddriverewards_gcf($a,$b){
	//efficient gcf detector for numbers with factors up to 97.
	$primes = array(2,3,5,7,11,13,17,19,23,29,31,37,41,43,47,53,59,61,67,71,73,79,83,89,97);
	$return = 1;
	for ($i=0; $i < count($primes); $i++){
		if ($a % $primes[$i] == 0 && $b % $primes[$i]==0) $return *= $primes[$i];
	}
	return $return;
}
?>
