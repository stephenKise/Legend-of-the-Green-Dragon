<?php

$accounts = db_prefix('accounts');
$sql = db_query(
    "SELECT name, lastip, uniqueid FROM $accounts WHERE acctid = $userid"
);
$row = db_fetch_assoc($sql);
if ($row['name'] != '') {
    output("Setting up ban information based on `\$%s`0`n", $row['name']);
}
rawoutput(
    sprintf(
        "<form action='user.php?op=saveban' method='POST'>
        <input type='radio' value='ip' name='type' checked> IP: 
        <input type='text' name='ip' id='ip' value='%s'> <br \>
        <input type='radio' value='ip' name='type'> ID: 
        <input type='text' name='id' id='id' value='%s'> <br \>
        Duration:
        <input type='number' name='duration' min='0' value='3'> <br \>
        Reason:
        <input type='text' name='reason' size='50' value='%s' required> <br \>
        <input type='submit' class='button' value='Post Ban' id='saveBan'>
        </form>",
        htmlent($row['lastip'] ?: ''),
        htmlent($row['uniqueid'] ?: ''),
        httpget('reason')
    )
);
output(
    "`Q`iIP bans automatically have a wildcard at the end to ban the entire range!`i
    `n`n"
);
addnav('', 'user.php?op=saveban');
if ($row['name'] != '') {
    $id = $row['uniqueid'];
    $ip = $row['lastip'];
    $trimmedIP = substr($ip, 0, -1);
    $name = $row['name'];
    addnav('Navigation');
    addnav('Edit user', "user.php?op=edit&userid=$userid");
    output(
        "`\$Banning`@ by `QID `^(%s)`@ will effect the following accounts:`0`n",
        $id
    );
    $sql = db_query(
        "SELECT name, login
        FROM $accounts WHERE uniqueid = '$id' ORDER BY lastip"
    );
    while ($row = db_fetch_assoc($sql)) {
        output_notl(
            "`%%s `^(%s)`0`n",
            $row['name'],
            $row['login']
        );
    }
    output_notl("`n");
    output(
        "`\$Banning `@by `Qsimilar IP `^(%s)`@ will effect the following accounts:`0`n",
        $ip
    );
    //FROM HERE DOWN WAS IN A FOR LOOP. MAKE SURE IT'S NOT CONSTRUCTED AS SUCH
    //CREATE TABLE TO SHOW NAME, LOGIN, LASTIP, UNIQUEUID AND GENTIMECOUNT
    $sql = db_query(
        "SELECT name, lastip, uniqueid laston, gentimecount
        FROM $accounts
        WHERE lastip LIKE '$trimmedIP%'
        AND NOT (lastip LIKE '$oip')
        ORDER BY uniqueid"
    );
    while ($row = db_fetch_assoc($sql)) {
        $lastOn = strtotime($row['laston']);
        output(
            "(%s) [%s] `%%s`0 - %s hits, last: %s`n",
            $row['lastip'],
            $row['uniqueid'],
            $row['name'],
            $row['gentimecount'],
            reltime($lastOn)
        );
    }
}
