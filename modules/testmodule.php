<?php
// addnews ready
// mail ready
// translator ready
// Sample LoGD Module File
//
// In all of the below, replace modulename with the short name of your
// module.  Short names are the file name of the module without the .php
// extenstions.  Valid module file names can only contain, letters, numbers
// and underscores.
//
// To build a new module, it must contain at least the following functions:
//   modulename_getmoduleinfo()
//   modulename_install()
//   modulename_uninstall()
// And optionally one or more of the following
//   modulename_dohook() [called on any hooks registered via module_addhook]
//   modulename_runevent() [called on any registered eventhooks]
//   modulename_run() [called via runmodule.php for links into module via nav]
//
// Modules may ALSO contain other functions which are considered to be private
// but they must be named in the same fashion (modulename_functionname) since
// PHP uses a global space for function names and if two functions have the
// same name, it is an error (at the PHP level).
//
// The API for these functions follows.  ALL entrypoints to the modules from
// the main game code go through these functions:
//
// modulename_getmoduleinfo():
// Arguments:    None
// Return value: An array containing at least the following keys:
//               name            => The formal name of the module, displayed
//                                  in configuration screens.
//               category        => The type of module this is.  The name is
//                                  arbitrary and just used for grouping on
//                                  configuration screens.
//               author          => The name of the module's author(s),
//                                  displayed in the module manager.
//               version         => The version of the module, displayed in
//                                  the module manager.
//               It may also contain the following keys but need not:
//               settings        => An array defining global settings for the
//                                  module.
//               prefs           => An array defining per-user settings for
//                                  the module.
//               prefs-drinks    => An array defining module properties which
//                                  modify specific drinks.
//               prefs-mounts    => An array defining module properties which
//                                  modify specific mounts.
//               prefs-creatures => An array defining module properties which
//                                  modify specific creatures.
//               All of the above arrays are of a form similar to what would
//               be passed to the showform function and are used for various
//               editors.  The differences it that a default value can be
//               appended to the definition string by "|<default>".  Any
//               value in the prefs array which is named as "user_<name>"
//               will be shown to the user in their preferences screen and
//               can be changed by them there.
// Purpose:      Allows the various parts of the system to glean information
//               about the module.
//
// modulename_install():
// Arguments:    None
// Return value: true on successful module install
//               false on unsuccessful module install
// Purpose:      Allows the module to set itself up when it is first
//               introduced to the game.  This might include the creation
//               of module tables, and must include the establishment of any
//               module hooks that the module uses (see module_addhook()) or
//               any event hooks the module users (see module_addeventhook())
//
// modulename_uninstall():
// Arguments:    none
// Return value: true on successful module uninstall
//               false on unsuccessful module uninstall
// Purpose:      Allows the module to destroy any data that it has
//               established in the database.  After this function has been
//               called, the module's hooks, settings, and user prefs will
//               be destroyed by the main game logic, so this is only needed
//               for tables the module itself creates.
//
// modulename_dohook(hookname,args):
// Arguments:    hookname: name of hook this functions is being called on.
//                         (since modules may respond to multiple hooks)
//               args:     array of arguments to define context for operation.
// Return value: Optionally modified array of arguments which was passed in.
// Purpose:      Allows the module to react to and take action on various
//               hooks defined within the main game or within other modules.
//               The action taken on those hooks is limited only by the
//               programmers imagination.  This module will only be called
//               for hooks on which it has registered an interst (see
//               module_addhoook().
//
// modulename_runevent(eventtype):
// Arguments:    eventtype; type of event which this is being invoked for
// Return value: none
// Purpose:      Allows the module to act as a special event.  This module
//               will only be called for events which the module has
//               registered. (see module_addeventhook()).  The action taken
//               during execution are only limited by the imagination of the
//               programmer.
//
// modulename_run():
// Arguments:    none
// Return value: none
// Purpose:      Allows the module to act as a navigation destination to
//               provide editors or other interactive content to the user.
//               This function is called from runmodule.php.  It is up to
//               the function to establish the context of the call; as with
//               other PHP files, this would typically be done by passing
//               values in the URL.  When calling your own modules (eg,
//               through nav hooks), link to
//               runmodule.php?module=modulename&arg1=val1&arg2=val2...
//               If you have not programmed for LoGD before, please note
//               that ALL SITE NAVIGATION MUST BE PASSED THROUGH THE addnav()
//               FUNCTION.  addnav() takes 2 arguments, display text, and
//               link location.  When display text is a blank string, the
//               link is added in to the allowed navs with out adding an entry
//               to the navigational area.  When the link is blank, the nav
//               item is assumed to be a nav heading, and is not clickable.
//               When both are supplied, a standard link is presented to the
//               user in the normal navigational area.
//
// The following functions are made available to modules to facilitate
// interaction with the game, and to enable storage of simple information for
// the module as a whole, or the individual user:
// module_addhook()  (only used during module installation)
// module_addeventhook() (only used during module installation)
// get_module_setting()
// set_module_setting()
// get_module_pref()
// set_module_pref()
//
// Modules MAY call other defined functions or import other libraries as
// needed, but the above are a minimum and will always be accessible.
//
// The API for these functions follow:
//
// module_addhook(hookname):
// Arguments:    hookname:     A string that represents the hook location.
//                             This is matched against any location throughout
//                             the rest of code where
//                             modulehook("hookname", $args) is called.
//                             Whenever this line gets executed, your module
//                             hook will be called with the hook name and the
//                             given array of arguments if you are registered
//                             on that hookname.
// Return value: none
// Purpose:      Used during module installation, this tells the game what
//               hook locations the module attaches to.
//
// module_addhook_priority(hookname,priority):
// Arguments:    hookname:     A string that represents the hook location.
//                             This is matched against any location throughout
//                             the rest of code where
//                             modulehook("hookname", $args) is called.
//                             Whenever this line gets executed, your module
//                             hook will be called with the hook name and the
//                             given array of arguments if you are registered
//                             on that hookname.
//               priority:     An integer value indicating the priority with
//                             which this hook will execute.  This affects
//                             only the execution order within the hook, against
//                             other functions registered on the same hook.
//                             Anything less than 50 will provide early execution
//                             and anything over 50 will provide late execution
// Return value: none
// Purpose:      Used during module installation, this tells the game what
//               hook locations the module attaches to, and what order to
//               execute those hooks in.
//
// module_addeventhook(eventtype,chancestring):
// Arguments:    eventtype:    A string which defines the class of special
//                             events.  It is matched against any location
//                             in the code where module_events("type",...) is
//                             called to be eligible to provide events of
//                             that type.
//               chancestring: This string is php code which will be eval()'d
//                             by the main engine at the time an event is to
//                             chosen to provide a (possibly) dynamic value
//                             for the chance of this event.  This code
//                             should be kept simple.  Look at examples of
//                             use for ideas on what can be done.
// Return value: none
// Purpose:      Used during module intallation, this tells the game that
//               this module provides a special event of the given type
//               and a way to determine the probability of this event
//               occuring assuming any event occurs.
//
// get_module_setting(settingname[, modulename]):
// Arguments:    settingname: the name of the setting you wish to retrieve
//               modulename:  optional argument that specifies the short name
//               of the module you are retrieving a setting for.  If omitted,
//               the current module is assumed.
// Return value: The value of the appropriate setting, or the default if it
//               has not been set.
// Purpose:      Allows the module to retrieve a value which is common across
//               all users, for example a configuration option for the module.
//
// set_module_setting(settingname, value[, modulename]):
// Arguments:    settingname: the name of the setting you wish to store
//               value:       the value you wish to place in this setting.
//               modulename:  optional argument that specifies the short name
//                            of the module you are modifying a setting for.
//                            If omitted, the current module is assumed.
// Return value: none
// Purpose:      Allows the module to store a value which is common across
//               all users, for example a configuration option for the module.
//
// get_module_pref(settingname[, modulename[, user]]):
// Arguments:    settingname: the name of the preference you wish to retrieve
//               modulename:  optional argument that specifies the short name
//                            of the module you are retrieving a preference
//                            for. If omitted, the current module is assumed.
//               user:        optional argument which specifies the userid
//                            for the player you are retrieving the
//                            preference for.  If omitted, the current user
//                            is assumed.
// Return value: The value of the appropriate setting, or the default if it
//               has not been set.
// Purpose:      Allows the module to retrieve a value which is kept for just
//               this user, for the module.
//
// set_module_pref(settingname, value[, modulename]):
// Arguments:    settingname: the name of the preference you wish to store
//               value:       the value you wish to place in this preference.
//               modulename:  optional argument that specifies the short name
//                            of the module you are modifying a preference
//                            for.  If omitted, the current module is assumed.
// Return value: none
// Purpose:      Allows a module to store data pertaining to this module and
//               user together.  For example a user preference for this
//               module.

