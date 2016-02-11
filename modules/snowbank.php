<?php
// addnews ready
// mail ready
// translator ready

// based (loosely, very loosely) on core module bank.php
// modularized for Esoterra by Shannon Brown => SaucyWench -at- gmail -dot- com
// 8th October 2004
// ver 2.1 for Polareia Borealis (text changes only) 8th December 2004

require_once("common.php");
require_once("lib/systemmail.php");
require_once("lib/sanitize.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

function snowbank_getmoduleinfo(){
	$info = array(
		"name"=>"Snow Bank",
		"version"=>"2.0",
		"author"=>"E Stevens, JT Traub, S Brown",
		"category"=>"Village",
		"download"=>"core_module",
		"settings"=>array(
			"allowdep"=>"Allow deposit?,bool|0",
			"allowtx"=>"Allow Transfer?,bool|0",
			"bankloc"=>"Where does the bank appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
		"prefs"=>array(
			"giventoday"=>"Has the user given a gift today?,bool|0",
		)
	);
	return $info;
}

function snowbank_install(){
	module_addhook("changesetting");
	module_addhook("newday");
	module_addhook("village");
	return true;
}

function snowbank_uninstall(){
	return true;
}

function snowbank_dohook($hookname,$args){
	global $session;
	switch($hookname){
	case "newday":
		set_module_pref("giventoday",0,"snowbank");
		break;
	case "changesetting":
		if ($args['setting'] == "villagename") {
			if ($args['old'] == get_module_setting("bankloc")) {
				set_module_setting("bankloc", $args['new']);
			}
		}
		break;
	case "village":
		if ($session['user']['location'] == get_module_setting("bankloc")) {
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("B?The Snow Bank","runmodule.php?module=snowbank");
		}
		break;
	}
	return $args;
}

function snowbank_run(){
	global $session;
	$op = httpget("op");
	$allowdep=get_module_setting("allowdep");
	$allowtx=get_module_setting("allowtx");
	$giventoday=get_module_pref("giventoday");
	page_header("The Snow Bank");
	output("`^`c`bThe Snow Bank`b`c");
	$op = httpget('op');
	if ($op==""){
		checkday();
		output("`7You step through the darkened doorway, to find a one of Santa's Elves standing behind a counter.`n`n");
		output("`7\"`&Hello %s,`7\" he says with a smile.",$session['user']['sex']?"Madam":"Sir");
		if ($session['user']['goldinbank']>=0){
			output("`7\"`&You have %s gold invested with us at the moment. ",$session['user']['goldinbank']);
		}else{
			output("`7\"`&You have a `4debt`& of `^%s gold`7 to us at moment. ",abs($session['user']['goldinbank']));
		}
		output("How may I help you today?`7\"");
	}elseif($op=="give" && $giventoday==0){
		output("`7You inform the elf that you'd like to donate a gift to the poor.");
		output("`7The expression on the elf's face is pure gratitude as you make the offer.`n`n");
		output("`7He takes a small folder of cards from his drawer, and you select one to attach to your gift.`n`n");
		output("`7Placing it under the bank's small tree, you step back with a smile.`n`n");
		output("`7You feel really good about yourself!");
		apply_buff('bank',array("name"=>"Generosity","rounds"=>20,"defmod"=>1.02));
		set_module_pref("giventoday",1);
	}elseif($op=="give" && $giventoday==1){
		output("The elf smiles, as you place another gift under the tree.`n`n");
	}elseif($op=="transfer" && $allowtx){
		output("`7`bTransfer Money`b:`n");
		if ($session['user']['goldinbank']>=0){
			output("`7The elf tells you, \"`&You understand of course, you may only transfer `^%s`& gold for each level that the recipient has achieved.",getsetting("transferperlevel",25));
			$maxout = $session['user']['level']*getsetting("maxtransferout",25);
			output("And we ask that you transfer no more than `^%s`& gold each day.`7\"`n",$maxout);
			if ($session['user']['amountouttoday'] > 0) {
				output("`7He checks the book in front of him for a moment, \"`&It looks as though you've already transferred `^%s`& gold today.`7\"`n",$session['user']['amountouttoday']);
			}
			output_notl("`n");
			$preview = translate_inline("Preview Transfer");
			rawoutput("<form action='runmodule.php?module=snowbank&op=transfer2' method='POST'>");
			output("Transfer how much: ");
			rawoutput("<input name='amount' id='amount' width='5'>");
			output_notl("`n");
			output("To: ");
			rawoutput("<input name='to'>");
			output(" (partial names are ok, you will be asked to confirm the transaction before it occurs).`n");
			rawoutput("<input type='submit' class='button' value='$preview'></form>");
			rawoutput("<script language='javascript'>document.getElementById('amount').focus();</script>");
			addnav("","runmodule.php?module=snowbank&op=transfer2");
		}else{
			output("`7\"`7I'm sorry, I can't allow you to transfer to someone who is already in our debt, you understand.`7\"");
		}
	}elseif($op=="transfer2" && $allowtx){
		output("`7`bConfirm Transfer`b:`n");
		$string="%";
		$to = httppost('to');
		for ($x=0;$x<strlen($to);$x++){
			$string .= substr($to,$x,1)."%";
		}
		$sql = "SELECT name,login FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($string)."' AND locked=0 ORDER by login='$to' DESC, name='$to' DESC, login";
		$result = db_query($sql);
		$amt = abs((int)httppost('amount'));
		if (db_num_rows($result)==1){
			$row = db_fetch_assoc($result);
			$msg = translate_inline("Complete Transfer");
			rawoutput("<form action='runmodule.php?module=snowbank&op=transfer3' method='POST'>");
			output("`7Transfer `^%s`7 to `&%s`7.",$amt,$row['name']);
			rawoutput("<input type='hidden' name='to' value='".HTMLEntities($row['login'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."'><input type='hidden' name='amount' value='$amt'><input type='submit' class='button' value='$msg'></form>");
			addnav("","runmodule.php?module=snowbank&op=transfer3");
		}elseif(db_num_rows($result)>100){
			output("`7The elf smiles at you, and suggests that a search that broad would take all day, and that perhaps you could narrow it down for him a little.`n`n");
			$msg = translate_inline("Preview Transfer");
			rawoutput("<form action='runmodule.php?module=snowbank&op=transfer2' method='POST'>");
			output("Transfer how much: ");
			rawoutput("<input name='amount' id='amount' width='5' value='$amt'><br>");
			output("To: ");
			rawoutput("<input name='to' value='$to'>");
			output(" (partial names are ok, you will be asked to confirm the transaction before it occurs).`n");
			rawoutput("<input type='submit' class='button' value='$msg'></form>");
			rawoutput("<script language='javascript'>document.getElementById('amount').focus();</script>");
			addnav("","runmodule.php?module=snowbank&op=transfer2");
		}elseif(db_num_rows($result)>1){
			rawoutput("<form action='runmodule.php?module=snowbank&op=transfer3' method='POST'>");
			output("`7Transfer `^%s`7 to ",$amt);
			rawoutput("<select name='to' class='input'>");
			for ($i=0;$i<db_num_rows($result);$i++){
				$row = db_fetch_assoc($result);
				rawoutput("<option value=\"".HTMLEntities($row['login'], ENT_COMPAT, getsetting("charset", "ISO-8859-1"))."\">".full_sanitize($row['name'])."</option>");
			}
			$msg = translate_inline("Complete Transfer");
			rawoutput("</select><input type='hidden' name='amount' value='$amt'><input type='submit' class='button' value='$msg'></form>");
			addnav("","runmodule.php?module=snowbank&op=transfer3");
		}else{
			output("`7The elf's eyes dance with merriment below his green cap. \"`&Perhaps you'd care to try someone who's one of our customers, since I can't seem to find that name in my records.`7\"");
		}
	}elseif($op=="transfer3" && $allowtx){
		$amt = abs((int)httppost('amount'));
		$to = httppost('to');
		output("`7`bTransfer Completion`b`n");
		if ($session['user']['gold']+$session['user']['goldinbank']<$amt){
			output("`7The elf regards you with a smile, \"`&How can you transfer `^%s`& gold when our bank holds only  `^%s`& for you?`7\"",$amt,$session['user']['gold']+$session['user']['goldinbank']);
		}else{
			$sql = "SELECT name,acctid,level,transferredtoday FROM " . db_prefix("accounts") . " WHERE login='$to'";
			$result = db_query($sql);
			if (db_num_rows($result)==1){
				$row = db_fetch_assoc($result);
				$maxout = $session['user']['level']*getsetting("maxtransferout",25);
				$maxtfer = $row['level']*getsetting("transferperlevel",25);
				if ($session['user']['amountouttoday']+$amt > $maxout) {
					output("`7The elf regards you with a smile, \"`&Perhaps I did not explain clearly? We ask our customers to transfer no more than `^%s`& gold total per day.`7\"",$maxout);
				}else if ($maxtfer<$amt){
					output("`7The elf regards you with a smile, \"`&Perhaps I did not explain clearly? `&%s`& may only receive up to `^%s`& gold per day.`7\"",$row['name'],$maxtfer);
				}else if($row['transferredtoday']>=getsetting("transferreceive",3)){
					output("`7The elf regards you with a smile, \"`&Perhaps I did not explain clearly? `&%s`& has received too many transfers today, perhaps you will try tomorrow.`7\"",$row['name']);
				}else if($amt<(int)$session['user']['level']){
					output("`7The elf regards you with a smile, \"`&Perhaps I did not explain clearly? \"`&We ask all our customers to transfer at least as much as their level.`7\"");
				}else if($row['acctid']==$session['user']['acctid']){
					output("`7The elf regards you with a smile, \"`&I'd just as soon not transfer money from yourself to yourself. It's rather a funny thing to do, wouldn't you agree?`7\"");
				}else{
					debuglog("transferred $amt gold to", $row['acctid']);
					$session['user']['gold']-=$amt;
					if ($session['user']['gold']<0){
						//withdraw in case they don't have enough on hand.
						$session['user']['goldinbank']+=$session['user']['gold'];
						$session['user']['gold']=0;
					}
					$session['user']['amountouttoday']+= $amt;
					$sql = "UPDATE ". db_prefix("accounts") . " SET goldinbank=goldinbank+$amt,transferredtoday=transferredtoday+1 WHERE acctid='{$row['acctid']}'";
					db_query($sql);
					output("`7The elf smiles, \"`&The transfer has been completed!`7\"");
					$subj = array("`^You have received a money transfer!`0");
					$body = array("`&%s`7 has transferred `^%s`7 gold to your bank account!",$session['user']['name'],$amt);
					systemmail($row['acctid'],$subj,$body);
				}
			}else{
				output("`7The elf looks up from his book and apologizes, \"`&I am sorry, I don't believe I caught that. Could you tell me again what you would like to transfer?`7\"");
			}
		}
	}elseif($op=="deposit" && $allowdep){
		output("`0");
		rawoutput("<form action='runmodule.php?module=snowbank&op=depositfinish' method='POST'>");
		$balance = translate_inline("`7The elf says, \"`&You have a balance of `^%s`& gold in the bank.`7\"`n");
		$debt = translate_inline("`7The elf says, \"`&You have a `\$debt`& of `^%s`& gold to the bank.`7\"`n");
		output_notl($session['user']['goldinbank']>=0?$balance:$debt,abs($session['user']['goldinbank']));
		output("`7Searching through all your pockets and pouches, you quackalate that you currently have `^%s`7 gold on hand.`n`n", $session['user']['gold']);
		$dep = translate_inline("`^Deposit how much?");
		$pay = translate_inline("`^Pay off how much?");
		output_notl($session['user']['goldinbank']>=0?$dep:$pay);
		$dep = translate_inline("Deposit");
		rawoutput(" <input id='input' name='amount' width=5 > <input type='submit' class='button' value='$dep'>");
		output("`n`iEnter 0 or nothing to deposit it all`i");
		rawoutput("</form>");
		rawoutput("<script language='javascript'>document.getElementById('input').focus();</script>");
		addnav("","runmodule.php?module=snowbank&op=depositfinish");
	}elseif($op=="depositfinish" && $allowdep){
		$amount = abs((int)httppost('amount'));
		if ($amount==0){
			$amount=$session['user']['gold'];
		}
		$notenough = translate_inline("`\$ERROR: Not enough gold in hand to deposit.`n`n`^You plunk your `&%s`^ gold on the counter and declare that you would like to deposit all `&%s`^ gold of it.`n`n`7The elf smiles at you and suggests you recount your money.");
		$depositdebt = translate_inline("`7The elf records your deposit of `^%s `7gold in the book before him. \"`&Thank you, `&%s`&.  You now have a debt of `\$%s`& gold to the bank and `^%s`& gold in hand.`7\"");
		$depositbalance= translate_inline("`7The elf records your deposit of `^%s `7gold in the book before him. \"`&Thank you, `&%s`&.  You now have a balance of `^%s`& gold in the bank and `^%s`& gold in hand.`7\"");
		if ($amount>$session['user']['gold']){
			output_notl($notenough,$session['user']['gold'],$amount);
		}else{
			debuglog("deposited " . $amount . " gold in the bank");
			$session['user']['goldinbank']+=$amount;
			$session['user']['gold']-=$amount;
			output_notl($session['user']['goldinbank']>=0?$depositbalance:$depositdebt,$amount,$session['user']['name'], abs($session['user']['goldinbank']),$session['user']['gold']);
		}
	}elseif($op=="borrow"){
		$maxborrow = $session['user']['level']*getsetting("borrowperlevel",20);
		$borrow = translate_inline("Borrow");
		$balance = translate_inline("`7The elf scans through the book before him, \"`&You have a balance of `^%s`& gold in the bank.`7\"`n");
		$debt = translate_inline("`7The elf scans through the book before him, \"`&You have a `\$debt`& of `^%s`& gold to the bank.`7\"`n");
		rawoutput("<form action='runmodule.php?module=snowbank&op=withdrawfinish' method='POST'>");
		output_notl($session['user']['goldinbank']>=0?$balance:$debt,abs($session['user']['goldinbank']));
		output("`7\"`&How much would you like to borrow `&%s`&?  At your level, you may borrow up to a total of `^%s`& from the bank.`7\"`n`n",$session['user']['name'], $maxborrow);
		rawoutput(" <input id='input' name='amount' width=5 > <input type='hidden' name='borrow' value='x'><input type='submit' class='button' value='$borrow'>");
		output("`n(Money will be withdrawn until you have none left, the remainder will be borrowed)");
		rawoutput("</form>");
		rawoutput("<script language='javascript'>document.getElementById('input').focus();</script>");
		addnav("","runmodule.php?module=snowbank&op=withdrawfinish");
	}elseif($op=="withdraw"){
		$withdraw = translate_inline("Withdraw");
		$balance = translate_inline("`7The elf scans through the book before him, \"`&You have a balance of `^%s`& gold in the bank.`7\"`n");
		$debt = translate_inline("`7The elf scans through the book before him, \"`&You have a `\$debt`& of `^%s`& gold in the bank.`7\"`n");
		rawoutput("<form action='runmodule.php?module=snowbank&op=withdrawfinish' method='POST'>");
		output_notl($session['user']['goldinbank']>=0?$balance:$debt,abs($session['user']['goldinbank']));
		output("`7\"`&How much would you like to withdraw `&%s`&?\"`n`n",$session['user']['name']);
		rawoutput("<input id='input' name='amount' width=5 > <input type='submit' class='button' value='$withdraw'>");
		output("`n`iEnter 0 or nothing to withdraw it all`i");
		rawoutput("</form>");
		rawoutput("<script language='javascript'>document.getElementById('input').focus();</script>");
		addnav("","runmodule.php?module=snowbank&op=withdrawfinish");
	}elseif($op=="withdrawfinish"){
		$amount=abs((int)httppost('amount'));
		if ($amount==0){
			$amount=abs($session['user']['goldinbank']);
		}
		if ($amount>$session['user']['goldinbank'] && httppost('borrow')=="") {
			output("`\$ERROR: Not enough gold in the bank to withdraw.`^`n`n");
			output("`7Having been informed that you have `^%s`7 gold in your account, you declare that you would like to withdraw all `^%s`7 of it.`n`n", $session['user']['goldinbank'], $amount);
			output("The elf smiles at you and suggests you rethink your request. You realize your mistake and think you should try again.");
		}else if($amount>$session['user']['goldinbank']){
			$lefttoborrow = $amount;
			$didwithdraw = 0;
			$maxborrow = $session['user']['level']*getsetting("borrowperlevel",20);
			if ($lefttoborrow<=$session['user']['goldinbank']+$maxborrow){
				if ($session['user']['goldinbank']>0){
					output("`7You withdraw your remaining `^%s`7 gold.", $session['user']['goldinbank']);
					$lefttoborrow-=$session['user']['goldinbank'];
					$session['user']['gold']+=$session['user']['goldinbank'];
					$session['user']['goldinbank']=0;
					debuglog("withdrew $amount gold from the bank");
					$didwithdraw = 1;
				}
				if ($lefttoborrow-$session['user']['goldinbank'] > $maxborrow){
					if ($didwithdraw) {
						output("`7Additionally, you ask to borrow `^%s`7 gold.", $leftoborrow);
					} else {
						output("`7You ask to borrow `^%s`7 gold.", $lefttoborrow);
					}
					output("The elf looks up your account and informs you that you may only borrow up to `^%s`7 gold.", $maxborrow);
				}else{
					if ($didwithdraw) {
						output("`7Additionally, you borrow `^%s`7 gold.", $lefttoborrow);
					} else {
						output("`7You borrow `^%s`7 gold.", $lefttoborrow);
					}
					$session['user']['goldinbank']-=$lefttoborrow;
					$session['user']['gold']+=$lefttoborrow;
					debuglog("borrows $lefttoborrow gold from the bank");
					output("`7The elf records your withdrawal of `^%s `7gold in the book before him. \"`&Thank you, `&%s`&.  You now have a debt of `\$%s`& gold to the bank and `^%s`& gold in hand.`7\"", $amount,$session['user']['name'], abs($session['user']['goldinbank']),$session['user']['gold']);
				}
			}else{
				output("`7Considering the `^%s`7 gold in your account, you ask to borrow `^%s`7. The elf looks up your account and informs you that you may only borrow up to `^%s`7 gold at your level.", $session['user']['goldinbank'], $lefttoborrow-$session['user']['goldinbank'], $maxborrow);
			}
		}else{
			$session['user']['goldinbank']-=$amount;
			$session['user']['gold']+=$amount;
			debuglog("withdrew $amount gold from the bank");
			output("`7The elf records your withdrawal of `^%s `7gold in the book before him. \"`&Thank you, `&%s`&.  You now have a balance of `^%s`& gold in the bank and `^%s`& gold in hand.`7\"", $amount,$session['user']['name'], abs($session['user']['goldinbank']),$session['user']['gold']);
		}
	}
	villagenav();
	addnav("Money");
	if ($session['user']['goldinbank']>=0){
		addnav("W?Withdraw","runmodule.php?module=snowbank&op=withdraw");
		if ($allowdep)
			addnav("D?Deposit","runmodule.php?module=snowbank&op=deposit");
		if (getsetting("borrowperlevel",20))
			addnav("L?Take out a Loan","runmodule.php?module=snowbank&op=borrow");
	}else{
		if ($allowdep)
			addnav("D?Pay off Debt","runmodule.php?module=snowbank&op=deposit");
		if (getsetting("borrowperlevel",20))
			addnav("L?Borrow More","runmodule.php?module=snowbank&op=borrow");
	}
	if ($allowtx){
		if ($session['user']['level']>=getsetting("mintransferlev",3) ||
				$session['user']['dragonkills']>0){
			addnav("M?Transfer Money","runmodule.php?module=snowbank&op=transfer");
		}
	}
	addnav("Give Gift","runmodule.php?module=snowbank&op=give");
	page_footer();
}

?>
