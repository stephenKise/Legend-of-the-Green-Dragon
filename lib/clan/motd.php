<?php

require_once('lib/nltoappon.php');

$accountsPrefix = db_prefix('accounts');
$clansPrefix = db_prefix('clans');
$clanId = $session['user']['clanid'];
$userId = $session['user']['acctid'];
$selectedClanQuery = db_query_cached(
    "SELECT clanname AS name,
     clanshort AS tag,
     clandesc AS description,
     clanmotd AS motd,
     clanid AS id,
     descauthor,
     motdauthor,
     customsay
     FROM $clansPrefix
     WHERE clanid = '$clanId'",
    "clan_data:$clanId",
    3600
);
$clanData = db_fetch_assoc($selectedClanQuery);

page_header('Update Description/MotD');

addnav('Clan Options');
if ($session['user']['clanrank'] >= CLAN_OFFICER) {
	$clanMotd = substr(httppost('motd'), 0, 4096);
	if (
        httppostisset('motd')
        && stripslashes($clanMotd) != $clanData['motd']
    ){
		db_query(
            "UPDATE $clansPrefix
             SET clanmotd = '$clanMotd', motdauthor = $userId
             WHERE clanid = $clanId"
        );
		invalidatedatacache("clan_data:$clanId");
		$clanData['motd'] = stripslashes($clanMotd);
		output("Updating MoTD`n");
		$clanData['motdauthor'] = $session['user']['acctid'];
	}

	$clanDesc = httppost('description');
	if (httppostisset('description') &&
			stripslashes($clanDesc)!=$clanData['description'] &&
			$clanData['descauthor']!=4294967295){
		$sql = "UPDATE $clansPrefix SET clandesc='".addslashes(substr(stripslashes($clanDesc),0,4096))."',descauthor={$session['user']['acctid']} WHERE clanid={$clanData['id']}";
		db_query($sql);
		invalidatedatacache("clan_data:$clanId");
		output("Updating description`n");
		$clanData['description'] = stripslashes($clanDesc);
		$clanData['descauthor'] = $session['user']['acctid'];
	}

	$customSay = httppost('customsay');
	if (
        httppostisset('customsay')
        && $customSay != $clanData['customsay']
        && $session['user']['clanrank'] >= CLAN_LEADER
    ) {
		db_query(
            "UPDATE $clansPrefix
             SET customsay='$customSay'
             WHERE clanid = $clanId"
        );
		invalidatedatacache("clan_data:$clanId");
		output('Updating custom say line`n');
		$clanData['customsay'] = stripslashes($customSay);
	}
	$result = db_query(
        "SELECT name
         FROM $accountsPrefix
         WHERE acctid={$clanData['motdauthor']}"
    );
	$row = db_fetch_assoc($result);
	$motdauthname = $row['name'];

	$result = db_query(
        "SELECT name
         FROM $accountsPrefix
         WHERE acctid={$clanData['descauthor']}"
    );
	$row = db_fetch_assoc($result);
	$descauthname = $row['name'];

	output('`&`bCurrent MoTD:`b `#by %s`2`n', $motdauthname);
	output_notl(nltoappon($clanData['motd']) . '`n');
	output('`&`bCurrent Description:`b `#by %s`2`n', $descauthname);
	output_notl(nltoappon($clanData['description']) . '`n');

	rawoutput("<form action='clan.php?op=motd' method='POST'>");
	addnav('', 'clan.php?op=motd');
	output('`&`bMoTD:`b `7(4096 chars)`n');
	rawoutput("<textarea name='motd' cols='50' rows='10' class='input' style='width: 66%'>".htmlentities($clanData['motd'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br>");
	output('`n`&`bDescription:`b `7(4096 chars)`n');
	$blocked = translate_inline("Your clan has been blocked from posting a description.`n");
	if ($clanData['descauthor'] == INT_MAX) {
		output_notl($blocked);
	} else {
		rawoutput("<textarea name='description' cols='50' rows='10' class='input' style='width: 66%'>".htmlentities($clanData['description'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."</textarea><br>");
	}
	if ($session['user']['clanrank'] >= CLAN_LEADER) {
		output('`n`&`bCustom Talk Line`b `7(blank means "says" -- 15 chars max)`n');
		rawoutput("<input name='customsay' value=\"".htmlentities($clanData['customsay'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" class='input' maxlength=\"15\"><br/>");
	}
	$save = translate_inline('Save');
	rawoutput("<input type='submit' class='button' value='$save'>");
	rawoutput('</form>');
} else {
	output('You do not have authority to change your clan\'s motd or description.');
}
addnav('Return to your clan hall', 'clan.php');

page_footer();