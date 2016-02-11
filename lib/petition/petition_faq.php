<?php
tlschema("faq");
popup_header("Frequently Asked Questions (FAQ)");
output("`^Welcome to Legend of the Green Dragon.`n`n");
output("`@You wake up one day, and you're in a village for some reason.");
output("You wander around, bemused, until you stumble upon the main village square.");
output("Once there you start asking lots of stupid questions.");
output("People (who are mostly naked for some reason) throw things at you.");
output("You escape by ducking into a nearby building and find a rack of pamphlets by the door.");
output("The title of the pamphlet reads: `&\"Everything You Wanted to Know About the LotGD, but Were Afraid to Ask.\"");
output("`@Looking furtively around to make sure nobody's watching, you open one and read:`n`n");
output("\"`#So, you're a Newbie.  Welcome to the club.");
output("Here you will find answers to the questions that plague you.");
output("Well, actually you will find answers to the questions that plagued US.");
output("So, here, read and learn, and leave us alone!`@\"`n`n");
output("`^`bContents:`b`0`n");

modulehook("faq-pretoc");
output("`^`bNew Player & FAQ`b`0`n");
$t = translate_inline("`@New Player Primer`0");
output_notl("&#149;<a href='petition.php?op=primer'>%s</a><br/>", $t, true);
$t = translate_inline("`@Frequently Asked Questions on Game Play (General)`0");
output_notl("&#149;<a href='petition.php?op=faq1'>%s</a><br/>", $t, true);
$t = translate_inline("`@Frequently Asked Questions on Game Play (with spoilers)`0");
output_notl("&#149;<a href='petition.php?op=faq2'>%s</a><br/>", $t, true);
$t = translate_inline("`@Frequently Asked Questions on Technical Issues`0");
output_notl("&#149;<a href='petition.php?op=faq3'>%s</a><br/>", $t, true);
modulehook("faq-toc");
modulehook("faq-posttoc");
output("`nThank you,`nthe Management.`n");
?>