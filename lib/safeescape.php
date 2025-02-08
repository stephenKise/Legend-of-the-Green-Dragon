<?php

/**
 * Ensures an input string has a escaped apostrophe and quote characters
 * @param string $input Input to sanitize
 * @return string Formatted string with escaped marks
 */
function safeescape(string $input): string
{
	$prevChar = '';
	$safeString = '';
	for ($x = 0; $x < strlen($input); $x++) {
		$char = substr($input, $x, 1);
		if (($char == "'" || $char == '"') && $prevChar != "\\") {
			$char = "\\$char";
		}
		$safeString .= $char;
		$prevChar = $char;
	}
	return $safeString;
}
