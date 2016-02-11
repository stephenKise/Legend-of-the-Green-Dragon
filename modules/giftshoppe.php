<?php
// translator ready
// addnews ready
// mail ready

/*
 * Date:	Dec 11, 2004
 * Version:	1.0
 * Author:	JT Traub
 * Email:	jtraub@dragoncat.net
 * Purpose:	Holiday gifts
 */

function giftshoppe_getmoduleinfo(){
	$info = array(
		"name"=>"Holiday Gift Shop",
		"author"=>"JT Traub",
		"category"=>"Village",
		"download"=>"core_module",
		"requires"=>array(
			"stocking"=>"1.0|By Talisman and Robert, from the core download",
		),
		"settings"=>array(
			"Holiday Gifts Settings,title",
			"date"=>"When is the holiday? (mm-dd),|12-25",
			"daysprior"=>"Days before the holiday that you can buy?,int|14",
			"extragift"=>"Is there an extra gift for everyone?,bool|1",
			"extragiver"=>"Who sends the anonymous gifts?,|`&Santa`0",
			"allpresents"=>"How many presents have been bought?,viewonly"
		),
		"prefs"=>array(
			"Holiday Gifts User Preferences,title",
			"canedit"=>"Has access to the gifts editor,bool|0",
			"gottengift"=>"Has the player gotten the extra gift,bool|0"
		),
		"version"=>"1.0"
	);
	return $info;
}

function giftshoppe_install(){
	require_once("lib/tabledescriptor.php");

	// See if we need to insert the basic gifts
	$populate = false;
	if (!db_table_exists(db_prefix("giftlist"))) {
		$populate = true;
	}

	$giftsdesc = array(
		'giftid'=> array('name'=>'giftid', 'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'),
		'giftfrom' => array('name'=>'giftfrom', 'type'=>'int(11) unsigned',
			'default'=>'0'),
		'giftto' => array('name'=>'giftto', 'type'=>'int(11) unsigned',
			'default'=>'0'),
		'name'=> array('name'=>'name', 'type'=>'text'),
		'note' => array('name'=>'note', 'type'=>'text'),
		'anonymous' => array('name'=>'anonymous', 'type'=>'int(4) unsigned',
			'default'=>'0'),
		'key-PRIMARY' => array('name'=>'PRIMARY', 'type'=>'primary key',
			'unique'=>'1', 'columns'=>'giftid'),
		'key-giftto'=>array('name'=>'giftto', 'type'=>'key',
			'columns'=>'giftto'));

	$giftlistdesc = array(
		'giftid'=> array('name'=>'giftid', 'type'=>'int(11) unsigned',
			'extra'=>'auto_increment'),
		'name'=> array('name'=>'name', 'type'=>'text'),
		'gold'=> array('name'=>'gold', 'type'=>'int(11) unsigned',
			'default'=>'0'),
		'gems'=> array('name'=>'gems', 'type'=>'int(11) unsigned',
			'default'=>'0'),
		'key-PRIMARY' => array('name'=>'PRIMARY', 'type'=>'primary key',
			'unique'=>'1', 'columns'=>'giftid'));
	synctable(db_prefix("gifts"), $giftsdesc, true);
	synctable(db_prefix("giftlist"), $giftlistdesc, true);

	if ($populate) {
		// Create some basic gifts
		$basegifts = array(
			"INSERT INTO " . db_prefix("giftlist") . " VALUES (0, '`QHoliday Fruitcake`0', 50, 0)",
			"INSERT INTO " . db_prefix("giftlist") . " VALUES (0, '`@Plush Dragon`0', 100, 0)",
			"INSERT INTO " . db_prefix("giftlist") . " VALUES (0, '`&Comfy Pillow`0', 150, 0)",
			"INSERT INTO " . db_prefix("giftlist") . " VALUES (0, '`2Subscription to \"`@Dragon Killers Weekly`2\"`0', 200, 0)",
			"INSERT INTO " . db_prefix("giftlist") . " VALUES (0, '`!Fancy Quill Pen`0', 0, 1)"
		);
		while (list($key,$sql)=each($basegifts)){
			db_query($sql);
		}
	}

	// Install the hooks.
	module_addhook("fireplace");
	module_addhook("changesetting");
	module_addhook("superuser");
	module_addhook("village");
	return true;
}

