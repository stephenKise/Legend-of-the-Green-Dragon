<?php

/**
 * YoM to SMS
 * 
 * Send a text to players when they receive a mail from another player
 * 
 * @author  ArthuruhtrA <http://dragonprime.net/index.php?action=profile;u=28816>
 * @author  Stephen.Kise <http://dragonprime.net/index.php?action=profile;u=76086>
 * @link http://dragonprime.net/index.php?topic=12773 Discussion
 */

function textme_getmoduleinfo()
{
    $info = [
        'name' => 'YoM to SMS',
        'author'=> 'ArthuruhtrA, Stephen Kise',
        'version' => '0.1b',
        'category' => 'Miscellaneous',
        'description' =>
            'Send a text to players when they receive a mail from another player.',
        'download' => 'https://github.com/stephenKise/Legend-of-the-Green-Dragon/blob/dev/modules/textme.php',
        'prefs' => [
            'SMS Preferences, title',
            'user_number' => 'What cell number do you wish to use?, int| ',
            'Please use a ten digit cell number&comma; DO NOT put the country code!, note',
        ],
    ];
    $i = 0;
    foreach (textme_getcarriers() as $carrier => $email) {
        $i++;
        $string .= "$email, $carrier";
        if ($i < count(textme_getcarriers())) {
            $string .= ', ';
        }
    }
    $info['prefs']['user_carrier'] = "Which carrier do you use?, enum, none, None, $string";
    return $info;
}

function textme_install()
{
    module_addhook('mailfunctions');
    return true;
}

function textme_uninstall()
{
    return true;
}

function textme_dohook($hook, $args)
{
    if (httpget('op') == 'send') {
        require_once('lib/names.php');
        textme_sendmail(httpallpost(), get_player_basename());
    }
    return $args;
}

function textme_getcarriers()
{
    return [
        "Alltel" => "sms.alltelwireless.com",
        "ATT" => "txt.att.net",
        "Boost Mobile" => "sms.myboostmobile.com",
        "Cricket" => "sms.mycricket.com",
        "Metro PCS" => "mymetropcs.com",
        "Sprint" => "messaging.sprintpcs.com",
        "T-Mobile" => "tmomail.net",
        "US Cellular" => "email.uscc.net",
        "Verizon" => "vtext.com",
        "Virgin Mobile" => "vmobile.com",
    ];
}

function textme_sendmail($post = [], $from = 'LotGD Staff')
{
    $accounts = db_prefix('accounts');
    $post['to'] = filter_var($post['to'], FILTER_SANITIZE_STRING);
    $post['body'] = trim(explode("---Original", $post['body'])[0]);
    $body = "From $from: \n{$post['body']}";
    $sql = db_query(
        "SELECT acctid FROM $accounts WHERE login = '{$post['to']}'"
    );
    $row = db_fetch_assoc($sql);
    $prefs = get_all_module_prefs('textme', $row['acctid']);
    foreach ($prefs as $key => $val) {
        $prefs[$key] = trim($val);
    }
    if ($prefs['user_number'] != '' && $prefs['user_carrier'] != 'none') {
        require_once('lib/sanitize.php');
        $checkSent = mail(
            "{$prefs['user_number']}@{$prefs['user_carrier']}",
            '',
            stripslashes(full_sanitize($body)),
            "From: textme@{$_SERVER['HTTP_HOST']}"
        );
        if (!$checkSent) {
            debuglog(
                "failed to send a message to {$post['to']} ({$prefs['user_number']}@{$prefs['user_carrier']})"
            );
        }
    }
}