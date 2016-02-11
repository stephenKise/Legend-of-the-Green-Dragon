<?php
function lovers_chat_violet(){
	global $session;
	if (httpget('act')==""){
		addnav("Gossip","runmodule.php?module=lovers&op=chat&act=gossip");
		addnav(array("Ask if your %s makes you look fat", $session['user']['armor']),"runmodule.php?module=lovers&op=chat&act=fat");
		output("You go over to %s`0 and help her with the drinks she is carrying.", getsetting("barmaid", "`%Violet"));
		output("Once they are passed out, she takes a cloth and wipes the sweat off of her brow, thanking you much.");
		output("Of course you didn't mind, as she is one of your oldest and truest friends!");
	}else if(httpget('act')=="gossip"){
		output("You and %s`0 gossip quietly for a few minutes about not much at all.", getsetting("barmaid", "`%Violet"));
		output("She offers you a pickle.");
		output("You accept, knowing that it's in her nature to do so as a former pickle wench.");
		output("After a few minutes, %s`0 begins to cast burning looks your way, and you decide you had best let %s`0 get back to work.",getsetting('barkeep','`tCedrik'), getsetting("barmaid", "`%Violet"));
	}else if(httpget('act')=="fat"){
		$charm = $session['user']['charm']+e_rand(-1,1);
		output("%s`0 looks you up and down very seriously.", getsetting("barmaid", "`%Violet"));
		output("Only a friend can be truly honest, and that is why you asked her.");
		switch($charm){
			case -3: case -2: case -1: case 0:
				$msg = translate_inline("Your outfit doesn't leave much to the imagination, but some things are best not thought about at all!  Get some less revealing clothes as a public service!");
				break;
			case 1: case 2: case 3:
				$msg = translate_inline("I've seen some lovely ladies in my day, but I'm afraid you aren't one of them.");
				break;
			case 4: case 5: case 6:
				$msg = translate_inline("I've seen worse my friend, but only trailing a horse.");
				break;
			case 7: case 8: case 9:
				$msg = translate_inline("You're of fairly average appearance my friend.");
				break;
			case 10: case 11: case 12:
				$msg = translate_inline("You certainly are something to look at, just don't get too big of a head about it, eh?");
				break;
			case 13: case 14: case 15:
				$msg = translate_inline("You're quite a bit better than average!");
				break;
			case 16: case 17: case 18:
				$msg = translate_inline("Few women could count themselves to be in competition with you!");
				break;
			default:
				$msg = translate_inline("I hate you, why, you are simply the most beautiful woman ever!");
		}
		output("Finally she reaches a conclusion and states, \"%s`0\"", $msg);
	}
}
?>