function giftshoppe_uninstall() {
	debug("Dropping gifts table");
	$sql = "DROP TABLE IF EXISTS " . db_prefix("gifts");
	db_query($sql);
	debug("Dropping giftlist table");
	$sql = "DROP TABLE IF EXISTS " . db_prefix("giftlist");
	db_query($sql);

	return true;
}

function giftshoppe_dohook($hookname,$args) {
	global $session;

	$holiday = get_module_setting("date") . "-" . date("Y");
	$holiday = str_replace("-", "/", $holiday);
	$htime = strtotime($holiday);
	$days = ceil(($htime - time())/86400);

	switch($hookname) {
	case "fireplace":
		if ($days > 30) break;
		if ($days > 3 && get_module_pref("gottengift")) {
			set_module_pref("gottengift", 0);
		}
		$id = $session['user']['acctid'];
		$sql = "SELECT count(*) AS count FROM " . db_prefix("gifts") . " WHERE giftto='$id'";
		$row = db_fetch_assoc(db_query($sql));
		$count = $row['count'];
		if (get_module_setting("extragift") && !get_module_pref("gottengift")){
			$count++;
		}
		if ($days > -3) {
			output("`7Under the tree, you see a pile of presents.");
			if ($count == 0) {
				output("Unfortunately, `\$none`7 of the gifts have your name on them.`n`n");
			} elseif ($count == 1) {
				output("One of the gifts has `@YOUR`7 name on it!`n`n");
			} else {
				output("`^%s`7 of the gifts have `@YOUR`7 name on them!`n`n", $count);
			}
		}
		if ($days > -3 && $days < 1) {
			if ($count) {
				addnav("Open Presents");
				addnav("Open A Present",
						"runmodule.php?module=giftshoppe&op=open");
			}
		} elseif ($days == 1) {
			if ($count > 1) {
				output("You look at the gifts longingly, but content yourself knowing that you'll be able to open them tomorrow!`n`n");
			} elseif ($count > 0) {
				output("You look at the gift longingly, but content yourself knowing that you'll be able to open it tomorrow!`n`n");
			}
		} elseif ($days > 1) {
			if ($count > 1) {
				output("You look at the gifts longingly, but you know that you must wait %s days, until Christmas, to open them.`n`n", $days);
			} elseif ($count > 0) {
				output("You look at the gift longingly, but you know that you must wait %s days, until Christmas, to open it.`n`n", $days);
			}
		}
		break;
	case "village":
		// If the gift shoppe isn't open, don't show it.
		if ($days <= get_module_setting("daysprior") && $days >= 0) {
			tlschema($args['schemas']['marketnav']);
			addnav($args['marketnav']);
			tlschema();
			addnav("Ye Olde Gifte Shoppe", "runmodule.php?module=giftshoppe");
		}
		break;
	case "superuser":
		if (get_module_pref("canedit")) {
			addnav("Module Configurations");
			// Stick the admin=true on so that when we call runmodule it'll
			// work to let us edit gifts even when the module is deactivated.
			addnav("Gifts Editor",
					"runmodule.php?module=giftshoppe&op=editor&admin=true");
		}
		break;
	case "changesetting":
		if ($args['setting'] == "date" && $args['module']=="giftshoppe") {
			// Someone changed the holiday date, remove the setting that
			// people already picked up their 'extra' gift for this holiday.
			$sql = "DELETE FROM " . db_prefix("module_userprefs") . " WHERE modulename='giftshoppe' AND setting='gottengift'";
			db_query($sql);
		}
		break;
	}
	return $args;
}