require_once("lib/http.php");

function testmodule_getmoduleinfo(){
	$info = array(
		"name"=>"Testing Module",
		"author"=>"Eric Stevens",
		"version"=>"1.0",
		"category"=>"Testing",
		"download"=>"core_module",
		"description"=>"This is a simple test module.",
	);
	return $info;
}

function testmodule_install(){
	debug("Installing this module.`n");
	// module_addhook hooks on named hook locations, such as 'dragonkill'
	// or 'newday'.
	module_addhook("testing");
	module_addhook("newday");
	//Because we already have a module hook on newday, we must provide an alternate
	//function name for additional newday hooks.  Each module can only have one
	//hook/function combo on each hook location.
	module_addhook_priority("newday",25,"testmodule_newday_top_priority");
	module_addhook_priority("newday",75,"testmodule_newday_low_priority");
	// Stick a silly little event onto the village and the inn and graveyard
	module_addeventhook("village", "return 100;");
	module_addeventhook("inn", "return 100;");
	module_addeventhook("graveyard", "return 100;");
	return true;
}

function testmodule_uninstall(){
	output("Uninstalling this module.`n");
	return true;
}

function testmodule_dohook($hookname, $args){
	switch($hookname){
	case "newday":
		output("`nAnd the game masters are playing around with the test module.`n");
		break;
	case "testing":
		addnav("Test Module");
		addnav("Try out this module",
				"runmodule.php?module=testmodule&testarea=1");
		addnav("Try out something else",
				"runmodule.php?module=testmodule&testarea=2");
		break;
	}
	return $args;
}

