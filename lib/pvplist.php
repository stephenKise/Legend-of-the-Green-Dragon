<?php

//put these outside the function since they're used by scripts outside of
//this function.
// @TODO: Remove these from this file.
$pvptime = getsetting('pvptimeout', 600);
$pvptimeout = date('Y-m-d H:i:s', strtotime("-$pvptime seconds"));


/**
 * Provides a list of PvP targets the player may access, and navigation to engage
 * in PvP combat.
 * 
 * @param bool|string $location Location to search for PvP targets.
 * @param bool|string $link Uri to navigate to when engaging in PvP.
 * @param bool|string $extra Extra options to append to uri.
 * @param bool|string $sql Override the default PvP search SQL statement.
 * @return void
 */
function pvplist(
    bool|string $location = false,
    bool|string $link = false,
    bool|string $extra = false,
    bool|string $sql = false
): void
{
	global $session, $pvptime, $pvptimeout;

	if ($location === false) {
		$location = $session['user']['location'];
    }
	if ($link === false) {
		$link = basename($_SERVER['SCRIPT_NAME']);
	}
	if ($extra === false) {
		$extra = '?act=attack';
	}

	$days = getsetting('pvpimmunity', 5);
	$exp = getsetting('pvpminexp', 1500);
    $clanRankColors = ['`!', '`#', '`^', '`&', '`$'];
    $returnUri = urlencode($_SERVER['REQUEST_URI']);

	if ($sql === false) {
        $loginTimeout = getsetting('LOGINTIMEOUT', 900);
        $accounts = db_prefix('accounts');
        $clans = db_prefix('clans');
		$minLvl = $session['user']['level'] - 1;
		$maxLvl = $session['user']['level'] + 2;
		$lastOnline = date("Y-m-d H:i:s", strtotime("-$loginTimeout sec"));
		$id = $session['user']['acctid'];
		$loc = addslashes($location);
		$sql = "SELECT acctid, name, alive, location, sex, level, laston, 
			loggedin, login, pvpflag, clanshort, clanrank, dragonkills, 
			$accounts.clanid
            FROM $accounts
            LEFT JOIN $clans
            ON $clans.clanid = $accounts.clanid
            WHERE (locked = 0) 
			AND (slaydragon = 0)
            AND (age > $days OR dragonkills > 0 OR pk > 0 OR experience > $exp)
			AND (level >= $minLvl AND level <= $maxLvl)
            AND (alive = 1) 
			AND (laston < '$lastOnline' OR loggedin = 0)
            AND (acctid <> $id) 
			ORDER BY location = '$loc' DESC, location, level DESC,
            experience DESC, dragonkills DESC";
	}

	$result = db_query($sql);

	$pvp = [];
	while($row = db_fetch_assoc($result)) {
		$pvp[] = $row;
	}

	$pvp = modulehook('pvpmodifytargets', $pvp);

	tlschema('pvp');
	$n = translate_inline('Name');
	$l = translate_inline('Level');
	$loc = translate_inline('Location');
	$ops = translate_inline('Ops');
	$bio = translate_inline('Bio');
	$att = translate_inline('Attack');

	rawoutput(
        "<table border='0' cellpadding='3' cellspacing='0' class='list pvp-list'>
        <tr class='trhead'>
            <td>$n</td>
            <td>$l</td>
            <td>$loc</td>
            <td>$ops</td>
        </tr>"
    );
	$locationCounts = [];
	$num = count($pvp);
	for ($i = 0; $i < $num; $i++) {
		$row = $pvp[$i];
        $acctId = $row['acctid'];
        $clanColor = $clanRankColors[$row['clanrank']];
		if (isset($row['invalid']) && $row['invalid']) continue;
		if (!isset($locationCounts[$row['location']])) {
			$locationCounts[$row['location']] = 0;
        }
		$locationCounts[$row['location']]++;
		if ($row['location'] != $location) continue;
		$bioLink = "bio.php?char=$acctId&ret=$returnUri";
		addnav('', $bioLink);
		rawoutput("<tr class='".($i % 2 ? 'trlight' : 'trdark')."'><td>");
		if ($row['clanshort'] > '' && $row['clanrank'] > CLAN_APPLICANT) {
			output_notl(
                '%s&lt;`2%s%s&gt;`0 ',
				$clanColor,
                $row['clanshort'],
				$clanColor,
                true
            );
		}
		output_notl("`@%s`0", $row['name']);
		rawoutput("</td><td>");
		output_notl("%s", $row['level']);
		rawoutput("</td><td>");
		output_notl("%s", $row['location']);
		rawoutput("</td><td>[ <a href='$bioLink'>$bio</a> | ");
		if($row['pvpflag'] > $pvptimeout) {
			output("`i(Attacked too recently)`i");
		} else if ($location != $row['location']) {
			output("`i(Can't reach them from here)`i");
		} else {
			rawoutput("<a href='$link$extra&name=$acctId'>$att</a>");
			addnav('',"$link$extra&name=$acctId");
		}
		rawoutput(" ]</td></tr>");
	}

	if (!isset($locationCounts[$location]) || $locationCounts[$location]==0) {
		$noone = translate_inline('`iThere are no available targets.`i');
		output_notl("<tr><td align='center' colspan='4'>$noone</td></tr>", true);
	}
	rawoutput("</table>");

	if (
        $num != 0 
        && (!isset($locationCounts[$location]) || $locationCounts[$location] != $num)
    ) {
		output(
            "`n`n`&As you listen to different people around you talking,
            you glean the following additional information:`n"
        );
		foreach ($locationCounts as $loc => $count) {
			if ($loc == $location) continue;
			$args = modulehook('pvpcount', ['count' => $count, 'loc' => $loc]);
			if (isset($args['handled']) && $args['handled']) continue;
			output(
                "`&There %s `^%s`& sleeping in %s`& whom you may challenge.`0`n",
                ($count == 1 ? "is": "are"),
                $count,
                $loc
            );
		}
	}
	tlschema();
}
