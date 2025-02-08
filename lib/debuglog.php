<?php

/**
 * Inserts a message into the debug log. 
 * 
 * @todo Restructure this table to accept serialized data
 * @todo Reduce the amount of arguments
 * @param string $message Debugging message to display
 * @param bool|int $target Account ID of targeted log
 * @param bool|int $actor Account ID of the user who triggered a debuglog
 * @param bool|string $field Tag for this debug message
 * @param bool|float $value Weighted value for the message
 * @return void
 */
function debuglog(
    string $message,
    bool|int $target = false, 
    bool|int $actor = false,
    bool|string $field = false,
    bool|float $value = false
): void
{
    if ($actor === false) {
        global $session;
        $actor = $session['user']['acctid'];
    }
	if ($field === false) $field = '';
    if ($target === false) $target = 0;
	if ($value === false) {
        $value = 0;
    } else {
        $message .= " ($value)";
    }
    $date = date('Y-m-d H:i:s');
    $debugLog = db_prefix('debuglog');
    $message = addslashes($message);
	db_query(
        "INSERT INTO $debugLog (date, actor, target, message, field, value)
        VALUES('$date', $actor, $target, '$message', '$field', '$value')"
    );
}
