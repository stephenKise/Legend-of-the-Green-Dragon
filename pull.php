<?php

$output = shell_exec('git pull origin dev');
echo "<pre>$output</pre>";
