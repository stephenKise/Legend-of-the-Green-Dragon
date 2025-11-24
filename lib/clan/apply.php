<?php

require_once 'lib/nltoappon.php';
require_once 'lib/systemmail.php';

$to = (int) httpget('to');
$clansPrefix = db_prefix('clans');
$mailPrefix = db_prefix('mail');
if ($to > 0) {
	output("`%%s`7 accepts your application, files it in her out box, and folds her hands on the desk, staring at you.", $registrar);
	output("You stand there staring blankly back at her for a few minutes before she suggests that perhaps you'd like to take a seat in the waiting area.");

	addnav('Return to the Lobby', 'clan.php');
	$session['user']['clanid'] = $to;
	$session['user']['clanrank'] = CLAN_APPLICANT;
	$session['user']['clanjoindate'] = date('Y-m-d H:i:s');
	$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE clanid='{$session['user']['clanid']}' AND clanrank>=".CLAN_OFFICER;
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)) {
		$msg = sprintf_translate(
            "`^You have a new Clan applicant! `&%s`^ has completed a membership
             application for your Clan!",
            $session['user']['name']
        );
        $msgTitle = translate('`@New Clan Applicant`0');
		systemmail($row['acctid'], $msgTitle, $msg);
	}

	// send reminder mail if clan of choice has a description

    invalidatedatacache("clan_members:$to");
    $clansPrefix = db_prefix('clans');
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
         WHERE clanid = '$to'",
        "clan_data:$clanId",
        3600
    );
	$row = db_fetch_assoc($selectedClanQuery);

	if (array_key_exists('description', $row) && nltoappon($row['description']) != '') {
		$subject = translate('Clan Application Reminder');
		$mail = translate(
            "`&Did you remember to read the description of the Clan of your
             choice before applying? Note that some clans may have requirements
             that you have to fulfill before you can become a member. If you
             are not accepted into the clan of your choice anytime soon, it may
             be because you have not fulfilled these requirements.  For your
             convenience, the description of the clan you are applying to is
             reproduced below.`n`n`c`#%s`@<`^%s`@>`0`c`n%s"
            );

		systemmail(
            $session['user']['acctid'],
            $subject,
            sprintf_translate(
                $mail,
                $row['name'],
                $row['tag'],
                nltoappon($row['description'])
            )
        );
	}
} else {
	$sql = "SELECT MAX($clansPrefix.clanid) AS clanid,MAX(clanname) AS clanname,count(" . db_prefix("accounts") . ".acctid) AS c FROM $clansPrefix INNER JOIN " . db_prefix("accounts") . " ON $clansPrefix.clanid=" . db_prefix("accounts") . ".clanid WHERE " . db_prefix("accounts") . ".clanrank > ".CLAN_APPLICANT." GROUP BY $clansPrefix.clanid ORDER BY c DESC";
	$result = db_query($sql);
	if (db_num_rows($result) > 0) {
		output("`7You ask %s`7 for a clan membership application form.",$registrar);
		output("She opens a drawer in her desk and pulls out a form.  It contains only two lines: Name and Clan Name.");
		output("You furrow your brow, not sure if you really like having to deal with all this red tape, and get set to concentrate really hard in order to complete the form.");
		output("Noticing your attempt to write on the form with your %s, %s`7 claims the form back from you, writes %s`7 on the first line, and asks you the name of the clan that you'd like to join:`n`n",$session['user']['weapon'],$registrar,$session['user']['name']);
		for ($i=0; $i < db_num_rows($result); $i++) {
			$row = db_fetch_assoc($result);
			if ($row['c'] == 0) {
				$sql = "DELETE FROM $clansPrefix WHERE clanid={$row['clanid']}";
				db_query($sql);
			} else {
				$row = modulehook('clan-applymember', $row);
				if (isset($row['handled']) && $row['handled']) continue;
				$memb_n = translate_inline('(%s members)');
				$memb_1 = translate_inline('(%s member)');
				if ($row['c'] == 1) {
					$memb = sprintf($memb_1, $row['c']);
				} else {
					$memb = sprintf($memb_n, $row['c']);
				}
				output_notl(
                    "&#149; <a href='clan.php?op=apply&to=%s'>%s</a> %s`n",
					$row['clanid'],
					full_sanitize(htmlentities($row['clanname'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))),
					$memb,
                    true
                );
				addnav('', "clan.php?op=apply&to={$row['clanid']}");
			}
		}
		addnav('Return to the Lobby','clan.php');
	} else {
		output("`7You ask %s`7 for a clan membership application form.", $registrar);
		output("She stares at you blankly for a few moments, then says, \"`5Sorry pal, no one has had enough gumption to start up a clan yet.  Maybe that should be you, eh?`7\"");
		addnav('Apply for a New Clan', 'clan.php?op=new');
		addnav('Return to the Lobby', 'clan.php');
	}
}