function testmodule_newday_top_priority($hookname, $args){
	switch($hookname){
	case "newday":
		output("`nThis is a top priority hook on newday.`n");
		break;
	}
	return $args;
}

function testmodule_newday_low_priority($hookname, $args){
	switch($hookname){
	case "newday":
		output("`nThis is a low priority hook on newday.`n");
		break;
	}
	return $args;
}

function testmodule_runevent($type)
{
	switch($type) {
	case "inn":
	case "graveyard":
		// This is mainly to illustrate why a lot of the modules do $from
		if ($type == "inn") $from = "inn.php";
		else $from = "graveyard.php";
		if ($type == "inn") output("You stumble on a loose floor board.");
		else output("You trip over a loose bone.");
		addnav("Catch your balance", $from);
		break;
	case "village":
		// This just dumps you right back to the village with no interaction
		output("You wrinkle your nose at the stench of some garbage.");
		break;
	}
}

function testmodule_run(){
	switch(httpget('testarea')){
	case "1":
		$value = get_module_setting("testvalue");
		$value++;
		set_module_setting("testvalue",$value);
		page_header("Test area 1");
		output("This is test area 1, it has been accessed %s times.", $value);
		$value = get_module_pref("testvalue");
		$value++;
		set_module_pref("testvalue",$value);
		output("`n`nThis is your %s time here.", $value);
		addnav("Return","superuser.php");
		break;
	case "2":
		$value = get_module_setting("testvalue1");
		$value++;
		set_module_setting("testvalue1",$value);
		page_header("Test area 2");
		output("This is test area 2, it has been accessed %s times.", $value);
		$value = get_module_pref("testvalue2");
		$value++;
		set_module_pref("testvalue2",$value);
		output("`n`nThis is your %s time here.", $value);
		addnav("Return","superuser.php");
		break;
	}
	page_footer();
}
?>
