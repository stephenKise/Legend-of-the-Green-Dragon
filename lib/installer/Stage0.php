<?php

output("`@`c`bWelcome to the Legend of the Green Dragon installer!`b`c`0");
if (DB_CHOSEN) {
    global $mysqli_resource, $session;
    $password = mysqli_real_escape_string(
        $mysqli_resource,
        httppost('password')
    );
    $login = mysqli_real_escape_string(
        $mysqli_resource,
        httppost('name')
    );
    $megaUser = SU_MEGAUSER;
    $accounts = db_prefix('accounts');
    $accountCheck = db_query(
        "SELECT count(*) AS c FROM $accounts WHERE superuser & $megaUser"
    );
    $result = db_fetch_assoc($accountCheck);
    if ($result['c'] > 0) {
        $authenticate = true;
    }
    if (httppost('name') != '') {
        if (getsetting('installer_version', '-1') != '-1') {
            $password = md5(md5($password));
        }
        $sql = db_query(
            "SELECT * FROM $accounts
            WHERE login = '$login'
            AND password = '$password'
            AND superuser & $megaUser
            LIMIT 1"
        );
        if ($num = db_num_rows($sql) == 0) {
            $authenticate = true;
            output("`\$An account with proper permissions was not found!`n");
        } else {
            $session['user'] = db_fetch_assoc($sql);
            redirect('installer.php?stage=2');
        }
    }
}

if ($authenticate) {
    $loginTemplate = templatereplace(
        'login',
        [
        'username' => 'Username',
        'password' => 'Password',
        'button' => 'Login'
            ]
    );
    $session['installer_stage'] = 0;
    output(
        "`2It seems as if you need to make some updates to the LotGD server.
        Please log in to an account that has administrative access, so we can
        start the update process.`n`n"
    );
    rawoutput(
        "<form action = 'installer.php?stage=0' method = 'POST'>
        $loginTemplate
        </form>"
    );
    return;
}

output(
    "`n`2You are moments away from setting up your own Legend of the Green Dragon
    (LotGD) server! In order to install and use this open-sourced server, you
    must agree to the license under which it is deployed and developed under.`n"
);
output(
    "`nThis game server is a small project into which Eric Stevens, JT Traub,
    DragonPrime and their community have invested tremendous effort into.
    This open-sourced software is provided to you free of charge. Please
    understand that if you modify the copyright, or violate the license, you
    are not only breakng international copyright law, but you are also
    defeating the spirit of open source. You should also know that it is within
    our rights to require you to permanently cease running a LotGD server
    if you care caught breaking the license at any moment.`n"
);
output(
    "`n`QBy proceeding with the installation, you agree to the
    `0<a target='_blank' href='%s'>Creative Commons license</a>`Q as well as 
    the `0<a target='_blank' href='LICENSE.txt'>license restrictions</a> `Qthat
    are in place!",
    "http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode",
    true
);

$session['stagecompleted'] = 0;
