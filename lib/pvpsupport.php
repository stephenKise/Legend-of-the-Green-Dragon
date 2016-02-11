<?php
// translator ready
// addnews ready
// mail ready

require_once("lib/pvplist.php");
require_once("lib/pvpwarning.php");
require_once("lib/substitute.php");
require_once("lib/systemmail.php");
require_once("lib/datetime.php");

// This contains functions to support pvp
function setup_target($name) {
	global $pvptimeout, $session;
	//Legacy support
	if (is_numeric($name)) {
		$where = "acctid=$name";
	}else{
		$where = "login='$name'";
	}
	$sql = "SELECT name AS creaturename, level AS creaturelevel, weapon AS creatureweapon, gold AS creaturegold, experience AS creatureexp, maxhitpoints AS creaturehealth, attack AS creatureattack, defense AS creaturedefense, loggedin, location, laston, alive, acctid, pvpflag, boughtroomtoday, race FROM " . db_prefix("accounts") . " WHERE $where";
	$result = db_query($sql);
	if (db_num_rows($result)>0){
		$row = db_fetch_assoc($result);
		if (abs($session['user']['level']-$row['creaturelevel'])>2){
			output("`\$Error:`4 That user is out of your level range!");
			return false;
		}elseif ($row['pvpflag'] > $pvptimeout){
			output("`\$Oops:`4 That user is currently engaged by someone else, you'll have to wait your turn!");
			return false;
		}elseif (strtotime($row['laston']) >
				strtotime("-".getsetting("LOGINTIMEOUT",900)." sec") &&
				$row['loggedin']){
			output("`\$Error:`4 That user is now online, and cannot be attacked until they log off again.");
			return false;
		} elseif((int)$row['alive']!=1){
			output("`\$Error:`4 That user is not alive.");
			return false;
		}elseif ($session['user']['playerfights']>0){
			$sql = "UPDATE " . db_prefix("accounts") . " SET pvpflag='".date("Y-m-d H:i:s")."' WHERE acctid={$row['acctid']}";
			db_query($sql);
			$row['creatureexp'] = round($row['creatureexp'],0);
			$row['playerstarthp'] = $session['user']['hitpoints'];
			$row['fightstartdate'] = strtotime("now");
			$row = modulehook("pvpadjust", $row);
			pvpwarning(true);
			return $row;
		}else{
			output("`4Judging by how tired you are, you think you had best not engage in battle against other players right now.");
			return false;
		}
	} else {
		output("`\$Error:`4 That user was not found!  It's likely that their account expired just now.");
		return false;
	}
	return false;
}

