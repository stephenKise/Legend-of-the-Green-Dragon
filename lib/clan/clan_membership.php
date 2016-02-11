<?php
		addnav("Clan Hall","clan.php");
		addnav("Clan Options");
		output("This is your current clan membership:`n");
		$setrank = httpget('setrank');
		$whoacctid = (int)httpget('whoacctid');
		if ($setrank>"") {
			$sql="SELECT name,login,clanrank FROM ".db_prefix("accounts")." WHERE acctid=$whoacctid LIMIT 1";
			$result=db_query($sql);
			$row=db_fetch_assoc($result);
			$who = $row['login'];
			$whoname = $row['name'];
			if ($setrank>""){
				$args = modulehook("clan-setrank", array("setrank"=>$setrank, "login"=>$who, "name"=>$whoname, "acctid"=>$whoacctid, "clanid"=>$session['user']['clanid'], "oldrank"=>$row['clanrank']));
				if (!(isset($args['handled']) && $args['handled'])) {
					$sql = "UPDATE " . db_prefix("accounts") . " SET clanrank=GREATEST(0,least({$session['user']['clanrank']},$setrank)) WHERE login='$who'";
					db_query($sql);
					debuglog("Player {$session['user']['name']} changed rank of {$whoname} to {$setrank}.", $whoacctid);
				}
			}
		}
		$remove = httpget('remove');
		if ($remove>""){
			$sql = "SELECT name,login,clanrank FROM " . db_prefix("accounts") . " WHERE acctid='$remove'";
			$row = db_fetch_assoc(db_query($sql));
			$args = modulehook("clan-setrank", array("setrank"=>0, "login"=>$row['login'], "name"=>$row['name'], "acctid"=>$remove, "clanid"=>$session['user']['clanid'], "oldrank"=>$row['clanrank']));
			$sql = "UPDATE " . db_prefix("accounts") . " SET clanrank=".CLAN_APPLICANT.",clanid=0,clanjoindate='0000-00-00 00:00:00' WHERE acctid='$remove' AND clanrank<={$session['user']['clanrank']}";
			db_query($sql);
			debuglog("Player {$session['user']['name']} removed player {$row['login']} from {$claninfo['clanname']}.", $remove);
			//delete unread application emails from this user.
			//breaks if the applicant has had their name changed via
			//dragon kill, superuser edit, or lodge color change
			require_once("lib/safeescape.php");
			$subj = safeescape(serialize(array($apply_short, $row['name'])));
			$sql = "DELETE FROM " . db_prefix("mail") . " WHERE msgfrom=0 AND seen=0 AND subject='$subj'";
			db_query($sql);
		}
		$sql = "SELECT name,login,acctid,clanrank,laston,clanjoindate,dragonkills,level FROM " . db_prefix("accounts") . " WHERE clanid={$claninfo['clanid']} ORDER BY clanrank DESC ,dragonkills DESC,level DESC,clanjoindate";
		$result = db_query($sql);
		rawoutput("<table border='0' cellpadding='2' cellspacing='0'>");
		$rank = translate_inline("Rank");
		$name = translate_inline("Name");
		$lev = translate_inline("Level");
		$dk = translate_inline("Dragon Kills");
		$jd = translate_inline("Join Date");
		$lo = translate_inline("Last On");
		$ops = translate_inline("Operations");
		$promote = translate_inline("Promote");
		$demote = translate_inline("Demote");
		$stepdown = translate_inline("`\$Step down as founder");
		$remove = translate_inline("Remove From Clan");
		$confirm = translate_inline("Are you sure you wish to remove this member from your clan?");
		rawoutput("<tr class='trhead'><td>$rank</td><td>$name</td><td>$lev</td><td>$dk</td><td>$jd</td><td>$lo</td>".($session['user']['clanrank']>CLAN_MEMBER?"<td>$ops</td>":"")."</tr>",true);
		$i=0;
		$tot = 0;
		require_once("lib/clan/func.php");
		while ($row=db_fetch_assoc($result)){
			$i++;
			$tot += $row['dragonkills'];
			rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
			rawoutput("<td>");
			output_notl($ranks[$row['clanrank']]);
			rawoutput("</td><td>");
			$link = "bio.php?char=".$row['acctid']."&ret=".urlencode($_SERVER['REQUEST_URI']);
			rawoutput("<a href='$link'>", true);
			addnav("", $link);
			output_notl("`&%s`0", $row['name']);
			rawoutput("</a>");
			rawoutput("</td><td align='center'>");
			output_notl("`^%s`0",$row['level']);
			rawoutput("</td><td align='center'>");
			output_notl("`\$%s`0",$row['dragonkills']);
			rawoutput("</td><td>");
			output_notl("`3%s`0",$row['clanjoindate']);
			rawoutput("</td><td>");
			output_notl("`#%s`0",reltime(strtotime($row['laston'])));
			rawoutput("</td>");
			if ($session['user']['clanrank']>CLAN_MEMBER){
				rawoutput("<td>");
				if ($row['clanrank']<$session['user']['clanrank'] && $row['clanrank']<CLAN_FOUNDER){
					rawoutput("[ <a href='clan.php?op=membership&setrank=".clan_nextrank($ranks,$row['clanrank'])."&who=".rawurlencode($row['login'])."&whoname=".rawurlencode($row['name'])."&whoacctid=".$row['acctid']."'>$promote</a> | ");
					addnav("","clan.php?op=membership&setrank=".clan_nextrank($ranks,$row['clanrank'])."&who=".rawurlencode($row['login'])."&whoname=".rawurlencode($row['name'])."&whoacctid=".$row['acctid']);
				}else{
					output_notl("[ `)%s`0 | ", $promote);
				}
				if ($row['clanrank']<=$session['user']['clanrank'] && $row['clanrank']>CLAN_APPLICANT && $row['login']!=$session['user']['login'] && clan_previousrank($ranks,$row['clanrank']) > 0){
					rawoutput("<a href='clan.php?op=membership&setrank=".clan_previousrank($ranks,$row['clanrank'])."&whoacctid=".$row['acctid']."'>$demote</a> | ");
					addnav("","clan.php?op=membership&setrank=".clan_previousrank($ranks,$row['clanrank'])."&whoacctid=".$row['acctid']);
				}elseif ($row['clanrank']==CLAN_FOUNDER && $row['clanrank']>CLAN_APPLICANT && $row['login']==$session['user']['login']){
					output_notl("<a href='clan.php?op=membership&setrank=".clan_previousrank($ranks,$row['clanrank'])."&whoacctid=".$row['acctid']."'>$stepdown</a> | ",true);
					addnav("","clan.php?op=membership&setrank=".clan_previousrank($ranks,$row['clanrank'])."&whoacctid=".$row['acctid']);
				} else {
					output_notl("`)%s`0 | ", $demote);
				}
				if ($row['clanrank'] <= $session['user']['clanrank'] && $row['login']!=$session['user']['login']){
					rawoutput("<a href='clan.php?op=membership&remove=".$row['acctid']."' onClick=\"return confirm('$confirm');\">$remove</a> ]");
					addnav("","clan.php?op=membership&remove=".$row['acctid']);
				}else{
					output_notl("`)%s`0 ]", $remove);
				}
				rawoutput("</td>");
			}
			rawoutput("</tr>");
		}
		rawoutput("</table>");
		output("`n`n`^This clan has a total of `\$%s`^ dragon kills.",$tot);
?>
