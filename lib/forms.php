<?php
function previewfield($name, $startdiv=false, $talkline="says", $showcharsleft=true, $info=false, $default=false) {
	global $schema,$session;
	$talkline = translate_inline($talkline, $schema);
	$youhave = translate_inline("You have ");
	$charsleft = translate_inline(" characters left.");

	if ($startdiv === false)
		$startdiv = "";
	rawoutput("<script language='JavaScript'>
				function previewtext$name(t,l){
					var out = \"<span class=\\'colLtWhite\\'>".addslashes(appoencode($startdiv))." \";
					var end = '</span>';
					var x=0;
					var y='';
					var z='';
					var max=document.getElementById('input$name');
					var charsleft='';");
	if ($talkline !== false) {
		rawoutput("	if (t.substr(0,2)=='::'){
						x=2;
						out += '</span><span class=\\'colLtWhite\\'>';
					}else if (t.substr(0,1)==':'){
						x=1;
						out += '</span><span class=\\'colLtWhite\\'>';
					}else if (t.substr(0,3)=='/me'){
						x=3;
						out += '</span><span class=\\'colLtWhite\\'>';");
		if ($session['user']['superuser']&SU_IS_GAMEMASTER) {
			rawoutput("
					}else if (t.substr(0,5)=='/game'){
						x=5;
						out = '<span class=\\'colLtWhite\\'>';");
		}
		rawoutput("	}else{
						out += '</span><span class=\\'colDkCyan\\'>".addslashes(appoencode($talkline)).", \"</span><span class=\\'colLtCyan\\'>';
						end += '</span><span class=\\'colDkCyan\\'>\"';
					}");
	}
	if ($showcharsleft == true) {
/*		if (translate_inline($talkline,$schema)!="says")
		$tll = strlen(translate_inline($talkline,$schema))+11;
		else $tll=0;  // Don't know why needed
		rawoutput("	if (x!=0) {
						if (max.maxlength!=200-$tll) max.maxlength=200-$tll;
						l=200-$tll; */ // Don't know why needed
		rawoutput("	if (x!=0) {
						if (max.maxLength!=200) max.maxLength=200;
						l=200;
					} else {
						max.maxLength=l;
					}
					if (l-t.length<0) charsleft +='<span class=\\'colLtRed\\'>';
					charsleft += '".$youhave."'+(l-t.length)+'".$charsleft."<br>';
					if (l-t.length<0) charsleft +='</span>';
					document.getElementById('charsleft$name').innerHTML=charsleft+'<br/>';");
	}
	rawoutput("		for (; x < t.length; x++){
						y = t.substr(x,1);
						if (y=='<'){
							out += '&lt;';
							continue;
						}else if(y=='>'){
							out += '&gt;';
							continue;
						}else if(y=='\\n'){
							out += '<br />';
							continue;
						}else if (y=='`'){
							if (x < t.length-1){
								z = t.substr(x+1,1);
								if (z=='0'){
									out += '</span>';
								}else if (z=='1'){
									out += '</span><span class=\\'colDkBlue\\'>';
								}else if (z=='2'){
									out += '</span><span class=\\'colDkGreen\\'>';
								}else if (z=='3'){
									out += '</span><span class=\\'colDkCyan\\'>';
								}else if (z=='4'){
									out += '</span><span class=\\'colDkRed\\'>';
								}else if (z=='5'){
									out += '</span><span class=\\'colDkMagenta\\'>';
								}else if (z=='6'){
									out += '</span><span class=\\'colDkYellow\\'>';
								}else if (z=='7'){
									out += '</span><span class=\\'colDkWhite\\'>';
								}else if (z=='q'){
									out += '</span><span class=\\'colDkOrange\\'>';
								}else if (z=='!'){
									out += '</span><span class=\\'colLtBlue\\'>';
								}else if (z=='@'){
									out += '</span><span class=\\'colLtGreen\\'>';
								}else if (z=='#'){
									out += '</span><span class=\\'colLtCyan\\'>';
								}else if (z=='$'){
									out += '</span><span class=\\'colLtRed\\'>';
								}else if (z=='%'){
									out += '</span><span class=\\'colLtMagenta\\'>';
								}else if (z=='^'){
									out += '</span><span class=\\'colLtYellow\\'>';
								}else if (z=='&'){
									out += '</span><span class=\\'colLtWhite\\'>';
								}else if (z=='Q'){
									out += '</span><span class=\\'colLtOrange\\'>';
								}else if (z==')'){
									out += '</span><span class=\\'colLtBlack\\'>';
								}else if (z=='r'){
									out += '</span><span class=\\'colRose\\'>';
								}else if (z=='R'){
									out += '</span><span class=\\'colRose\\'>';
								}else if (z=='v'){
									out += '</span><span class=\\'coliceviolet\\'>';
								}else if (z=='V'){
									out += '</span><span class=\\'colBlueViolet\\'>';
								}else if (z=='g'){
									out += '</span><span class=\\'colXLtGreen\\'>';
								}else if (z=='G'){
									out += '</span><span class=\\'colXLtGreen\\'>';
								}else if (z=='T'){
									out += '</span><span class=\\'colDkBrown\\'>';
								}else if (z=='t'){
									out += '</span><span class=\\'colLtBrown\\'>';
								}else if (z=='~'){
									out += '</span><span class=\\'colBlack\\'>';
								}else if (z=='j'){
									out += '</span><span class=\\'colMdGrey\\'>';
								}else if (z=='J'){
									out += '</span><span class=\\'colMdBlue\\'>';
								}else if (z=='e'){
									out += '</span><span class=\\'colDkRust\\'>';
								}else if (z=='E'){
									out += '</span><span class=\\'colLtRust\\'>';
								}else if (z=='l'){
									out += '</span><span class=\\'colDkLinkBlue\\'>';
								}else if (z=='L'){
									out += '</span><span class=\\'colLtLinkBlue\\'>';
								}else if (z=='x'){
									out += '</span><span class=\\'colburlywood\\'>';
								}else if (z=='X'){
									out += '</span><span class=\\'colbeige\\'>';
								}else if (z=='y'){
									out += '</span><span class=\\'colkhaki\\'>';
								}else if (z=='Y'){
									out += '</span><span class=\\'coldarkkhaki\\'>';
								}else if (z=='k'){
									out += '</span><span class=\\'colaquamarine\\'>';
								}else if (z=='K'){
									out += '</span><span class=\\'coldarkseagreen\\'>';
								}else if (z=='p'){
									out += '</span><span class=\\'collightsalmon\\'>';
								}else if (z=='P'){
									out += '</span><span class=\\'colsalmon\\'>';
								}else if (z=='m'){
									out += '</span><span class=\\'colwheat\\'>';
								}else if (z=='M'){
									out += '</span><span class=\\'coltan\\'>';
								}
								x++;
							}
						}else{
							out += y;
						}
					}
					document.getElementById(\"previewtext$name\").innerHTML=out+end+'<br/>';
				}
				</script>
				");
	if ($charsleft == true) {
		rawoutput("<span id='charsleft$name'></span>");
	}
	if (!is_array($info)) {
		if ($default) {
			rawoutput("<input name='$name' id='input$name' maxlength='255' onKeyUp='previewtext$name(document.getElementById(\"input$name\").value,200);' value='$default'>");
		} else {
			rawoutput("<input name='$name' id='input$name' maxlength='255' onKeyUp='previewtext$name(document.getElementById(\"input$name\").value,200);'>");
		}
	} else {
		if (isset($info['maxlength'])) {
			$l = $info['maxlength'];
		} else {
			$l=200;
		}
		if (isset($info['type']) && $info['type'] == 'textarea') {
			rawoutput("<textarea name='$name' id='input$name' onKeyUp='previewtext$name(document.getElementById(\"input$name\").value,$l);' ");
		} else {
			rawoutput("<input name='$name' id='input$name' onKeyUp='previewtext$name(document.getElementById(\"input$name\").value,$l);' ");
		}
		foreach ($info as $key=>$val){
			rawoutput("$key='$val'");
		}
		if (isset($info['type']) && $info['type'] == 'textarea') {
			rawoutput(">");
			if ($default) {
				rawoutput($default);
			}
			rawoutput("</textarea>");
		} else {
			if ($default) {
				rawoutput(" value='$default'>");
			} else {
				rawoutput(">");
			}
		}
	}
	rawoutput("<div id='previewtext$name'></div>");
}
?>