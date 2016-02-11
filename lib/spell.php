<?php
// translator ready
// addnews ready
// mail ready
$spell_dictionary = array();
function spell($input,$words=false,$prefix="<span style='border: 1px dotted #FF0000;'>",$postfix="</span>"){
	global $spell_dictionary;
	if ($words===false)
		$words = getsetting("dictionary","/usr/share/dict/words");
	if (file_exists($words)){
		if (!is_array($spell_dictionary) || count($spell_dictionary)==0){
			//retrieve dictionary
			$dict = file($words);
			//sanitize the keys to drop linefeeds from the words
			$dict = join("",$dict);
			$dict = explode("\n",$dict);

			$dict = array_flip($dict);
			//words not typically found in a dict file
			$dict['a']=1;
			$dict['I']=1;
			$spell_dictionary =& $dict;
		}else{
			$dict = &$spell_dictionary;
		}
		//Common Contractions
		$contractions = array(
			"n't"=>"n't", //haven't
			"'s"=>"'s", //Joe's going to, also possessive noun
			"'ll"=>"'ll", //we'll
			"'re"=>"'re", //they're
			"'ve"=>"'ve", //Where've you been all day?
			"'m"=>"'m", //What'm I supposed to say?
			"'d"=>"'d", //He'd
		);
		$input = preg_split("/([<>])/",$input,-1,PREG_SPLIT_DELIM_CAPTURE);
		$intag = false;
		$output = "";
		while (list($key,$val)=each($input)){
			if ($val=="<"){
				$intag = true;
			}elseif ($val==">"){
				$intag = false;
			}elseif (!$intag){
				//spellcheck data not found within tags.
				$line =
					preg_split("/([\t\n\r[:space:]-])/",
						$val,-1,PREG_SPLIT_DELIM_CAPTURE);
				$val = "";
				while (list($k,$v)=each($line)){
					$lookups = array();
					$i=0;
					//look for common variations on words
					$v1 = trim($v);
					if ($v1>"") {
						$lookups[$v1]=$i++;
						$lookups[strtolower($v1)]=$i++;
					}
					//search for contraction endings
					reset($contractions);
					//strip trailing punctuation
					$v2 = preg_replace("/[.?!\"']+$/","",$v);
					while (list($cont,$throwaway)=each($contractions)){
						if (substr($v2,strlen($v2)-strlen($cont)) == $cont){
							$v1 = substr($v2,0,strlen($v2)-strlen($cont));
							if ($v1>"") {
								$lookups[$v1]=$i++;
								$lookups[strtolower($v1)]=$i++;
							}
						}
					}
					$v1 = preg_replace("/[^a-zA-Z]/","",trim($v));
					if ($v1>"") {
						$lookups[$v1]=$i++;
						$lookups[strtolower($v1)]=$i++;
					} else {
						 //if there's no alpha chars, we have no lookups to do
						$lookups = array();
					}
					if (count($lookups)>0){
						$found = false;
						while (list($k1,$v1)=each($lookups)){
							if (isset($dict[$k1])){
								$found = true;
								break;
							}
						}
					}else{
						$found = true;
					}
					if (!$found){
						if (preg_match("/[[:digit:]]/",$v)) $found=true;
					}
					if (!$found){
						$val = $val.$prefix.$v.$postfix;
					}else{
						$val.=$v;
					}
				}//end while
			}//end if
			$output.=$val;
		}//end while
	}else{
		$output = $input;
	}
	return $output;
}
?>
