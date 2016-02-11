<?php
$setspecialty=httpget('setspecialty');
if ($setspecialty != "") {
	$session['user']['specialty']=$setspecialty;
	modulehook("set-specialty");
	addnav("Continue","newday.php?continue=1$resline");
} else {
	page_header("A little history about yourself");
	output("What do you recall doing as a child?`n`n");
	modulehook("choose-specialty");
}
if (navcount() == 0) {
	clearoutput();
	page_header("No Specialties Installed");
	output("Since there are no suitable specialties available, we'll make you a student of the mystical powers and get on with it.");
	// This is someone who will definately have the rights to install
	// modules.
	if ($session['user']['superuser'] & (SU_MEGAUSER|SU_MANAGE_MODULES)) {
		output("You should go into the module manager off of the super user grotto, install and activate some specialties.");
	} else {
		output("You might want to ask your admin to install some specialties, as they are quite fun (and helpful).");
	}
	$session['user']['specialty'] = "MP";
	addnav("Continue","newday.php?continue=1$resline");
	page_footer();
}else{
	page_footer();
}
?>