function pvpvictory($badguy, $killedloc, $options)
{
	global $session;
	// If the victim has logged on and banked some, give the lessor of
	// the gold amounts.
	$sql = "SELECT gold FROM " . db_prefix("accounts") . " WHERE acctid='".(int)$badguy['acctid']."'";
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	$badguy['creaturegold'] =
		((int)$row['gold']>(int)$badguy['creaturegold']?
		 (int)$badguy['creaturegold']:(int)$row['gold']);

	if ($session['user']['level'] == 15) {
		output('`#***At your level of fighting prowess, the mere reward of beating your foe is sufficient accolade.`n');
	}

	// Winner of fight gets altered amount of gold based on badguy's level
	// and amount of gold they were carrying this can some times work to
	// their advantage, sometimes against.  The basic idea is to prevent
	// exhorbitant amounts of money from being transferred this way.
	$winamount = round(10 * $badguy['creaturelevel'] *
			log(max(1,$badguy['creaturegold'])),0);
	output("`b`\$You have slain %s!`0`b`n", $badguy['creaturename']);
	if ($session['user']['level'] == 15) $winamount = 0;
	output("`#You receive `^%s`# gold!`n", $winamount);
	$session['user']['gold']+=$winamount;

	$exp = round(getsetting("pvpattgain",10)*$badguy['creatureexp']/100,0);
	if ($session['user']['level'] == 15) $exp = 0;
	$expbonus = round(($exp *
				(1+.1*($badguy['creaturelevel']-
					   $session['user']['level']))) - $exp,0);
	if ($expbonus>0){
		output("`#***Because of the difficult nature of this fight, you are awarded an additional `^%s`# experience!`n", $expbonus);
	}else if ($expbonus<0){
		output("`#***Because of the simplistic nature of this fight, you are penalized `^%s`# experience!`n", abs($expbonus));
	}
	$wonexp = $exp + $expbonus;
	output("You receive `^%s`# experience!`n`0", $wonexp);
	$session['user']['experience']+=$wonexp;

	$lostexp = round($badguy['creatureexp']*getsetting("pvpdeflose",5)/100,0);

//	debuglog("gained $winamount ({$badguy['creaturegold']} base) gold and $wonexp exp (loser lost $lostexp) for killing ", $badguy['acctid']);
	//player wins gold and exp from badguy
	debuglog("started the fight and defeated {$badguy['creaturename']} in $killedloc (earned $winamount of {$badguy['creaturegold']} gold and $wonexp of $lostexp exp)",false,$session['user']['acctid']);
	debuglog("was victim and has been defeated by {$session['user']['name']} in $killedloc (lost {$badguy['creaturegold']} gold and $lostexp exp, actor tooks $winamount gold and $wonexp exp)",false,$badguy['acctid']);

	$args=array('pvpmessageadd'=>"", 'handled'=>false, 'badguy'=>$badguy, 'options'=>$options);
	$args = modulehook("pvpwin", $args);

	// /\- Gunnar Kreitz
	if ($session['user']['sex'] == SEX_MALE) {
		$msg = "`2While you were in %s, `^%s`2 initiated an attack on you with his `^%s`2, and defeated you!`n`nYou noticed he had an initial hp of `^%s`2 and just before you died he had `^%s`2 remaining.`n`nAs a result, you lost `\$%s%%`2 of your experience (approximately %s points), and `^%s`2 gold.`n%s`nDon't you think it's time for some revenge?`n`n`b`7Technical Notes:`b`nAlthough you might not have been in %s`7 when you got this message, you were in %s`7 when the fight was started, which was at %s according to the server (the fight lasted about %s).";
	} else {
		$msg = "`2While you were in %s, `^%s`2 initiated an attack on you with her `^%s`2, and defeated you!`n`nYou noticed she had an initial hp of `^%s`2 and just before you died she had `^%s`2 remaining.`n`nAs a result, you lost `\$%s%%`2 of your experience (approximately %s points), and `^%s`2 gold.`n%s`nDon't you think it's time for some revenge?`n`n`b`7Technical Notes:`b`nAlthough you might not have been in %s`7 when you got this message, you were in %s`7 when the fight was started, which was at %s according to the server (the fight lasted about %s).";
	}
	$mailmessage = array($msg,
			$killedloc, $session['user']['name'],
			$session['user']['weapon'], $badguy['playerstarthp'],
			$session['user']['hitpoints'], getsetting("pvpdeflose", 5),
			$lostexp, $badguy['creaturegold'], $args['pvpmessageadd'],
			$killedloc, $killedloc,
			date("D, M d h:i a", (int)$badguy['fightstartdate']),
			reltime((int)$badguy['fightstartdate']));

	systemmail($badguy['acctid'],
			array("`2You were killed while in %s`2", $killedloc),
			$mailmessage);
	// /\- Gunnar Kreitz

	$sql = "UPDATE " . db_prefix("accounts") . " SET alive=0, goldinbank=(goldinbank+IF(gold<{$badguy['creaturegold']},gold-{$badguy['creaturegold']},0)),gold=IF(gold<{$badguy['creaturegold']},0,gold-{$badguy['creaturegold']}), experience=experience-$lostexp WHERE acctid=".(int)$badguy['acctid']."";
	db_query($sql);
	return $args['handled'];
}

