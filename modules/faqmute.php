<?php

function faqmute_getmoduleinfo()
{
    $info = [
        'name' => 'FAQ Mute',
        'author' => 'Stephen Kise',
        'version' => '1.0',
        'category' => 'Administrative',
        'description' =>
            'Do not allow the players to use the commentary system until they read the FAQ, based on Booger\'s Newbie Mute.',
        'download' => 'core_module',
        'prefs' => [
            'seen_faq' => 'Has the player seen the FAQ, bool| 0',
        ],
    ];
    return $info;
}

function faqmute_install()
{
    module_addhook('insertcomment');
    module_addhook('mailfunctions');
    module_addhook('faq-posttoc');
    return true;
}

function faqmute_uninstall()
{
    return true;
}

function faqmute_dohook($hook, $args)
{
    global $session;
    $seen = get_module_pref('seen_faq');
    if ($seen == 0) {
        switch ($hook) {
            case 'insertcomment':
                $args['mute'] = 1;
                $args['mutemsg'] = translate_inline('`n`$You have to read the FAQ before you can post comments. You can find it in any town.`0`n');
                break;
            case 'mailfunctions':
                array_push(
                    $args,
                    ['petition.php?op=faq', 'Read the FAQ']
                );
                if (httpget('op') == 'write') {
                    $session['message'] = '`$Unfortunately you need to read the FAQ before you can write mail.';
                    header('Location: mail.php');
                }
                break;
            case 'faq-posttoc':
                set_module_pref('seen_faq', 1);
                break;
        }
    }
    return $args;
}
