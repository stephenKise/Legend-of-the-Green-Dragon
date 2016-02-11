<?php
$config = unserialize($session['user']['donationconfig']);
$expense = round(($session['user']['level']*(10+log($session['user']['level']))),0);
$pay = httpget('pay');
if ($pay){
	if ($pay == 2 || $session['user']['gold']>=$expense ||
			$session['user']['boughtroomtoday']){
		if ($session['user']['loggedin']){
			if (!$session['user']['boughtroomtoday']) {
				if ($pay == 2) {
					$fee = getsetting("innfee", "5%");
					if (strpos($fee, "%"))
						$expense += round($expense * $fee / 100,0);
					else
						$expense += $fee;
					$session['user']['goldinbank']-=$expense;
				} else {
					$session['user']['gold']-=$expense;
				}
				$session['user']['boughtroomtoday']=1;
				debuglog("spent $expense gold on an inn room");
			}
			$session['user']['location']=$iname;
			$session['user']['loggedin']=0;
			$session['user']['restorepage']="inn.php?op=strolldown";
			saveuser();
		}
		$session=array();
		redirect("index.php");
	}else{
		output("\"Aah, so that's how it is,\" %s`0 says as he puts the key he had retrieved back on to its hook behind his counter.",$barkeep);
		output("Perhaps you'd like to get sufficient funds before you attempt to engage in local commerce.");
	}
}else{
	if ($session['user']['boughtroomtoday']){
		output("You already paid for a room for the day.");
		addnav("Go to room","inn.php?op=room&pay=1");
	}else{
		modulehook("innrooms");
		output("You stroll over to the bartender and request a room.");
		output("He eyes you up and says, \"It will cost `\$%s`0 gold for the night in a standard room.", $expense);
		$fee = getsetting("innfee", "5%");
		if (strpos($fee, "%")) {
			$bankexpense = $expense + round($expense * $fee / 100,0);
		} else {
			$bankexpense = $expense + $fee;
		}
		if ($session['user']['goldinbank'] >= $bankexpense && $bankexpense != $expense) {
			output("And since you are such a fine person, I'll even offer you a rate of `\$%s`0 gold if you pay direct from the bank.", $bankexpense);
			if (strpos($fee, "%")) {
				output("That includes a %s transaction fee.", $fee);
			} else {
				output("That includes a transaction fee of %s gold.",
						$fee);
			}
		}
		$bodyguards = array("Butch","Bruce","Alfonozo","Guido","Bruno","Bubba","Al","Chuck","Brutus","Nunzio","Terrance","Mitch","Rocco","Spike","Gregor","Sven","Draco");
		output("`n`n\"Also, let me tell you about our new 'Bodyguard Assistance Program' &#151; BAP.  You see, you hire one of my guards here, and they'll protect you should anyone happen to, er, pick the locks of your room,\" he says as he gestures to a series of men sitting at one of the inn's tables drinking ale.", true);
		output("They range in size from a skinny shifty-eyed fellow who appears barely able to lift his stein to a great bear of a fellow.");
		output("This bruiser has a tattoo of a heart with \"Mom\" written across it on his huge bicep, and goes to take a sip from his ale, but instead crushes his stein, squirting it all over the skinny fellow who doesn't voice any objection for obvious reasons.");
		output("\"We call it the BAP program because when someone tries to sneak into your room, BAP BAP BAP, our guys go to work.");
		output("There's only two conditions: you pay your fee up front, and the guard you choose gets to keep a portion of the rewards from any fights.\"");
		output("`n`nNot wanting to part with your money when the fields offer a place to sleep, you debate the issue.");
		output("You realize, however, that the inn is a considerably safer place to sleep.");
		output("It is far harder for vagabonds to get you in your room while you sleep.");
		output("Also, those bodyguards sound pretty safe to you.");
		//output("`n`bNote, bodyguard levels not yet implemented`b`n");
		addnav(array("Give him %s gold", $expense),"inn.php?op=room&pay=1");
		if ($session['user']['goldinbank'] >= $bankexpense) {
			addnav(array("Pay %s gold from bank", $bankexpense),"inn.php?op=room&pay=2");
		}
	}
}
?>