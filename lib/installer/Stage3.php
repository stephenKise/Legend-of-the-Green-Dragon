<?php

global $mysqli_resource;
$superUser = SU_MEGAUSER | SU_EDIT_USERS | SU_EDIT_PETITIONS |
        SU_MANAGE_MODULES;
$accounts = db_prefix('accounts');
$name = mysqli_real_escape_string($mysqli_resource, httppost('name'));
$pass = md5(md5(stripslashes(httppost('pass'))));
if ($name > '' && $pass > '') {
    $sql = db_query("SELECT count(*) AS total FROM $accounts");
    $row = db_fetch_assoc($sql);
    if ($row['count'] > 0) {
        redirect('home.php');
        return;
    }
    $sql = db_query(
        "INSERT INTO $accounts
        (login, password, superuser, name, ctitle, regdate)
        VALUES
        ('$name', '$pass', $superUser, '`@Dev`0 $name', '`@Dev`0',
        NOW())"
    );
    if (!db_error()) {
        output(
            "`@Your superuser account has been created!Enjoy your 
            server, $name`@!"
        );
        blocknav('installer.php', true);
        addnav('Return to Home', 'home.php');
    }
    return;
}
$name = htmlent($name);
$submit = translate_inline('Create Superuser');
output("`@Now all you need is a superuser account!`n`n");
rawoutput("<form action='installer.php?stage=$stage' method='POST'>");
output("`^Name: ");
rawoutput("<input name='name' value='$name'>");
output("`n`^Password: ");
rawoutput(
    "<input name='pass' type='password'>
    <br><input type='submit' value='$submit' class='button'>
    </form>"
);
