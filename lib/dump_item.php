<?php

function dump_item(array|string $item): string
{
	$out = '';
	if (is_array($item)) $temp = $item;
	else $temp = @unserialize($item);
	if (is_array($temp)) {
        $length = count($temp);
		$out .= "array($length) {<div style='padding-left: 20pt;'>";
		foreach ($temp as $key => $val) {
            $dump = dump_item($val);
			$out .= "'$key' = '$dump'`n";
		}
		$out .= "</div>}";
	} else {
		$out .= $item;
	}
	return $out;
}

function dump_item_ascode(array|string $item): string
{
	$out = '';
	if (is_array($item)) $temp = $item;
	else $temp = @unserialize($item);
	if (is_array($temp)) {
		$out .= "array(\n\t";
		$row = [];
		foreach ($temp as $key => $val) {
			array_push($row, "'$key'=&gt;" . dump_item_ascode($val));
		}
		if (strlen(join(", ", $row)) > 80){
		 	$out .= join(",\n\t", $row);
		}else{
		 	$out .= join(", ", $row);
		}
		$out .= "\n\t)";
	} else {
        $item = htmlent(addslashes($item));
		$out .= "'$item'";
	}
	return $out;
}
