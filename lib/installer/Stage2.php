<?php

global $mysqli_resource;
$session['dbinfo'] = httpAllPost();
$dbInfo = $session['dbinfo'];
$repository = $dbInfo['MODULES_REPOSITORY'];
$cachePath = $dbInfo['DB_DATACACHEPATH'];
$indent = "&nbsp;&nbsp;&nbsp;&nbsp;";
$failed = "$indent `\$`b&#10008;`b";
$passed = "$indent `@`b&#10004;`b";
$dbConnect = "<?php\n";
output("`@`c`bDeploying Server`b`c");
output(
    "`n`2The server will try to finish as much of the installation process
    as possible. You may have to intervene at certain steps, refer to the wiki,
    and recheck credentials multiple times if you are still learning how to
    host a webserver. Be patient - the installation process is one of the
    largest hurdles!`n"
);

// Create cache directory.
if ($dbInfo['DB_USEDATACACHE'] == 1 && $cachePath != '') {
    mkdir($cachePath);
}
if (!is_dir($cachePath)) {
    output("%s Could not create the cache path.`n", $failed, true);
    $endNavigation = true;
    return;
}

// Connect to the MySQL server.
output("`n`3&bull; Attempting to establish a MySQL connection...`n", true);
$x = @db_connect($dbInfo['DB_HOST'], $dbInfo['DB_USER'], $dbInfo['DB_PASS']);
if (!$mysqli_resource) {
    output("%s We could not connect to the MySQL server!`n", $failed, true);
    $endNavigation = true;
    return;
}
define('DB_CONNECTED', true);
define('DB_NODB', false);
define('DB_INSTALLER_STAGE2', true);
output(
    "%s Connected to `^%s`@.`n",
    $passed,
    mysqli_get_server_info($mysqli_resource),
    true
);

// Create the database.
output("`n`3&bull; Creating database (`#{$dbInfo['DB_NAME']}`3)...`n", true);
$databaseName = mysqli_real_escape_string($mysqli_resource, $dbInfo['DB_NAME']);
$sql = mysqli_query($mysqli_resource, "CREATE DATABASE $databaseName;");
if ($error = mysqli_error($mysqli_resource)) {
    output("%s `\$MySQL error: %s`n", $failed, $error, true);
    $sql = mysqli_query(
        $mysqli_resource,
        "SELECT COUNT(*) AS c FROM information_schema.tables
        WHERE table_schema = '$databaseName'"
    );
    $row = mysqli_fetch_assoc($sql);
    if ($row['c'] < 1) {
        output("%s Database is empty. Continuing to use it.`n", $passed, true);
    } else {
        output("%s Database is already in use.`n", $failed, true);
        $endNavigation = true;
        return;
    }
}
if (!$select = mysqli_select_db($mysqli_resource, $databaseName)) {
    output(
        "%s Could not select database for use: %s`n",
        $failed,
        mysqli_error($mysqli_resource),
        true
    );
    $endNavigation = true;
    return;
}
define('LINK', $select);
output("%s Database created and accessed properly!`n", $passed, true);

// Write dbconnect.php
output(
    "`n`3&bull; Writing database credentials to `#dbconnect.php`3...`n",
    true
);
foreach ($dbInfo as $key => $val) {
    $dbConnect .= "    \$$key = '$val';\n";
}
$dbFile = @fopen('dbconnect.php', 'w+');
if ($dbFile !== false && fwrite($dbFile, $dbConnect)) {
    output(
        "%s Successfully wrote credentials to to %s/dbconnect.php`n",
        $passed,
        $_SERVER['DOCUMENT_ROOT'],
        true
    );
} else {
    output(
        "%s You must create the file yourself or check that `Q%s`\$ has proper
        permissions to write in the `^%s`\$ directory`n",
        $failed,
        getenv('USER'),
        $_SERVER['DOCUMENT_ROOT'],
        true
    );
    fclose($dbFile);
    return;
}
fclose($dbFile);

// Create all rows in tables.
output("`n`3&bull; Inserting default data into tables...`n", true);
rawoutput("<blockquote hidden>");
foreach (get_all_tables() as $table => $data) {
    synctable($dbInfo['DB_PREFIX'] . $table, $data);
}
rawoutput("</blockquote>");
$sql = mysqli_query(
    $mysqli_resource,
    "SELECT count(*) AS total FROM information_schema.tables
    WHERE table_schema = '$databaseName';"
);
$row = mysqli_fetch_assoc($sql);
if ($row['total'] != count(get_all_tables())) {
    output("%s Could not write all tables to the database.`n", $failed, true);
    $endNavigation = true;
    return;
}
output("%s Created all tables successfully!`n", $passed, true);
foreach ($sql_upgrade_statements as $version => $statements) {
    foreach ($statements as $stmt) {
        mysqli_query($mysqli_resource, $stmt);
    }
}
output("%s Populated the server with default data!`n", $passed, true);
$db = db_prefix('settings');
foreach ($defaultSettings as $setting => $value) {
    $setting = mysqli_real_escape_string($mysqli_resource, $setting);
    $value = mysqli_real_escape_string($mysqli_resource, $value);
    mysqli_query(
        $mysqli_resource,
        "INSERT INTO $db (setting, value) VALUES ('$setting', '$value');"
    );
}
output("%s Saved all default settings!`n", $passed, true);

// Clone the modules folder.
if ($repository == '') {
    output("`n`3&bull; Skipping module cloning...", true);
    mkdir('modules');
    return;
}
output("`n`3&bull; Cloning modules from `#%s`3...`n", $repository, true);
if (is_dir('modules')) {
    output(
        "%s Modules directory already exists! Please remove %s/modules and try
        again!",
        $failed,
        $_SERVER['DOCUMENT_ROOT'],
        true
    );
    $endNavigation = true;
    return;
}
if (strpos($repository, '@') !== false) {
    $repository = str_replace(':', '/', $repository);
    $repository = str_replace('git@', 'https://', $repository);
}
shell_exec("git clone $repository modules/");
if (!is_dir('modules')) {
    output(
        "%s Could not clone the modules repository! Make sure that the PHP user
        (%s) has proper permissions to the root directory and git!`n",
        $failed,
        getenv('USER'),
        true
    );
    $endNavigation = true;
    return;
}
output("%s Cloned the repository into the modules directory!`n", $passed, true);
$session['stagecompleted'] = 2;
