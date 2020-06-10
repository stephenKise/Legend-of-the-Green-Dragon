<?php

require_once "lib/installer/installer_functions.php";
rawoutput("<form action='installer.php?stage=4' method='POST'>");
output("`@`c`bDatabase Connection Information`b`c`2");
output("In order to run Legend of the Green Dragon, your server must have access to a MySQL database.");
output("If you are not sure if you meet this need, talk to server's Internet Service Provider (ISP), and make sure they offer MySQL databases.");
output("If you are running on your own machine or a server under your control, you can download and install MySQL from <a href='http://www.mysql.com/' target='_blank'>the MySQL website</a> for free.`n", true);
if (file_exists("dbconnect.php")) {
    output("There appears to already be a database setup file (dbconnect.php) in your site root, you can proceed to the next step.");
} else {
    output("`nIt looks like this is a new install of Legend of the Green Dragon.");
    output("First, thanks for installing LoGD!");
    output("In order to connect to the database server, I'll need the following information.");
    output("`iIf you are unsure of the answer to any of these questions, please check with your server's ISP, or read the documentation on MySQL`i`n");

    output("`nWhat is the address of your MySQL server?`n");
    rawoutput("<input name='DB_HOST' value=\"" . htmlentities($session['dbinfo']['DB_HOST'], ENT_COMPAT, getsetting("charset", "ISO-8859-1")) . "\" required>");

    output("`nWhat is the username you use to connect to the database server?`n");
    rawoutput("<input name='DB_USER' value=\"" . htmlentities($session['dbinfo']['DB_USER'], ENT_COMPAT, getsetting("charset", "ISO-8859-1")) . "\" required>");
    output("`nWhat is the password for this username?`n");
    rawoutput("<input type='password' name='DB_PASS'>");

    output("`nWhat is the name of the database you wish to install LoGD in?`n");
    rawoutput("<input name='DB_NAME' value=\"" . htmlentities($session['dbinfo']['DB_NAME'], ENT_COMPAT, getsetting("charset", "ISO-8859-1")) . "\" required>");

    output("`nDo you want to use datacaching (high load optimization)?`n");
    rawoutput("<select id='DB_USEDATACACHE' name='DB_USEDATACACHE'>");
    rawoutput("<option value=\"0\" " . ($session['dbinfo']['DB_USEDATACACHE'] ? 'selected=\"selected\"' : '') . ">" . translate_inline("No") . "</option>");
    rawoutput("<option value=\"1\" " . (!$session['dbinfo']['DB_USEDATACACHE'] ? 'selected=\"selected\"' : '') . ">" . translate_inline("Yes") . "</option>");
    rawoutput("</select><div id='DB_DATACACHE' style='display: none'>");

    output("`nIf yes, what is the path to the datacache directory?`n");
    rawoutput("<input name='DB_DATACACHEPATH' value=\"" . htmlentities($session['dbinfo']['DB_DATACACHEPATH'], ENT_COMPAT, getsetting("charset", "ISO-8859-1")) . "\"></div>");

    /*
      $yes = translate_inline("Yes");
      $no = translate_inline("No");
      output("`nShould I attempt to create this database if it does not exist?`n");
      rawoutput("<select name='DB_CREATE'><option value='1'>$yes</option><option value='0'>$no</option></select>");
      tip("If this database doesn't exist, I'll try to create it for you if you like.");
     */
    $submit = "Test this connection information.";
    output_notl("`n`n<input type='submit' value='$submit' class='button'>", true);
}
rawoutput("</form>");
