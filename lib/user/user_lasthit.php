<?php
$output = file_get_contents("accounts-output/$userid.html");
$output = str_replace("<iframe src=", "<iframe Xsrc=", $output);
$output = str_replace(".focus();",".blur();", $output);
echo $output;
exit();
?>