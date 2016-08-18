<?php

function blurry_getmoduleinfo()
{
    $info = [
        'name' => 'Blurry Vision',
        'author'=> 'Stephen Kise',
        'version' => '0.1',
        'category' => 'Miscellaneous',
        'description' =>
            'Skews your &quote;vision&quote; if you are drunk, rendering the navigation a mess.',
        'download' => 'nope',
        'requires' => [
            'drinks' => '1.1 | John Collins and JT Traub, core_module',
        ],
    ];
    return $info;
}

function blurry_install()
{
    module_addhook('everyfooter-loggedin');
    return true;
}

function blurry_uninstall()
{
    return true;
}

function blurry_dohook($hook, $args)
{
    switch ($hook) {
        case 'everyfooter-loggedin':
            global $navbysection;
            $drunkeness = get_module_pref('drunkeness');
            if (file_exists('modules/drinks/drunkenize.php') && $drunkeness != 0) {
                require_once('modules/drinks/drunkenize.php');
                foreach ($navbysection as $section => $navs) {
                    for ($i = 0; $i < count($navs); $i++) {
                        // Support to fix villagenav() and other arrayed navigation titles.
                        if (is_array($navs[$i][0])) {
                            $navs[$i][0] = sprintf_translate($navs[$i][0]);
                        }
                        if (strpos($navs[$i][0], '?') !== false) {
                            $navTitle = explode('?', $navs[$i][0]);
                            if (count($navTitle) > 2) {
                                $navTitle[2] = drinks_drunkenize($navTitle[2], $drunkeness);
                                $navTitle[2] = str_replace('*hic*', '', $navTitle[2]);
                            }
                            else {
                                $navTitle[1] = drinks_drunkenize($navTitle[1], $drunkeness);
                                $navTitle[1] = str_replace('*hic*', '', $navTitle[1]);
                            }
                            $navTitle[1] = str_replace('*hic*', '', $navTitle[1]);
                            $navTitle = implode('?', $navTitle);
                            $navbysection[$section][$i][0] = stripslashes($navTitle);
                            unset($navTitle);
                        }
                        else {
                            $navbysection[$section][$i][0] = drinks_drunkenize($navs[$i][0], $drunkeness);
                        }
                    }
                }
            }
            break;
    }
    return $args;
}