function giftshoppe_run(){
	global $session;
	$op = httpget('op');
	if ($op =="editor"){
		giftshoppe_editor();
	} elseif ($op == "open") {
		page_header("Presents by the Fireplace");
		addnav("Return to Fireplace", "runmodule.php?module=stocking");
		output("`&You wander over to the pile of presents under the tree and pick out one of the ones with your name on it.`n`n");

		$id = $session['user']['acctid'];
		$sql = "SELECT count(*) AS count FROM " . db_prefix("gifts") . " WHERE giftto='$id'";
		$row = db_fetch_assoc(db_query($sql));
		$count = $row['count'];

		// Open all non-game generated gifts first
		if ($count >= 1) {
			$sql = "SELECT * FROM " . db_prefix("gifts") . " WHERE giftto='$id' ORDER BY RAND(".e_rand().") LIMIT 1";
			$row = db_fetch_assoc(db_query($sql));
			$gid = $row['giftid'];
			$row['giftto'] = $session['user']['name'];
			if ($row['anonymous']) {
				$row['giftfrom'] = get_module_setting('extragiver');
			} else {
				$fid = $row['giftfrom'];
				$sql1 = "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$fid'";
				$row1 = db_fetch_assoc(db_query($sql1));
				$row['giftfrom'] = $row1['name'];
				if (!$row['giftfrom'])
					$row['giftfrom'] = get_module_setting('extragiver');
			}
			// Okay, delete it from the database
			$sql = "DELETE FROM " . db_prefix("gifts") . " WHERE giftid='$gid'";
			db_query($sql);
			$count--;
		} elseif (get_module_setting("extragift") && !get_module_pref("gottengift")) {
			set_module_pref("gottengift", 1);
			// Select a random gift
			$sql1 = "SELECT name FROM " . db_prefix("giftlist") . " ORDER BY RAND(".e_rand().") LIMIT 1";
			$grow = db_fetch_assoc(db_query($sql1));
			$row = array(
				'giftfrom'=>get_module_setting('extragiver'),
				'giftto'=>$session['user']['name'],
				'name'=>$grow['name'],
				'note'=>"Happy Holidays and a Happy New Year!"
			);
		} else {
			output("There are no more gifts under the tree for you.");
			page_footer();
		}

		$papers = array(
			"`7metallic `@green`7",
			"`7metallic `!blue`7",
			"`\$red`7 and `@green`7",
			"`!blue`7 and `&white`7",
			"`!m`@u`#l`\$t`%i`^c`&o`!l`4o`qr`7 shimmering",
			"`&Snowflake covered`7");
		$papers = translate_inline($papers);
		$paper = $papers[e_rand(0, count($papers)-1)];

		output("`7Unwrapping the %s wrapping paper from around the gift, you open the box to find the %s`7 which someone bought for you!`n`n", $paper, $row['name']);
		output("After a moment, you finally think to look at the attached card.`n");
		output_notl("`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`n");
		output("`^From: %s`n", $row['giftfrom']);
		output("`^To: %s`n", $row['giftto']);
		output_notl("`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`n");
		if ($row['note']) {
			output_notl("`^%s`n", $row['note']);
			output_notl("`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`4-=-`&*`4-=-`@-=--`&*`@-=-`n");
		}
		output("`n`7You hold your gift up to show everyone else what you've gotten.`n");
		if ($count > 0 ||
				(get_module_setting("extragift") &&
				 !get_module_pref("gottengift"))){
			addnav("Open Presents");
			addnav("Open another Present",
					"runmodule.php?module=giftshoppe&op=open");
		}
		page_footer();
	} elseif ($op == "buy") {
		page_header("Ye Olde Gifte Shoppe");
		villagenav();
		addnav("Return to Gifte Shoppe", "runmodule.php?module=giftshoppe");
		$id = httpget("item");
		$sql = "SELECT * FROM " . db_prefix("giftlist") . " WHERE giftid='$id'";
		$row = db_fetch_assoc(db_query($sql));
		if (!$row['giftid']) {
			output("`&The sales-elf looks broken-hearted.  \"`\$I'm SO sorry! We seem to be out of that item!  Is there something else you would like?`&\"");
		} elseif ($row['gold'] > $session['user']['gold'] || $row['gems'] > $session['user']['gems']) {
			output("`^As beautiful as the %s`^ is, you realize you don't have the necessary gold or gems to purchase it.`n`n", $row['name']);
			output("`&The sales-elf notices your look of distress and suggests that there might be something else you would like?");
		} else {
			// All good on the item, now we just need to figure out who to
			// send it to and if it's anonymous
			output("`&The sales-elf's eyes light up at your choice.");
			output("\"`\$The %s`\$ is a WONDERFUL choice.  I just know the person you are giving it to will love it!`&\"`n`n", $row['name']);
			output("`^You follow the elf over to the counter to fill out the delivery information.`n`n");
			output("`&\"`\$Now we just need to know to whom you are sending the gift!`&\"");
			output("The sales-elf hands you a form and asks you to fill in the name of the recipient.");
			rawoutput("<form action='runmodule.php?module=giftshoppe&op=checkname' method='POST'>");
			addnav("", "runmodule.php?module=giftshoppe&op=checkname");
			output("`^Recipient: ");
			rawoutput("<input name='recip'>");
			rawoutput("<input type='hidden' value='".$row['gems']."' name='gemcost'>");
			rawoutput("<input type='hidden' value='".$row['gold']."' name='goldcost'>");
			rawoutput("<input type='hidden' value='".rawurlencode(addslashes($row['name']))."' name='gift'>");
			rawoutput("<input type='hidden' value='$id' name='giftid'>");
			$sname = translate_inline("Send Gift To");
			rawoutput("<input type='submit' class='button' value='$sname'>");
			rawoutput("</form>");
		}
		page_footer();
	} elseif ($op == "checkname") {
		page_header("Ye Olde Gifte Shoppe");
		villagenav();
		addnav("Return to Gifte Shoppe", "runmodule.php?module=giftshoppe");
		$item = httppost('giftid');
		$name = stripslashes(rawurldecode(httppost('recip')));
		if (httpget('subfinal')==1){
			$sql = "SELECT acctid,name FROM " . db_prefix("accounts") . " WHERE name='".addslashes($name) . "'";
		} else {
			$search = "%";
			for ($x = 0; $x < strlen($name); $x++) {
				$search .= substr($name, $x,1)."%";
			}
			$sql = "SELECT acctid,name FROM " . db_prefix("accounts") . " WHERE name LIKE '".addslashes($search) . "'";
		}
		$result = db_query($sql);
		$count = db_num_rows($result);
		if ($count == 0) {
			output("`&The sales-elf opens a book and looks through it.`n");
			output("\"`\$I'm so sorry, but I don't know who you're talking about.  Would you like to try again?`&\"`n`n");
			addnav("Correct error");
			addnav("Re-enter name",
					"runmodule.php?module=giftshoppe&op=buy&item=$item");
		} elseif ($count > 100) {
			output("`&The sales-elf opens a book and spends quite a bit of time looking through it.`n");
			output("\"`\$I'm so sorry, but I don't know who you're talking about.  The name you gave me could be any of %d people. Would you like to try again?`&\"`n`n", $count);
			$item = httppost('giftid');
			addnav("Correct error");
			addnav("Re-enter name",
					"runmodule.php?module=giftshoppe&op=buy&item=$item");
		} elseif ($count > 1) {
			// It matches a small number of people, list them.
			output("`&The sales-elf opens a book and spends some time looking through it.`n");
			output("\"`\$I'm sorry, but you could be talking about any of %d people. Can you be more specific?`&\"`n`n", $count);
			rawoutput("<form action='runmodule.php?module=giftshoppe&op=checkname&subfinal=1' method='POST'>");
			addnav("", "runmodule.php?module=giftshoppe&op=checkname&subfinal=1");
			output("`^Recipient: ");
			rawoutput("<select name='recip'>");
			for ($i = 0; $i < $count; $i++) {
				$row = db_fetch_assoc($result);
				rawoutput("<option value='".rawurlencode(addslashes($row['name']))."'>".full_sanitize($row['name'])."</option>");
			}
			rawoutput("</select>");
			rawoutput("<input type='hidden' value='".httppost('gift')."' name='gift'>");
			rawoutput("<input type='hidden' value='".httppost('gemcost')."' name='gemcost'>");
			rawoutput("<input type='hidden' value='".httppost('goldcost')."' name='goldcost'>");
			rawoutput("<input type='hidden' value='$item' name='giftid'>");
			$sname = translate_inline("Send Gift To");
			rawoutput("<input type='submit' class='button' value='$sname'>");
			rawoutput("</form>");
		} else {
			$row = db_fetch_assoc($result);
			// Okay, we've got the name! And exactly one.
			$gift = stripslashes(rawurldecode(httppost('gift')));
			output("`&The sales-elf opens a book and looks through it.`n");
			output("`&\"`\$Ahh wonderful!  We'll be happy to send the %s`\$ that you picked out to `&%s`\$.  It'll be there before Christmas.  Now, we just need you to enter a note to go along with it and for you to tell me if you want the gift  sent anonymously.`&\"`n`n", $gift, $row['name']);
			output("`&The sales-elf turns the form over and points out the things you still need to enter, and then waits patiently.");
			addnav("Change recipient",
					"runmodule.php?module=giftshoppe&op=buy&item=$item");
			$form = array(
				"anonymous"=>"Should the gift be anonymous?,bool",
				"note"=>"What should the note say?,textarea,40");
			require_once("lib/showform.php");
			rawoutput("<form action='runmodule.php?module=giftshoppe&op=send' method='POST'>");
			addnav("", "runmodule.php?module=giftshoppe&op=send");
			rawoutput("<input type='hidden' value='".httppost('gift')."' name='gift'>");
			rawoutput("<input type='hidden' value='".httppost('gemcost')."' name='gemcost'>");
			rawoutput("<input type='hidden' value='".httppost('goldcost')."' name='goldcost'>");
			$recip = $row['acctid'];
			rawoutput("<input type='hidden' value='$recip' name='recip'>");
			rawoutput("<input type='hidden' value='$item' name='giftid'>");
			showform($form, array(), true);
			$sname = translate_inline("Send Gift");
			rawoutput("<input type='submit' class='button' value='$sname'>");
			rawoutput("</form>");
		}
		page_footer();
	} elseif ($op == "send") {
		page_header("Ye Olde Gifte Shoppe");
		villagenav();
		$gname = urldecode(httppost('gift'));
		addnav("Return to Gifte Shoppe", "runmodule.php?module=giftshoppe");
		output("`&The sales-elf collects the form with all the information and the %s`& to the back of the shop.", stripslashes($gname));
		output("He returns a moment later and smiles, \"`\$Okay, it'll get there!`&\"`n`n");
		output("Lastly, the sale-elf collects the payment for the gift.`n");
		$gold = httppost("goldcost");
		$gems = httppost("gemcost");
		$session['user']['gems'] -= $gems;
		$session['user']['gold'] -= $gold;
		debuglog("spent $gold gold and $gems gems on a gift");
		$note = urldecode(httppost('note'));
		$anon = httppost('anonymous');
		$recip = httppost('recip');
		$from = $session['user']['acctid'];
		$sql = "INSERT INTO " . db_prefix("gifts") . " (giftid,giftfrom,giftto,name,note,anonymous) VALUES(0,$from,$recip,'$gname','$note',$anon)";
		db_query($sql);
		set_module_setting("allpresents", get_module_setting("allpresents") + 1);
		page_footer();
	} elseif ($op == "") {
		// This is the actual gift shop where people can buy gifts
		page_header("Ye Olde Gifte Shoppe");
		villagenav();
		output("`^You step through the doors of the 'Ye Olde Gifte Shoppe' and are immediately surrounded by knick-knacks and bric-a-brac that anyone would just love to own!`n`n");

		output("Thinking of all the people you know, however, you manage to narrow the list of items down to a few.`n`n");
		output("`&As you stand there trying to make a final choice, a rather harried little sales-elf comes over to help you.`n");
		output("`&\"`\$Hi, hi! How can we help you today!`&\" the elf chirps happily.`n");
		// Okay, list all the items that can be bought!
		addnav("Gifts");
		$sql = "SELECT * FROM " . db_prefix("giftlist");
		$res = db_query($sql);
		while($row = db_fetch_assoc($res)) {
			if ($row['gems'] == 1) $str = "gem";
			else $str = "gems";
			$item = $row['giftid'];
			if ($row['gold'] && $row['gems']) {
				addnav(array("%s (%s gold, %s $str)", $row['name'],
							$row['gold'], $row['gems']),
					"runmodule.php?module=giftshoppe&op=buy&item=$item");
			} elseif ($row['gold']) {
				addnav(array("%s (%s gold)", $row['name'], $row['gold']),
					"runmodule.php?module=giftshoppe&op=buy&item=$item");
			} elseif ($row['gems']) {
				addnav(array("%s (%s $str)", $row['name'], $row['gems']),
					"runmodule.php?module=giftshoppe&op=buy&item=$item");
			}
		}
		page_footer();
	}
}

