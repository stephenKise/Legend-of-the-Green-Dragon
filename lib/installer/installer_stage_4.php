<?php
require_once("lib/dbwrapper.php");
if (httppostisset("DB_HOST")) {
	$session['dbinfo']['DB_HOST']=httppost("DB_HOST");
	$session['dbinfo']['DB_USER']=httppost("DB_USER");
	$session['dbinfo']['DB_PASS']=httppost("DB_PASS");
	$session['dbinfo']['DB_NAME']=httppost("DB_NAME");
	$session['dbinfo']['DB_USEDATACACHE']=(bool)httppost("DB_USEDATACACHE");
	$session['dbinfo']['DB_DATACACHEPATH']=httppost("DB_DATACACHEPATH");
}
output("`@`c`bTesting the Database Connection`b`c`2");
output("Trying to establish a connection with the database:`n");
ob_start();
$link = db_connect($session['dbinfo']['DB_HOST'], $session['dbinfo']['DB_USER'], $session['dbinfo']['DB_PASS']);
$error = ob_get_contents();
ob_end_clean();
if (!$link){
	output("`\$Blast!  I wasn't able to connect to the database server with the information you provided!");
	output("`2This means that either the database server address, database username, or database password you provided were wrong, or else the database server isn't running.");
	output("The specific error the database returned was:");
	rawoutput("<blockquote>".$error."</blockquote>");
	output("If you believe you provided the correct information, make sure that the database server is running (check documentation for how to determine this).");
	output("Otherwise, you should return to the previous step, \"Database Info\" and double-check that the information provided there is accurate.");
	$session['stagecompleted']=3;
}else{
	output("`^Yahoo, I was able to connect to the database server!");
	output("`2This means that the database server address, database username, and database password you provided were probably accurate, and that your database server is running and accepting connections.`n");
	output("`nI'm now going to attempt to connect to the LoGD database you provided.`n");
	if (httpget("op")=="trycreate"){
		require_once 'lib/installer/installer_functions.php';
		create_db($session['dbinfo']['DB_NAME']);
	}
	if (!db_select_db($session['dbinfo']['DB_NAME'])){
		output("`\$Rats!  I was not able to connect to the database.");
		$error = db_error();
		if ($error=="Unknown database '{$session['dbinfo']['DB_NAME']}'"){
			output("`2It looks like the database for LoGD hasn't been created yet.");
			output("I can attempt to create it for you if you like, but in order for that to work, the account you provided has to have permissions to create a new database.");
			output("If you're not sure what this means, it's safe to try to create this database, but you should double check that you've typed the name correctly by returning to the previous stage before you try it.`n");
			output("`nTo try to create the database, <a href='installer.php?stage=4&op=trycreate'>click here</a>.`n",true);
		}else{
			output("`2This is probably because the username and password you provided doesn't have permission to connect to the database.`n");
		}
		output("`nThe exact error returned from the database server was:");
		rawoutput("<blockquote>$error</blockquote>");
		$session['stagecompleted']=3;
	}else{
		output("`n`^Excellent, I was able to connect to the database!`n");
		define("DB_INSTALLER_STAGE4", true);
		output("`n`@Tests`2`n");
		output("I'm now going to run a series of tests to determine what the permissions of this account are.`n");
		$issues = array();
		output("`n`^Test: `#Creating a table`n");
		//try to destroy the table if it's already here.
		$sql = "DROP TABLE IF EXISTS logd_environment_test";
		db_query($sql,false);
		$sql = "CREATE TABLE logd_environment_test (a int(11) unsigned not null)";
		db_query($sql);
		if ($error=db_error()){
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`^Warning:`2 The installer will not be able to create the tables necessary to install LoGD.  If these tables already exist, or you have created them manually, then you can ignore this.  Also, many modules rely on being able to create tables, so you will not be able to use these modules.");
		}else{
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Modifying a table`n");
		$sql = "ALTER TABLE logd_environment_test CHANGE a b varchar(50) not null";
		db_query($sql);
		if ($error=db_error()){
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`^Warning:`2 The installer will not be able to modify existing tables (if any) to line up with new configurations.  Also, many modules rely on table modification permissions, so you will not be able to use these modules.");
		}else{
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Creating an index`n");
		$sql = "ALTER TABLE logd_environment_test ADD INDEX(b)";
		db_query($sql);
		if ($error=db_error()){
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`^Warning:`2 The installer will not be able to create indices on your tables.  Indices are extremely important for an active server, but can be done without on a small server.");
		}else{
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Inserting a row`n");
		$sql = "INSERT INTO logd_environment_test (b) VALUES ('testing')";
		db_query($sql);
		if ($error=db_error()){
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`\$Critical:`2 The game will not be able to function with out the ability to insert rows.");
			$session['stagecompleted']=3;
		}else{
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Selecting a row`n");
		$sql = "SELECT * FROM logd_environment_test";
		db_query($sql);
		if ($error=db_error()){
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`\$Critical:`2 The game will not be able to function with out the ability to select rows.");
			$session['stagecompleted']=3;
		}else{
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Updating a row`n");
		$sql = "UPDATE logd_environment_test SET b='MightyE'";
		db_query($sql);
		if ($error=db_error()){
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`\$Critical:`2 The game will not be able to function with out the ability to update rows.");
			$session['stagecompleted']=3;
		}else{
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Deleting a row`n");
		$sql = "DELETE FROM logd_environment_test";
		db_query($sql);
		if ($error=db_error()){
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`\$Critical:`2 The game database will grow very large with out the ability to delete rows.");
			$session['stagecompleted']=3;
		}else{
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Locking a table`n");
		$sql = "LOCK TABLES logd_environment_test WRITE";
		db_query($sql);
		if ($error = db_error()) {
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`\$Critical:`2 The game will not run correctly without the ability to lock tables.");
			$session['stagecompleted']=3;
		} else {
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Unlocking a table`n");
		$sql = "UNLOCK TABLES";
		db_query($sql);
		if ($error = db_error()) {
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`\$Critical:`2 The game will not run correctly without the ability to unlock tables.");
			$session['stagecompleted']=3;
		} else {
			output("`2Result: `@Pass`n");
		}
			output("`n`^Test: `#Deleting a table`n");
		$sql = "DROP TABLE logd_environment_test";
		db_query($sql);
		if ($error=db_error()){
			output("`2Result: `\$Fail`n");
			rawoutput("<blockquote>$error</blockquote>");
			array_push($issues,"`^Warning:`2 The installer will not be able to delete old tables (if any).  Also, many modules need to be able to delete the tables they put in place when they are uninstalled.  Although the game will function, you may end up with a lot of old data sitting around.");
		}else{
			output("`2Result: `@Pass`n");
		}
		output("`n`^Test: `#Checking datacache`n");
		if (!$session['dbinfo']['DB_USEDATACACHE']) {
			output("-----skipping, not selected-----`n");
		} else {
			$fp = @fopen($session['dbinfo']['DB_DATACACHEPATH']."/dummy.php","w+");
			if ($fp){
				if (fwrite($fp,	$dbconnect)!==false){
					output("`2Result: `@Pass`n");
				}else{
					output("`2Result: `\$Fail`n");
					rawoutput("<blockquote>");
					array_push($issues,"`^I was not able to write to your datacache directory!`n");
				}
				fclose($fp);
				@unlink($session['dbinfo']['DB_DATACACHEPATH']."/dummy.php");
			}else{
				output("`2Result: `\$Fail`n");
				array_push($issues,"`^I was not able to write to your datacache directory! Check your permissions there!`n");
			}
		}
		output("`n`^Overall results:`2`n");
		if (count($issues)==0){
			output("You've passed all the tests, you're ready for the next stage.");
		}else{
			rawoutput("<ul>");
			output("<li>".join("</li>\n<li>",$issues)."</li>",true);
			rawoutput("</ul>");
			output("Even if all of the above issues are merely warnings, you will probably periodically see database errors as a result of them.");
			output("It would be a good idea to resolve these permissions issues before attempting to run this game.");
			output("For you technical folk, the specific permissions suggested are: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER and LOCK TABLES.");
			output("I'm sorry, this is not something I can do for you.");
		}
	}
}
?>