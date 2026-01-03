<?php
	output("`Q`bIMPORTANT`b`n`n");

	output("`&`b'Cities' Module:`b `7`HThis module `b*is*`b required`H. For compatibility please install the modified version that's included in the 'city_creator' Zip file.`n`n");
	output("`&`b'City Prefs' Module:`b `7`HThis module `b*is not*`b required`H, however if you already have it installed then you may also have other modules that use it. For compatibility please install the modified version that's included in the 'race_creator' Zip file instead of uninstalling it.`n`n");

	output("`Q`bGETTING STARTED`b`n`n");

	output("`&`b%s:`b `7When you installed this module the main city would have been created. You don't need to keep this, but doing so will allow you to block navs and modules from appearing in it.`n`n", getsetting('villagename', LOCATION_FIELDS));
	output("`&`bEric Stevens' Cities:`b `7To start you off, I have included the 4 villages that come with LotGD race modules. Romar for Humans, Glorfindal for Elves, Qexelcrag for Dwarves and Glukmoore for Trolls. Click the Eric Stevens' Cities link on the main page to install them.`n`n");

	output("`Q`bADDITIONAL`b`n`n");

	output("`&`b'City Prerequisites' Module:`b `7This module adds the ability to block travel access to cities depending on Dragon kills, alignment, charm, gender and lodge points.`n`n");
	output("`&`b'City Routes' Module:`b `7This module adds the ability to block travel access to cities depending on which city you're in. This allows you to create cities that can only be travelled to from certain cities.`n`n");

	output("`Q`bOTHER`b`n`n");

	output("`7On the form page you'll see various notes. Please follow these carefully. If you're stuck or have questions or find bugs or think of a cool feature that could be added then post in the discussions thread. Thanks. :)`n`n");

	addnav('Editor');
	addnav('Add a City',$from.'&op=edit');
	addnav('Main Page',$from);
?>