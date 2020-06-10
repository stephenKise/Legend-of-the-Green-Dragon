<?php

$session['installer_stage'] = 1;
$session['stagecompleted'] = 1;
$endNavigation = true;
$host = htmlent($session['dbinfo']['DB_HOST'] ?: 'localhost');
$user = htmlent($session['dbinfo']['DB_USER'] ?: 'root');
$name = htmlent($session['dbinfo']['DB_NAME'] ?: 'lotgd');
$prefix = htmlent($session['dbinfo']['DB_PREFIX'] ?: '');
$cachePath = htmlent($session['dbinfo']['DB_DATACACHEPATH'] ?: 'cache');
$useCacheSelected = (
        $session['dbinfo']['DB_USEDATACACHE'] == 1 ?
        'selected=\"selected\"' : ''
        );
$modulesRepo = htmlent(
    $session['dbinfo']['MODULES_REPOSITORY'] ?:
        'https://github.com/stephenKise/xythen-modules.git'
);
$no = translate_inline('no');
$yes = translate_inline('yes');
$submit = translate_inline("Test connection");
rawoutput("<form action='installer.php?stage=2' method='POST'>");
output("`@`c`bDatabase Connection Information`b`c`n`0");
if (file_exists('dbconnect.php')) {
    output(
        "`n`\$You already have a database connection saved! Modifying this form
        will update your database connection info! Do not submit anything
        if you do not intend to update your connection credentials!`n"
    );
}
output(
    "`n`2In order to run Legend of the Green Dragon, we need to set up a few
    server credentials. We will create your database and attempt to write a
    `0dbconnect.php `2 file to your system. If your permissions system denies
    the file to be made, you will have to create this file yourself. This stage
    of installation assumes you have MariaDB or a similar MySQL server
    installed as well as the proper php7.0-mysql or newer extension. If you
    need assistance with installation, you can read more or open an issue in the
    <a target='_blank' href='%s'>GitHub Repository</a>`2.`n",
    'http://github.com/stephenKise/Legend-of-the-Green-Dragon',
    true
);

output("`n`@What is the `^address`@ of your MySQL server?`n");
rawoutput("<input name='DB_HOST' value='$host' required>");
output("`n`@What is the MySQL `^username`@?`n");
rawoutput("<input name='DB_USER' value='$user' required>");
output("`n`@What is the `^password`@ for this username?`n");
rawoutput("<input type='password' name='DB_PASS'>");
output("`n`@What should we `^name`@ the LotGD database?`n");
rawoutput("<input name='DB_NAME' value='$name' required>");
output("`n`@What should we `^prefix`@ table names with?`n");
rawoutput("<input name='DB_PREFIX' value='$prefix'></div>");
output("`n`@Do you want to `^cache`@ database queries?`n");
rawoutput("<select id='DB_USEDATACACHE' name='DB_USEDATACACHE'>");
rawoutput("<option value='0' selected='selected'>$no</option>");
rawoutput("<option value='1' $useCacheSelected>$yes</option>");
rawoutput("</select><div id='DB_DATACACHE' style='display: none'>");
output("`n`@If yes, what should we `^name `@the cache directory?`n");
rawoutput("<input name='DB_DATACACHEPATH' value='$cachePath'></div>");
output("`n`@Which `^module repository`@ should we clone from?`Q *`n");
rawoutput("<input name='MODULES_REPOSITORY' value='$modulesRepo'>");
output("`n`n");
rawoutput("<input type='submit' value='$submit' class='button'>");
output("`n`n`Q* `iClone using a HTTPS protocol, not through a SSH protocol!");
rawoutput("</form>");
