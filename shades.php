<?php
// translator ready
// addnews ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");


tlschema("shades");

page_header("Land of the Shades");
addcommentary();
checkday();

if ($session['user']['alive']) redirect("village.php");
output("`\$You walk among the dead now, you are a shade. ");
output("Everywhere around you are the souls of those who have fallen in battle, in old age, and in grievous accidents. ");
output("Each bears telltale signs of the means by which they met their end.`n`n");
output("Their souls whisper their torments, haunting your mind with their despair:`n");

output("`nA sepulchral voice intones, \"`QIt is now %s in the world above.`\$\"`n`n",getgametime());
modulehook("shades", array());
commentdisplay("`n`QNearby, some lost souls lament:`n", "shade","Despair",25,"despairs");

addnav("Log out","login.php?op=logout");
addnav("Places");
addnav("The Graveyard","graveyard.php");

addnav("Return to the news","news.php");

tlschema("nav");

// the mute module blocks players from speaking until they
// read the FAQs, and if they first try to speak when dead
// there is no way for them to unmute themselves without this link.
addnav("Other");
addnav("??F.A.Q. (Frequently Asked Questions)", "petition.php?op=faq",false,true);

if ($session['user']['superuser'] & SU_EDIT_COMMENTS){
	addnav("Superuser");
	addnav(",?Comment Moderation","moderate.php");
}
if ($session['user']['superuser']&~SU_DOESNT_GIVE_GROTTO){
	addnav("Superuser");
  addnav("X?Superuser Grotto","superuser.php");
}
if ($session['user']['superuser'] & SU_INFINITE_DAYS){
	addnav("Superuser");
  addnav("/?New Day","newday.php");
}

tlschema();

page_footer();
?>