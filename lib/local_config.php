<?php

// Make PHP know about the memory limit.
// Please note, this will not work if you are running in safe mode.
// If you are, then you will need to find some other way of increasing
// your memory limit.   This increase is needed because when going to
// install all modules, it is very possible to blow out this memory as
// it tries to load and compile every selected module file.
// Of course, people shouldn't be doing that, but people seem to think
// that more is better always, even when it's not.  Just blame it on the
// 'supersize society' we live in.
ini_set("memory_limit","64M");
ini_set("max_execution_time", "90");
?>
