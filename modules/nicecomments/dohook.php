<?php
function nicecomments_dohook_private($hookname,$args){
	global $session;
	switch($hookname){
	case "commentary":
		$text = $args['commentline'];
		$his = translate_inline("his");
		$her = translate_inline("her");
		$o = ($session['user']['sex']?$her:$his);
		if (get_module_setting("do_emotes")){
			$full_emotes = array(
				"hehe"=>":chuckles.",
				"haha"=>":laughs.",
				"lol"=>":laughs heartily!",
				"gtg"=>":needs to depart.",
				"bbiab"=>":will return momentarily.",
				"bbias"=>":will return momentarily.",
				"rot?fl"=>":plops right on the ground, laughing.",
				"lmao|rot?flmao"=>":laughs so hard, $o posterior falls off.",
				"wtf"=>":looks confused.",
			);
			$partial_emotes = array(
				"hehe"=>"That's funny",
				"haha"=>"That's funny",
				"bbl"=>"I'll be back later.",
				"omg"=>"Goodness!",
				"gtg"=>"I need to leave.",
				"wtf"=>"I'm confused",
				"omfg"=>"Wow!",
				"atm"=>"at the moment",
				"wb"=>"Welcome back",
				"omgwtf|wtfomg"=>"Oh my heavens!",
				"brb"=>"I'll be right back.",
				"bbias"=>"I'll be right back.",
				"bbiab"=>"I'll be right back.",
				"yom"=>"Ye Olde Mail",
				"motd"=>"Message of the Day",
				"dk"=>"dragon kill",
				"dks"=>"dragon kills",
				"ty"=>"thank you",
				"rng"=>"random number generator",
				"lvl"=>"level",
				"g2g"=>"got to go",
			);
			foreach ($full_emotes as $pattern=>$replacement){
				$text = preg_replace("/^($pattern)(one|eleven|[.!1])*$/i",$replacement,$text);
			}
			foreach ($partial_emotes as $pattern=>$replacement){
				$text = preg_replace("/\\b($pattern)\\b/i","$replacement ",$text);
			}
		}
		if (get_module_setting("do_aol")){
			$partial_emotes = array(
				"u"=>"you",
				"r"=>"are",
				"ur"=>"you are",
				"ru"=>"are you",
				"ne *1"=>"any one",
				"ne"=>"any",
				"(n[o0][o0]b|newb)"=>"new player",
				"a\\/?s\\/?l"=>"Tell me about yourself.",
				"(some?|sum) *(won|1)"=>"someone",
				"(som|sum)"=>"some",
				"wun"=>"one",
			);
			foreach ($partial_emotes as $pattern=>$replacement){
				$text = preg_replace("/\\b($pattern)\\b/i","$replacement ",$text);
			}
		}
		if (get_module_setting("do_caps")){
			//we don't force small caps on short text strings.
			if (strlen($text) > 5){
				//test to see if there are too many caps:
				$allletters = preg_replace("/[^a-zA-Z]/","",$text);
				$allcaps = preg_replace("/[a-z]/","",$allletters);
				if (strlen($allcaps) >= strlen($allletters)/2){
					//too many caps.
					$text = preg_replace("/\\b([a-zA-Z0-9])([a-zA-Z0-9]*)\\b/e","'\\1'.strtolower('\\2')",$text);
				}
			}
		}
		$args['commentline'] = $text;
		break;
	}
	return $args;
}
?>
