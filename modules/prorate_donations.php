<?php
function prorate_donations_getmoduleinfo(){
    $info = array(
        "name"=>"Prorated Donator Points",
        "version"=>"1.0",
        "author"=>"Eric Stevens",
        "category"=>"Administrative",
        "download"=>"core_module",
        "settings"=>array(
            "Prorated Donator Points - Settings,title",
            "Enter dollar ranges and percent to prorate them below; enter 0 as the range for any range sets you wish to disable.,note",
            "zero_rate"=>"Percent of points to award from \$0.00 to the first adjustment range,int|0",
            "range_1"=>"Interval 1 starts at (dollars),int|1",
            "rate_1"=>"Interval 1 pays (percent),int|70",
            "range_2"=>"Interval 2 starts at (dollars),int|3",
            "rate_2"=>"Interval 2 pays (percent),int|90",
            "range_3"=>"Interval 3 starts at (dollars),int|5",
            "rate_3"=>"Interval 3 pays (percent),int|100",
            "range_4"=>"Interval 4 starts at (dollars),int|10",
            "rate_4"=>"Interval 4 pays (percent),int|110",
            "range_5"=>"Interval 5 starts at (dollars),int|20",
            "rate_5"=>"Interval 5 pays (percent),int|120",
        )
    );
    return $info;
}

function prorate_donations_install(){
	module_addhook("donation_adjustments");
	module_addhook("donator_point_messages");
	return true;
}

function prorate_donations_uninstall(){
	return true;
}

function prorate_donations_dohook($hookname,$args){
	switch($hookname){
	case "donation_adjustments":
		$orig_points = $args['points'];
		$args['points'] = prorate_donations_apply_rate($args['amount'],$args['points']);
		$pct = 1 + ($args['points'] - $orig_points) / $orig_points;
		$pct = round($pct * 100,2);
		$args['messages'][] = "Prorated donation of \${$args['amount']} to {$pct}% ({$args['points']} points).";
		break;
	case "donator_point_messages":
		$args['messages']['default'] = "`7Because of processing fees on small donations, and the tendency of some players to donate small amounts on a frequent basis, we're introducing incentive to consolidate payments.  As a result, depending on how much you donate, you'll receive a larger reward in contributor points.`n";
		$smallest = -1;
		$lastrange = 0;
		for ($x = 5; $x >= 1; $x--){
			$range = get_module_setting("range_$x");
			$rate = get_module_setting("rate_$x");
			if ($range > 0){
				if ($lastrange) {
					$args['messages'][] = sprintf_translate("`0&#149; `7For donations equal to or greater than `&\$%s`7 but less than `&\$%s`7, you will receive `^%s`7 points for each `&\$1`7 donated.`n", $range, $lastrange, $rate);
				} else {
					$args['messages'][] = sprintf_translate("`0&#149; `7For donations equal to or greater than `&\$%s`7, you will receive `^%s`7 points for each `&\$1`7 donated.`n", $range, $rate);
				}
				if ($smallest == -1 || $smallest > $range) {
					$smallest = $range;
				}
				$lastrange = $range;
			}
		}
		if ($smallest > 0){
			$smallpoints = get_module_setting("zero_rate");
			if ($smallpoints > 0){
				$args['messages'][] = "`0&#149; `7For donations `&under \$$smallest`7, you will receive `^$smallpoints`7 points.`n";
			}else{
				$args['messages'][] = "`0&#149; `7Although we appreciate your generosity, transaction fees make it unreasonable for us to assign any contributor points for donations `&under \$$range.`n";
			}
		}
		$args['messages'][] = "`n";
		break;
	}
	return $args;
}

function prorate_donations_apply_rate($amount,$points){
	$rate = get_module_setting("zero_rate");
	for ($x = 1; $x <= 5; $x++ ){
		if (abs($amount) >= get_module_setting("range_$x") && get_module_setting("range_$x") > 0){
			$rate = get_module_setting("rate_$x");
		}
	}
	return $points * $rate / 100;
}
?>
