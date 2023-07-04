<?php
require_once('lib/pullurl.php');


function license() {
	$licenseUrl = 'http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode';
	$license = join('', pullurl($licenseUrl));
	$license = str_replace("\n", '', $license);
	$license = str_replace("\r", '', $license);
	$match = [];
	preg_match_all("'<body[^>]*>(.*)</body>'", $license, $match);
	return $match[1][0];
}

function licenseHash() {
	return md5(license());
}

function licenseTxt() {
	$license = join('', file('LICENSE.txt'));
	preg_replace(
		"/[^\na-zA-Z0-9!?.,;:'\"\\/\\()@ -\\]\\[]/",
		'',
		$license
	);
	$license = nl2br(htmlentities(
		$license, ENT_COMPAT, getsetting('charset', 'UTF-8')
	));
	return $license;
}

function licenseTxtHash() {
	return md5(licenseTxt());
}

$licenseOutput = "
	<a href='http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode'>
		<div class='license-agreement'>$license</div>
	</a>
";
$licenseTxt = licenseTxt();
$licenseHash = licenseTxtHash();

output("
	`@`c`bLicense Agreement`b`c`0 
	`2By installing, you agree to the following license agreement:`0`n`n
");
if (licenseHash() == '484d213db9a69e79321feafb85915ff1')
	rawoutput(license());
else
	output("
		`^Warning: Creative Commons license not found. 
		Below are the author's explanation of the agreement, from LICENSE.txt`0
	");
output('`n`n');
if (licenseTxtHash() == 'a192586931e44a73b118c3ff96a45c27') 
	output("`b`@License Plaintext information: `b`n`7$licenseTxt`0", true);
else {
	output("
		`^LICENSE.txt has been modified. This file cannot be tampered with. 
		Expected MD5 of a192586931e44a73b118c3ff96a45c27, received $licenseHash.`0
	");
	$stage = -1;
	$session['stagecompleted'] = -1;
}
?>