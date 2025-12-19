<?php

require_once 'lib/nltoappon.php';
require_once 'lib/systemmail.php';

$to = (int) httpget('to');
$clansPrefix = db_prefix('clans');
$mailPrefix = db_prefix('mail');
if ($to > 0) {
	output("clan.application_sent", $registrar);

	addnav('Return to the Lobby', 'clan.php');
	$session['user']['clanid'] = $to;
	$session['user']['clanrank'] = CLAN_APPLICANT;
	$session['user']['clanjoindate'] = date('Y-m-d H:i:s');
	$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE clanid='{$session['user']['clanid']}' AND clanrank>=".CLAN_OFFICER;
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)) {
		$msg = sprintf(
            loadTranslation('clan.mail_officer_body'),
            $session['user']['name']
        );
        $msgTitle = loadTranslation('clan.mail_officer_subject');
		systemmail($row['acctid'], $msgTitle, $msg);
	}

	// send reminder mail if clan of choice has a description

    invalidatedatacache("clan_members:$to");
    $clansPrefix = db_prefix('clans');
    $selectedClanQuery = db_query(
        "SELECT clanname AS name,
         clanshort AS tag,
         clandesc AS description,
         clanmotd AS motd,
         clanid AS id,
         descauthor,
         motdauthor,
         customsay
         FROM $clansPrefix
         WHERE clanid = '$to'"
    );
	$row = db_fetch_assoc($selectedClanQuery);

	if (nltoappon($row['description']) != '') {
		$subject = loadTranslation('clan.applicant_reminder_subject');
		$mail = loadTranslation('clan.applicant_reminder_body');
		systemmail(
            $session['user']['acctid'],
            $subject,
            sprintf(
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
		output(sprintf(
            loadTranslation('clan.application_unknown'),
            $registrar,
            $session['user']['weapon'],
            $registrar,
            $session['user']['name']
        ));
        $clanRows = [];
		for ($i=0; $i < db_num_rows($result); $i++) {
			$row = db_fetch_assoc($result);
			if ($row['c'] == 0) {
				$sql = "DELETE FROM $clansPrefix WHERE clanid={$row['clanid']}";
				db_query($sql);
			} else {
                $trClass = $i % 2 ? 'trlight' : 'trdark';
				$row = modulehook('clan-applymember', $row);
				if (isset($row['handled']) && $row['handled']) continue;
				$memb_n = translate_inline('(%s members)');
				$memb_1 = translate_inline('(%s member)');
				if ($row['c'] == 1) {
					$memb = sprintf($memb_1, $row['c']);
				} else {
					$memb = sprintf($memb_n, $row['c']);
				}
				$clanRow = sprintf(
                    loadTranslation('clan.apply_list_row'),
                    $trClass,
                    $row['clanid'],
					full_sanitize(htmlentities($row['clanname'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))),
					$memb
                );
                array_push($clanRows, $clanRow);
				addnav('', "clan.php?op=apply&to={$row['clanid']}");
			}
		}
        output(
            sprintf(
                    loadTranslation('clan.apply_list_template'),
                    join($clanRows)
            ),
            true
        );
		addnav('Return to the Lobby','clan.php');
	} else {
		output("`7You ask %s`7 for a clan membership application form.", $registrar);
		output('clan.apply_no_clans_created');
		addnav('Apply for a New Clan', 'clan.php?op=new');
		addnav('Return to the Lobby', 'clan.php');
	}
}
