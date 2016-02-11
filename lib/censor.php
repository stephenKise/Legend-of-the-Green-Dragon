<?php
// translator ready
// addnews ready
// mail ready
function soap($input,$debug=false,$skiphook=false){
	global $session;
	require_once("lib/sanitize.php");
	$final_output = $input;
	// $output is the color code-less (fully sanitized) input against which
	// we search.
	$output = full_sanitize($input);
	// the mask of displayable chars that should be masked out;
	// X displays, _ masks.
	$mix_mask = str_pad("",strlen($output),"X");
	if (getsetting("soap",1)){
		$search = nasty_word_list();
		$exceptions = array_flip(good_word_list());
		$changed_content = false;
		while (list($key,$word)=each($search)){
			do {
				if ($word > "")
					$times = preg_match_all($word,$output,$matches);
				else
					$times = 0;
				for ($x=0; $x<$times; $x++){
					if (strlen($matches[0][$x]) < strlen($matches[1][$x])){
						$shortword = $matches[0][$x];
						$longword = $matches[1][$x];
					}else{
						$shortword = $matches[1][$x];
						$longword = $matches[0][$x];
					}
					if (isset($exceptions[strtolower($longword)])){
						$x--;
						$times--;
						if ($debug)
							output("This word is ok because it was caught by an exception: `b`^%s`7`b`n",$longword);
					}else{
						if ($debug)
							output("`7This word is not ok: \"`%%s`7\"; it blocks on the pattern `i%s`i at \"`\$%s`7\".`n",$longword,$word,$shortword);
						// if the word should be filtered, drop it from the
						// search terms ($output), and mask its bytes out of
						// the output mask.
						$len = strlen($shortword);
						$pad = str_pad("",$len,"_");
						//while (($p = strpos($output,$shortword))!==false){
							$p = strpos($output,$shortword);
							$output = substr($output,0,$p) . $pad .
								substr($output,$p+$len);
							$mix_mask = substr($mix_mask,0,$p) . $pad .
								substr($mix_mask,$p+$len);
						//}
						$changed_content = true;
					}//end if
				}//end for
			} while ($times > 0);
		}
		$y = 0; //position within final output
		$pad = '#@%$!';
		for ($x=0; $x<strlen($mix_mask); $x++){
			while (substr($final_output,$y,1)=="`"){
				$y+=2; //when encountering appo encoding, skip over it.
			}
			//this character should be masked out.
			if (substr($mix_mask,$x,1)=="_"){
				$final_output = substr($final_output,0,$y) .
					substr($pad,$x % strlen($pad),1) .
					substr($final_output,$y+1);
			}
			$y++;
		}
		if ($session['user']['superuser'] & SU_EDIT_COMMENTS &&
				$changed_content){
			output("`0The filter would have tripped on \"`#%s`0\" but since you're a moderator, I'm going to be lenient on you.  The text would have read, \"`#%s`0\"`n`n",$input,$final_output);
			return $input;
		}else{
			if ($changed_content && !$skiphook)
				modulehook("censor", array("input"=>$input));
			return $final_output;
		}
	}else{
		return $final_output;
	}
}

function good_word_list(){
	$sql = "SELECT * FROM " . db_prefix("nastywords") . " WHERE type='good'";
	$result = db_query_cached($sql,"goodwordlist");
	$row = db_fetch_assoc($result);
	return explode(" ",$row['words']);
}

function nasty_word_list(){
	$search = datacache("nastywordlist",600);
	if ($search!==false && is_array($search)) return $search;

	$sql = "SELECT * FROM " . db_prefix("nastywords") . " WHERE type='nasty'";
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	$search = " ".$row['words']." ";
	$search = preg_replace('/(?<=.)(?<!\\\\)\'(?=.)/', '\\\'', $search);
	$search = str_replace("a",'[a4@ªÀÁÂÃÄÅàáâãäå]',$search);
	$search = str_replace("b",'[bß]',$search);
	$search = str_replace("d",'[dÐÞþ]',$search);
	$search = str_replace("e",'[e3ÉÊËÈèéêë]',$search);
	$search = str_replace("n",'[nÑñ]',$search);
	$search = str_replace("o",'[o°º0ÒÓÔÕÖØðòóôõöø¤]',$search);
	$search = str_replace("p",'[pÞþ¶]',$search);
	$search = str_replace("r",'[r®]',$search);
//	$search = str_replace("s",'[sz$§]',$search);
	$search = preg_replace('/(?<!\\\\)s/','[sz$§]',$search);
	$search = str_replace("t",'[t7+]',$search);
	$search = str_replace("u",'[uÛÜÙÚùúûüµ]',$search);
	$search = str_replace("x",'[x×¤]',$search);
	$search = str_replace("y",'[yÝ¥ýÿ]',$search);
	//these must happen in exactly this order:
	$search = str_replace("l",'[l1!£]',$search);
	$search = str_replace("i",'[li1!¡ÌÍÎÏìíîï]',$search);
	$search = str_replace("k",'c',$search);
	$search = str_replace("c",'[c\\(kç©¢]',$search);
	$start = "'\\b";
	$end = "\\b'iU";
	$ws = "[^[:space:]\\t]*"; //whitespace (\w is not hungry enough)
	//space not preceeded by a star
	$search = preg_replace("'(?<!\\*) '",")+$end ",$search);
	//space not anteceeded by a star
	$search = preg_replace("' (?!\\*)'"," $start(",$search);
	//space preceeded by a star
	$search = str_replace("* ",")+$ws$end ",$search);
	//space anteceeded by a star
	$search = str_replace(" *"," $start$ws(",$search);
	$search = "$start(".trim($search).")+$end";
	$search = str_replace("$start()+$end","",$search);
	$search = explode(" ",$search);
	updatedatacache("nastywordlist",$search);
	return $search;
}
?>
