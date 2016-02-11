<?php
reset($labels);
$pdktotal = 0;
$pdkneg = false;
modulehook("pdkpointrecalc");
foreach($labels as $type=>$label) {
	$pdktotal += (int)$pdks[$type];
	if((int)$pdks[$type] < 0) $pdkneg = true;
}
if ($pdktotal == $dkills-$dp && !$pdkneg) {
	$dp += $pdktotal;
	$session['user']['maxhitpoints'] += (5 * $pdks["hp"]);
	$session['user']['attack'] += $pdks["at"];
	$session['user']['defense'] += $pdks["de"];
	reset($labels);
	foreach($labels as $type=>$label) {
		$count = 0;
		if (isset($pdks[$type])) $count = (int)$pdks[$type];
		while($count) {
			$count--;
			array_push($session['user']['dragonpoints'],$type);
		}
	}
}else{
	output("`\$Error: Please spend the correct total amount of dragon points.`n`n");
}
?>