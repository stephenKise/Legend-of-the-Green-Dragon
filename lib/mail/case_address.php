<?php

output_notl("<form action='mail.php?op=write' method='post'>", true);
output("`2`bAddress:`b`n`0");
$to = translate_inline('To:');
$search = translate_inline('Search');
$prePop = htmlent(stripslashes(httpget('prepop')));
output_notl(
    "`2<input name='to' id='to' placeholder='%s' value='%s' autofocus>",
    $to,
    $prePop,
    true
);
output_notl("<input type='submit' class='button' value='%s'>", $search, true);
if ($session['user']['superuser'] & SU_IS_GAMEMASTER) {
	$from = translate_inline('From: ');
	output_notl("`n`b`2%s`b`n<input name='from' id='from'>`n`0", $from, true);
	output("`7`iLeave empty to send from your account!`i`0");
}
rawoutput("</form>");