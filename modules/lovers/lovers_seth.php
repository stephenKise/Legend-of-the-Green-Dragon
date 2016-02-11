<?php
function lovers_seth(){
	global $session;
	$seenlover = get_module_pref("seenlover");
	$partner = get_partner();

	if ($seenlover==0){
		//haven't seen lover
		if ($session['user']['marriedto']==INT_MAX){
			//married
			$seenlover=1;
			if (e_rand(1,4)==1){
				switch(e_rand(1,4)){
				case 1:
					$msg = translate_inline("being too busy tuning his lute,");
					break;
				case 2:
					$msg = translate_inline("\"that time of month,\"");
					break;
				case 3:
					$msg = translate_inline("\"a little cold...  *cough cough* see?\"");
					break;
				case 4:
					$msg = translate_inline("wanting you to fetch him a beer,");
					break;
				}
				output("You head over to snuggle up to %s`0 and kiss him about the face and neck, but he grumbles something about %s and with a comment like that, you storm away from him!",$partner,$msg);
				$session['user']['charm']--;
				output("`n`n`^You LOSE a charm point!");
			}else{
				output("You and %s`0 take some time to yourselves, and you leave the inn, positively glowing!",$partner);
				apply_buff('lover',lovers_getbuff());
				$session['user']['charm']++;
				output("`n`n`^You gain a charm point!");
			}
		}else{
			//not married.
			if (httpget("flirt")==""){
				//haven't flirted yet
				addnav("Flirt");
				addnav("Wink","runmodule.php?module=lovers&op=flirt&flirt=1");
				addnav("Flutter Eyelashes","runmodule.php?module=lovers&op=flirt&flirt=2");
				addnav("Drop Hanky","runmodule.php?module=lovers&op=flirt&flirt=3");
				addnav("Ask him to buy you a drink","runmodule.php?module=lovers&op=flirt&flirt=4");
				addnav("Kiss him soundly","runmodule.php?module=lovers&op=flirt&flirt=5");
				addnav("Completely seduce him","runmodule.php?module=lovers&op=flirt&flirt=6");
				addnav("Marry him","runmodule.php?module=lovers&op=flirt&flirt=7");
			}else{
				//flirting now
				$c = $session['user']['charm'];
				$seenlover=1;
				switch(httpget('flirt')){
				case 1:
					if (e_rand($c,2)>=2){
						output("%s`0 grins a big toothy grin.",$partner);
						output("My, isn't the dimple in his chin cute??");
						if ($c<4) $c++;
					}else{
						output("%s`0 raises an eyebrow at you, and asks if you have something in your eye.",$partner);
					}
					break;
				case 2:
					if (e_rand($c,4)>=4){
						output("%s`0 smiles at you and says, \"`^My, what pretty eyes you have.`0\"",$partner);
						if ($c<7) $c++;
					}else{
						output("%s`0 smiles, and waves... to the person standing behind you.",$partner);
					}
					break;
				case 3:
					if (e_rand($c,7)>=7){
						output("%s`0 bends over and retrieves your hanky, while you admire his firm posterior.",$partner);
						if ($c<11) $c++;
					}else{
						output("%s`0 bends over and retrieves your hanky, wipes his nose with it, and gives it back.",$partner);
					}
					break;
				case 4:
					if (e_rand($c,11)>=11){
						output("%s`0 places his arm around your waist, and escorts you to the bar where he buys you one of the Inn's fine swills.",$partner);
						if ($c<14) $c++;
					}else{
						output("%s`0 apologizes, \"`^I'm sorry m'lady, I have no money to spare,`0\" as he turns out his moth-riddled pocket.",$partner);
						if ($c>0 && $c<10) $c--;
					}
					break;
				case 5:
					if (e_rand($c,14)>=14){
						output("You walk up to %s`0, grab him by the shirt, pull him to his feet, and plant a firm, long kiss right on his handsome lips.",$partner);
						output("He collapses after, hair a bit disheveled, and short on breath.");
						if ($c<18) $c++;
					}else{
						output("You duck down to kiss %s`0 on the lips, but just as you do so, he bends over to tie his shoe.",$partner);
						if ($c>0 && $c<13) $c--;
					}
					break;
				case 6:
					if (e_rand($c,18)>=18){
						output("Standing at the base of the stairs, you make a come-hither gesture at %s`0.",$partner);
						output("He follows you like a puppydog.");
						if ($session['user']['turns']>0){
							output("You feel exhausted!");
							$session['user']['turns']-=2;
							if ($session['user']['turns']<0)
								$session['user']['turns']=0;
						}
						addnews("`@%s`@ and %s`@ were seen heading up the stairs in the inn together.`0",$session['user']['name'],$partner);
						if ($c<25) $c++;
					}else{
						output("\"`^I'm sorry m'lady, but I have a show in 5 minutes`0\"");
						if ($c>0) $c--;
					}
					break;
				case 7:
					output("Walking up to %s`0, you simply demand that he marry you.`n`n",$partner);
					output("He looks at you for a few seconds.`n`n");
					if ($c>=22){
						output("\"`^Of course my love!`0\" he says.");
						output("The next weeks are a blur as you plan the most wonderous wedding, paid for entirely by %s`0, and head on off to the deep forest for your honeymoon.",$partner);
						addnews("`&%s`& and %s`& are joined today in joyous matrimony!!!",$session['user']['name'],$partner);
						$session['user']['marriedto']=INT_MAX;
						apply_buff('lover',lovers_getbuff());
					}else{
						output("%s`0 says, \"`^I'm sorry, apparently I've given you the wrong impression, I think we should just be friends.`0\"", $partner);
						output("Depressed, you have no more desire to fight in the forest today.");
						$session['user']['turns']=0;
						debuglog("lost all turns after being rejected for marriage.");
					}
					break;
				}//end switch
				if ($c > $session['user']['charm'])
					output("`n`n`^You gain a charm point!");
				if ($c < $session['user']['charm'])
					output("`n`n`\$You LOSE a charm point!");
				$session['user']['charm']=$c;
			}//end if
		}//end if
	}else{
		//have seen lover
		output("You think you had better not push your luck with %s`0 today.",$partner);
	}
	set_module_pref("seenlover",$seenlover);
}
?>