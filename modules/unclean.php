<?php

// this is a module that should be able to keep track of recent dirty
// commentary before it is filtered.
// the unclean commentary should show up in the bad word editor screen, if
// there are any, with the option to show a nav that allows the most recent
// one to be deleted, if it exists.
// the last unclean commentary made by a specific user should show up in
// the user's character biography.
// the module should also show how many times the player has tripped the
// filter in the character biography.

function unclean_getmoduleinfo()
{
	$info = array(
		"name"=>"Unclean Commentary Tracker",
		"category"=>"Administrative",
		"author"=>"dying",
		"version"=> "0.23",
		"download"=>"core_module",
		"settings"=> array(
			"Unclean Commentary Settings, title",
			"hidenav"=>"Hide nav to clear most recent comment,bool|0",
			"maxhistory"=>"Maximum number of comments to keep in history,int|5",
			"comments"=> "History,viewonly"
		),
		"prefs"=>array(
			"Unclean Commentary Preferences, title",
			"This counter does not increment if the user has the SU_EDIT_COMMENTS flag set.,note",
			"filthiness"=>"How many times has this user tripped the filter?,int|0",
			"usercomment"=>"Last unclean comment"
		)
	);
   return $info;
}

function unclean_install()
{
   module_addhook("censor");
   module_addhook("header-badword");
   module_addhook("biostat");

   return true;
}

function unclean_uninstall()
{
   return true;
}

function unclean_dohook($hookname, $args)
{
	switch ($hookname) {
	case "censor":
		global $session;

		$page = substr($_SERVER['REQUEST_URI'], 1);

		if ( strstr( $page, "logdnet.php" ) === $page )
			break;   // don't log filter tripping on logdnet.php

		$serializedcomments = get_module_setting("comments");

		if ($serializedcomments == "") $comments = array();
		else $comments = unserialize ( $serializedcomments );

		$maxhistory = get_module_setting("maxhistory");

		if ($maxhistory < 0) {
			$maxhistory = 0;
			set_module_setting ("maxhistory", $maxhistory);
		}

		if ($maxhistory < 2) $comments = array();
		else $comments = unclean_prunecomments($comments, $maxhistory - 1);

		$username = $session['user']['name'];
		if ($username == "") $username = "`\$no name";

		if ( strstr( $page, "village.php" ) === $page )
			$city = " {`5" . $session['user']['location'] . "`0}";
		else $city = "";

		if ($maxhistory > 0)
			array_push($comments, "`0[`&" . $username . "`0]"
					. " (`7" . $page . "`0)"
					. $city
					. " `#" . $args["input"] . "`0");

		set_module_setting("comments", serialize($comments));

		// add unclean comment in user history
		set_module_pref("usercomment", $args["input"]);

		set_module_pref("filthiness", get_module_pref("filthiness") + 1);
		break;
	case "header-badword":
		$serializedcomments = get_module_setting("comments");

		if ($serializedcomments == "") $comments = array();
		else $comments = unserialize($serializedcomments);

		$maxhistory = get_module_setting("maxhistory");

		if (httpget("unclean_op") === "clearfirst") {
			array_pop($comments);
			set_module_setting("comments", serialize($comments));
		}
		if ($maxhistory < 0) {
			$maxhistory = 0;
			set_module_setting("maxhistory", $maxhistory);
		}

		if (count($comments) > $maxhistory) {
			$comments = unclean_prunecomments($comments, $maxhistory);
			set_module_setting("comments", serialize($comments));
		}

		$numhistory = count($comments);

		if ($numhistory) {
			// there is at least one unclean comment in the history
			if (!(get_module_setting("hidenav"))) {
				addnav("Unclean Module");
				addnav("Clear Most Recent Comment",
						"badword.php?unclean_op=clearfirst");
			}

			output("`b`^Recently filtered comment%s`b`n",
					($numhistory > 1) ? "s" : "" );

			$reversecomments = array_reverse($comments);

			foreach($reversecomments as $comment)
				output_notl($comment . "`n");

			output_notl("`n");
			rawoutput("<hr>");
			output_notl("`n");
		}
		break;
	case "biostat":
		global $session;

		if ($session['user']['superuser'] & SU_EDIT_COMMENTS) {
			$usercomment = get_module_pref("usercomment",
					false, $args['acctid']);
			$userfilthiness = get_module_pref("filthiness", false,
					$args['acctid']);

			if ($userfilthiness != 0)
				output("`^Filter Trips: `@%s`0`n", $userfilthiness);

			if ($usercomment != "")
				output("`^Last Unclean Comment: `@%s`n", $usercomment);
		}
		break;
	}
	return $args;
}

function unclean_prunecomments($comments, $size)
{
   if (count($comments) > $size) {
	   $reversecomments = array_reverse($comments);

	   do
		   array_pop($reversecomments);
	   while (count($reversecomments) > $size);

	   return (array_reverse($reversecomments));
   } else return $comments;
}

?>
