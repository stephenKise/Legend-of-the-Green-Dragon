<?php
	if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
		$clanname = httppost('clanname');
		if ($clanname) $clanname = full_sanitize($clanname);
		$clanshort = httppost('clanshort');
		if ($clanshort) $clanshort = full_sanitize($clanshort);
		if ($clanname>"" && $clanshort>""){
			$sql = "UPDATE " . db_prefix("clans") . " SET clanname='$clanname',clanshort='$clanshort' WHERE clanid='$detail'";
			output("Updating clan names`n");
			db_query($sql);
			invalidatedatacache("clandata-$detail");
		}
		if (httppost('block')>""){
			$blockdesc = translate_inline("Description blocked for inappropriate usage.");
			$sql = "UPDATE " . db_prefix("clans") . " SET descauthor=4294967295, clandesc='$blockdesc' where clanid='$detail'";
			output("Blocking public description`n");
			db_query($sql);
			invalidatedatacache("clandata-$detail");
		}elseif (httppost('unblock')>""){
			$sql = "UPDATE " . db_prefix("clans") . " SET descauthor=0, clandesc='' where clanid='$detail'";
			output("UNblocking public description`n");
			db_query($sql);
			invalidatedatacache("clandata-$detail");
		}
	}
	$sql = "SELECT * FROM " . db_prefix("clans") . " WHERE clanid='$detail'";
	$result1 = db_query_cached($sql, "clandata-$detail", 3600);
	$row1 = db_fetch_assoc($result1);
	if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
		rawoutput("<div id='hidearea'>");
		rawoutput("<form action='clan.php?detail=$detail' method='POST'>");
		addnav("","clan.php?detail=$detail");
		output("Superuser / Moderator renaming:`n");
		output("Long Name: ");
		rawoutput("<input name='clanname' value=\"".htmlentities($row1['clanname'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" maxlength=50 size=50>");
		output("`nShort Name: ");
		rawoutput("<input name='clanshort' value=\"".htmlentities($row1['clanshort'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\" maxlength=5 size=5>");
		output_notl("`n");
		$save = translate_inline("Save");
		rawoutput("<input type='submit' class='button' value=\"$save\">");
		$snu = htmlentities(translate_inline("Save & UNblock public description"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
		$snb = htmlentities(translate_inline("Save & Block public description"), ENT_COMPAT, getsetting("charset", "ISO-8859-1"));
		if ($row1['descauthor']=="4294967295")
			rawoutput("<input type='submit' name='unblock' value=\"$snu\" class='button'>");
		else
			rawoutput("<input type='submit' name='block' value=\"$snb\" class='button'>");
		rawoutput("</form>");
		rawoutput("</div>");
		rawoutput("<script language='JavaScript'>var hidearea = document.getElementById('hidearea');hidearea.style.visibility='hidden';hidearea.style.display='none';</script>",true);
		$e = translate_inline("Edit Clan Info");
		rawoutput("<a href='#' onClick='hidearea.style.visibility=\"visible\"; hidearea.style.display=\"inline\"; return false;'>$e</a>",true);
		output_notl("`n");
	}

	output_notl(nltoappon($row1['clandesc']));
	if ( nltoappon($row1['clandesc']) != "" ) output ("`n`n");
	output("`0This is the current clan membership of %s < %s >:`n",$row1['clanname'],$row1['clanshort']);
	page_header("Clan Membership for %s &lt;%s&gt;", full_sanitize($row1['clanname']), full_sanitize($row1['clanshort']));
	addnav("Clan Options");
	$rank = translate_inline("Rank");
	$name = translate_inline("Name");
	$dk = translate_inline("Dragon Kills");
	$jd = translate_inline("Join Date");
	rawoutput("<table border='0' cellpadding='2' cellspacing='0'>");
	rawoutput("<tr class='trhead'><td>$rank</td><td>$name</td><td>$dk</td><td>$jd</td></tr>");
	$i=0;
	$sql = "SELECT acctid,name,login,clanrank,clanjoindate,dragonkills FROM " . db_prefix("accounts") . " WHERE clanid=$detail ORDER BY clanrank DESC,clanjoindate";
	$result = db_query($sql);
	$tot = 0;
	//little hack with the hook...can't think of any other way
	$ranks = array(CLAN_APPLICANT=>"`!Applicant`0",CLAN_MEMBER=>"`#Member`0",CLAN_OFFICER=>"`^Officer`0",CLAN_LEADER=>"`&Leader`0", CLAN_FOUNDER=>"`\$Founder");
	$args = modulehook("clanranks", array("ranks"=>$ranks, "clanid"=>$detail));
	$ranks = translate_inline($args['ranks']);
	//end
	while ($row=db_fetch_assoc($result)){
		$i++;
		$tot += $row['dragonkills'];
		rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
		rawoutput("<td>");
		output_notl($ranks[$row['clanrank']]); //translated earlier
		rawoutput("</td><td>");
		$link = "bio.php?char=".$row['acctid']."&ret=".urlencode($_SERVER['REQUEST_URI']);
		rawoutput("<a href='$link'>");
		addnav("", $link);
		output_notl("`&%s`0", $row['name']);
		rawoutput("</a>");
		rawoutput("</td><td align='center'>");
		output_notl("`\$%s`0", $row['dragonkills']);
		rawoutput("</td><td>");
		output_notl("`3%s`0", $row['clanjoindate']);
		rawoutput("</td></tr>");
	}
	rawoutput("</table>");
	output("`n`n`^This clan has a total of `\$%s`^ dragon kills.",$tot);
?>