// Support functions
function giftshoppe_editor(){
	global $mostrecentmodule;

	page_header("Ye Olde Gifte Shoppe Editor");
	require_once("lib/superusernav.php");
	superusernav();
	addnav("Gift Editor");
	addnav("Add a gift","runmodule.php?module=giftshoppe&op=editor&subop=add&admin=true");
	$subop = httpget('subop');
	$giftid = httpget('giftid');
	$header = "";
	if ($subop != "") {
		addnav("Gift Editor Main","runmodule.php?module=giftshoppe&op=editor&admin=true");
		if ($subop == 'add') {
			$header = translate_inline("Adding a new gift");
		} else if ($subop == 'edit') {
			$header = translate_inline("Editing a gift");
		}
	} else {
		$header = translate_inline("Current gifts");
	}
	output_notl("`&<h3>$header`0</h3>", true);
	$giftarray=array(
		"Gift,title",
		"giftid"=>"Gift ID,hidden",
		"name"=>"Gift Name",
		"gold"=>"Gold cost,int",
		"gems"=>"Gem cost,int",
	);
	if($subop=="del"){
		$sql = "DELETE FROM " . db_prefix("giftlist") . " WHERE giftid='$giftid'";
		db_query($sql);
		$subop = "";
		httpset('subop', "");
	}
	if($subop=="save"){
		$giftid = httppost("giftid");
		list($sql, $keys, $vals) = postparse($giftarray);
		if ($giftid > 0) {
			$sql = "UPDATE " . db_prefix("giftlist") . " SET $sql WHERE giftid='$giftid'";
		} else {
			$sql = "INSERT INTO " . db_prefix("giftlist") . " ($keys) VALUES ($vals)";
		}
		db_query($sql);
		if (db_affected_rows()> 0) {
			output("`^Gift saved!");
		} else {
			output("`^Gift not saved: `\$%s`0", $sql);
		}
		if ($giftid) {
			$subop = "edit";
			httpset("giftid", $giftid, true);
		} else {
			$subop = "";
		}
		httpset('subop', $subop);
	}
	if ($subop==""){
		$ops = translate_inline("Ops");
		$id = translate_inline("Id");
		$nm = translate_inline("Name");
		$gold = translate_inline("Gold Cost");
		$gem = translate_inline("Gem Cost");
		$edit = translate_inline("Edit");
		$conf = translate_inline("Are you sure you wish to delete this gift?");
		$del = translate_inline("Del");
		rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
		rawoutput("<tr class='trhead'>");
		rawoutput("<td>$ops</td><td>$id</td><td>$nm</td><td>$gold</td><td>$gem</td>");
		rawoutput("</tr>");
		$sql = "SELECT * FROM " . db_prefix("giftlist") . " ORDER BY giftid";
		$result= db_query($sql);
		for ($i=0;$i<db_num_rows($result);$i++){
			$row = db_fetch_assoc($result);
			$id = $row['giftid'];
			rawoutput("<tr class='".($i%2?"trlight":"trdark")."'>");
			rawoutput("<td nowrap>[ <a href='runmodule.php?module=giftshoppe&op=editor&subop=edit&giftid=$id&admin=true'>$edit</a>");
			addnav("","runmodule.php?module=giftshoppe&op=editor&subop=edit&giftid=$id&admin=true");
			rawoutput(" | <a href='runmodule.php?module=giftshoppe&op=editor&subop=del&giftid=$id&admin=true' onClick='return confirm(\"$conf\");'>$del</a> ]</td>");
			addnav("","runmodule.php?module=giftshoppe&op=editor&subop=del&giftid=$id&admin=true");
			output_notl("<td>`^%s</td>`0", $id, true);
			output_notl("<td>`&%s`0</td>", $row['name'], true);
			output_notl("<td>`^%s`0</td>", $row['gold'], true);
			output_notl("<td>`%%s`0</td>", $row['gems'], true);
			rawoutput("</tr>");
		}
		rawoutput("</table>");
	}
	if($subop=="edit"){
		$sql="SELECT * FROM " . db_prefix("giftlist") . " WHERE giftid='$giftid'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
	}elseif ($subop=="add"){
		/* We're adding a new drink, make an empty row */
		$row = array();
		$row['giftid'] = 0;
	}

	if ($subop == "edit" || $subop == "add") {
		require_once("lib/showform.php");
		rawoutput("<form action='runmodule.php?module=giftshoppe&op=editor&subop=save&admin=true' method='POST'>");
		addnav("","runmodule.php?module=giftshoppe&op=editor&subop=save&admin=true");
		showform($giftarray,$row);
		rawoutput("</form>");
	}
	page_footer();
}

?>
