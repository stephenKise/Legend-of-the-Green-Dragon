<?php
		$to = (int)httpget('to');
		if ($to>0){
			output("`%%s`7 accepts your application, files it in her out box, and folds her hands on the desk, staring at you.",$registrar);
			output("You stand there staring blankly back at her for a few minutes before she suggests that perhaps you'd like to take a seat in the waiting area.");

			addnav("Return to the Lobby","clan.php");
			addnav("Waiting Area","clan.php?op=waiting");
			$session['user']['clanid']=$to;
			$session['user']['clanrank']=CLAN_APPLICANT;
			$session['user']['clanjoindate']=date("Y-m-d H:i:s");
			$sql = "SELECT acctid FROM " . db_prefix("accounts") . " WHERE clanid='{$session['user']['clanid']}' AND clanrank>=".CLAN_OFFICER;
			$result = db_query($sql);
			$sql = "DELETE FROM . ".  db_prefix("mail") . " WHERE msgfrom=0 AND seen=0 AND subject='".serialize($apply_subj)."'";
			db_query($sql);
			while ($row = db_fetch_assoc($result)){
				$msg = array("`^You have a new clan applicant!  `&%s`^ has completed a membership application for your clan!",$session['user']['name']);
				systemmail($row['acctid'],$apply_subj,$msg);
			}

			// send reminder mail if clan of choice has a description

			$sql = "SELECT * FROM " . db_prefix("clans") . " WHERE clanid='$to'";
			$res = db_query_cached($sql, "clandata-$to", 3600);
			$row = db_fetch_assoc($res);

			if ( nltoappon($row['clandesc']) != "" ) {

				$subject = "Clan Application Reminder";
				$mail = "`&Did you remember to read the description of the clan of your choice before applying?  Note that some clans may have requirements that you have to fulfill before you can become a member.  If you are not accepted into the clan of your choice anytime soon, it may be because you have not fulfilled these requirements.  For your convenience, the description of the clan you are applying to is reproduced below.`n`n`c`#%s`@ <`^%s`@>`0`c`n%s";

				systemmail($session['user']['acctid'],array($subject),array($mail, $row['clanname'], $row['clanshort'], nltoappon($row['clandesc'])));
			}
		}else{
			$sql = "SELECT MAX(" . db_prefix("clans") . ".clanid) AS clanid,MAX(clanname) AS clanname,count(" . db_prefix("accounts") . ".acctid) AS c FROM " . db_prefix("clans") . " INNER JOIN " . db_prefix("accounts") . " ON " . db_prefix("clans") . ".clanid=" . db_prefix("accounts") . ".clanid WHERE " . db_prefix("accounts") . ".clanrank > ".CLAN_APPLICANT." GROUP BY " . db_prefix("clans") . ".clanid ORDER BY c DESC";
			$result = db_query($sql);
			if (db_num_rows($result)>0){
				output("`7You ask %s`7 for a clan membership application form.",$registrar);
				output("She opens a drawer in her desk and pulls out a form.  It contains only two lines: Name and Clan Name.");
				output("You furrow your brow, not sure if you really like having to deal with all this red tape, and get set to concentrate really hard in order to complete the form.");
				output("Noticing your attempt to write on the form with your %s, %s`7 claims the form back from you, writes %s`7 on the first line, and asks you the name of the clan that you'd like to join:`n`n",$session['user']['weapon'],$registrar,$session['user']['name']);
				for ($i=0;$i<db_num_rows($result);$i++){
					$row = db_fetch_assoc($result);
					if ($row['c']==0){
						$sql = "DELETE FROM " . db_prefix("clans") . " WHERE clanid={$row['clanid']}";
						db_query($sql);
					}else{
/*//*/					$row = modulehook("clan-applymember", $row);
/*//*/					if (isset($row['handled']) && $row['handled']) continue;
						$memb_n = translate_inline("(%s members)");
						$memb_1 = translate_inline("(%s member)");
						if ($row['c'] == 1) {
							$memb = sprintf($memb_1, $row['c']);
						} else {
							$memb = sprintf($memb_n, $row['c']);
						}
						output_notl("&#149; <a href='clan.php?op=apply&to=%s'>%s</a> %s`n",
								$row['clanid'],
								full_sanitize(htmlentities($row['clanname'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))),
								$memb, true);
						addnav("","clan.php?op=apply&to={$row['clanid']}");
					}
				}
				addnav("Return to the Lobby","clan.php");
			}else{
				output("`7You ask %s`7 for a clan membership application form.",$registrar);
				output("She stares at you blankly for a few moments, then says, \"`5Sorry pal, no one has had enough gumption to start up a clan yet.  Maybe that should be you, eh?`7\"");
				addnav("Apply for a New Clan","clan.php?op=new");
				addnav("Return to the Lobby","clan.php");
			}
		}
?>