function pvpdefeat($badguy, $killedloc, $taunt, $options)
{
	global $session;

	addnav("Daily news","news.php");
	$killedin = $badguy['location'];
	$badguy['acctid']=(int)$badguy['acctid'];
	$badguy['creaturegold']=(int)$badguy['creaturegold'];

	// Winner of fight gets altered amount of gold based on badguy's level
	// and amount of gold they were carrying this can some times work to
	// their advantage, sometimes against.  The basic idea is to prevent
	// exhorbitant amounts of money from being transferred this way.
	$winamount = round(10 * $session['user']['level'] *
			log(max(1,$session['user']['gold'])),0);
	if ($badguy['creaturelevel'] == 15)	$wonamount = 0;

	$sql = "SELECT level FROM " . db_prefix("accounts") . " WHERE acctid={$badguy['acctid']}";
	$result = db_query($sql);
	$row = db_fetch_assoc($result);

	$wonexp = round($session['user']['experience']*getsetting("pvpdefgain",10)/100,0);
	if ($badguy['creaturelevel'] == 15)	$wonexp = 0;

	$lostexp = round($session['user']['experience'] * getsetting("pvpattlose",15) / 100,0);

	$args=array('pvpmessageadd'=>"", 'taunt'=>$taunt, 'handled'=>false, 'badguy'=>$badguy, 'options'=>$options);
	$args = modulehook("pvploss", $args);

	$msg = "`^%s`2 attacked you while you were in %s`2, but you were victorious!`n`n";
	if ($row['level'] < $badguy['creaturelevel']) {
		// if the player has leveled DOWN some how from when we started
		// attacking them, let's assume they DK'd, and these rewards are
		// way too rich for them.
		output("`cThis player has leveled down!!!`c");
		$msg .= "You would have received `^%s`2 experience and `^%s`2 gold, `\$however it seems you lost it all while fighting the dragon";
	} elseif ($badguy['creaturelevel'] == 15) {
		$msg .= "At your level of fighting prowess, the mere reward of beating your foe is sufficient accolade.  You received `^%s`2 experience and `^%s`2 gold";
	} else {
		$msg .= "You received `^%s`2 experience and `^%s`2 gold";
	}
	$msg .= "!`n%s`n`0";
	systemmail($badguy['acctid'],
			array("`2You were successful while you were in %s`2", $killedloc),
			array($msg, $session['user']['name'], $killedloc, $wonexp,
				$winamount, $args['pvpmsgadd']));

	if ($row['level'] >= $badguy['creaturelevel']) {
		// Only give the reward if the person didn't level down
		$sql = "UPDATE " . db_prefix("accounts") . " SET gold=gold+".$winamount.", experience=experience+".$wonexp." WHERE acctid=".(int)$badguy['acctid']."";
		db_query($sql);
	}

	$session['user']['alive']=false;
	//debuglog("lost {$session['user']['gold']} ($winamount to winner) gold and $lostexp exp ($wonexp to winner) being slain by ", $badguy['acctid']);
	
	debuglog("started the fight and has been defeated by {$badguy['creaturename']} in $killedloc (lost {$session['user']['gold']} gold and $lostexp exp, victim tooks $winamount gold and $wonexp exp)",false,$session['user']['acctid']);
	debuglog("was the victim and won aginst {$session['user']['name']} in $killedloc (earned $winamount gold and $wonexp exp)",false,$badguy['acctid']);	
	
	$session['user']['gold']=0;
	$session['user']['hitpoints']=0;
	$session['user']['experience'] =
		round($session['user']['experience']*
				(100-getsetting("pvpattlose",15))/100,0);
	output("`b`&You have been slain by `%%s`&!!!`n", $badguy['creaturename']);
	output("`4All gold on hand has been lost!`n");
	output("`4%s%% of experience has been lost!`n",
			getsetting("pvpattlose", 15));
	output("You may begin fighting again tomorrow.");
	return $args['handled'];
}

?>
