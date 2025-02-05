<?php

require_once('lib/safeescape.php');
require_once('lib/sanitize.php');

/**
 * Creates new 'Ye Olde Mail' message.
 * 
 * @param int $to Account ID of the message recipient.
 * @param string $subject Title of the message.
 * @param string $body Body of the message.
 * @param int $from Account ID of the message sender.
 * @return void
 */
function systemmail(
    int $to = 0,
    string $subject = '',
    string $body = '',
    int $from = 0
): void
{
	global $session;
    $mail = db_prefix('mail');
    $acctId = $session['user']['acctid'];
    $date = date("Y-m-d H:i:s");
    $subject = safeescape($subject);
    $body = safeescape($body);
	db_query(
        "INSERT INTO $mail (msgfrom, msgto, subject, body, sent, originator)
        VALUES ($from, $to, '$subject', '$body', '$date', $acctId);"
    );
	invalidatedatacache("mail-$to");
    // TODO: May want to add a modulehook to this since emailing is removed.
}
