<?php

$acctId = (int)httpget('userid');
$accountsPrefix = db_prefix('accounts');
$sql = db_query(
    "SELECT name, lastip AS last_ip, uniqueid AS unique_id
    FROM $accountsPrefix
    WHERE acctid = '$acctId'"
);
$row = db_fetch_assoc($sql);
if (!$row) return;
var_dump($row);
$lastIp = htmlent($row['last_ip']);
$uniqueId = htmlent($row['unique_id']);
if ($row['name'] != '')
	output(
        "Setting up ban information based on `\$%s`0",
        $row['name']
    );
$pban = translate_inline("Post ban");
rawoutput(
        "<form action='user.php?op=saveban' method='POST' class='ban-form'>
            Ban based on:
            <input type='radio' value='ip' id='ipradio' name='type' checked>
            <label for='ip'>IP</label>
            <input hidden name='ip' id='ip' value='$lastIp' placeholder='Use % for wildcard search'>
            <input type='radio' value='id' name='type'>
            <label for='id'>ID</label>
            <input hidden name='id' value='$uniqueId'>
            <br />
            <label for='duration'>Day Duration</label>
            <input name='duration' id='duration' size='3' value='14'>
            <br />
            <lavel for='reason'>Reason</label>
            <input name='reason' size=50 value=''>
            <input 
                type='submit'
                class='button'
                value='$pban'
                onClick='if (document.getElementById(\"duration\").value==0) {return confirm('Are you sure?');} else {return true;}'
            />
        </form>"
);
$reason = httpget('reason');
if ($reason == '')
	$reason = translate_inline("Bad conduct");
addnav('', 'user.php?op=saveban');