<?php

function abigail_getmoduleinfo()
{
    $info = [
        'name' => 'Abigail, the Street Hawker',
        'author' => 'Shannon Brown, changes by Christian Rutsch',
        'version' => '1.1',
        'category' => 'Village Specials',
        'download' => 'core_module',
        'requires' => [
            'lovers' => '1.0|Eric Stevens, core_module',
        ],
        'settings' => [
            'cost' => 'Number of gems the items cost, int| 2',
            'charm_chance' =>
                'What is the chance that the partner will like the gift?, range, 5, 100, 5| 80',
        ],
        'prefs' => [
            'bought' => 'Purchased gift today?, bool| 0',
            'trinket' => 'Last gift offered?, text| Nothing',
            'liked' => 'Did this user like their gift?, bool| 0',
            'angry' => 'How many days will Abigail leave this user alone?, int| 0',
        ],
    ];
    return $info;
}

function abigail_install()
{
    module_addhook('newday');
    module_addeventhook(
        'village',
        "\$bought = get_module_pref(\"bought\", \"abigail\"); return (\$bought ? 0 : 50);"
    );
    module_addeventhook(
        'gardens',
        "\$bought = get_module_pref(\"bought\", \"abigail\"); return (\$bought ? 0 : 50);"
    );
    return true;
}

function abigail_uninstall()
{
    return true;
}

function abigail_dohook($hook, $args)
{
    require_once('lib/partner.php');
    global $session;
    $partner = get_partner(true);
    $angry = get_module_pref('angry');
    if ($angry > 0) {
        increment_module_pref('angry', -1);
        set_module_pref('bought', 1);
    }
    else {
        set_module_pref('bought', 0);
    }
    if (get_module_pref('bought') == 1) {
        output(
            "`n`n`5Just as you get ready to set off for the day, a messenger boy hands you a note from `^%s`5.`0",
            $partner
        );
        if (get_module_pref('liked') == 1) {
            output(
                "`5\"`%What a wonderful surprise! Your gift was very thoughtful! I shall show everyone!`5\"`n`n`0"
            );
            output("`^You gain some charm!`n`0");
            $session['user']['charm'] += 2;
        }
        else {
            output("`5\"`%I can't believe you think you can win my approval with a cheap gift like that!`5\"`n`n`0");
            output("`^You `\$lose`^ some charm.`n`0");
            if ($session['user']['charm'] > 2) {
                $session['user']['charm'] -= 2;
            }
            else {
                $session['user']['charm'] = 0;
            }
        }
    }
    set_module_pref('liked', 0);
    return $args;
}

function abigail_runevent($type, $link)
{
    require_once('lib/partner.php');
    global $session;
    $session['user']['specialinc'] = "module:abigail";
    $partner = get_partner(true);
    $cost = get_module_setting('cost');
    $trinket = get_module_pref('trinket');
    $op = httpget('op');
    switch ($op) {
        case 'leave':
            $session['user']['specialinc'] = '';
            output(
                "`5Not having any gems to buy a gift for `^%s`5, you wander sadly away.`n`n`0",
                $partner
            );
            addnav('Return to whence you came', $link);
            break;
        case 'nope':
            $session['user']['specialinc'] = '';
            output(
                "`7You decide not to buy the %s from Abigail. You are sure that `^%s `7would not like something like that anyways...`n`0",
                $trinket,
                $partner
            );
            addnav('Return to whence you came', $link);
            break;
        case 'shout':
            $session['user']['specialinc'] = '';
            output(
                "Abigail just shakes her head and walks away, leaving you with a feeling of great relief."
                );
            set_module_pref('angry', rand(1, 10));
            set_module_pref('bought', 1);
            break;
        case 'shop':
            set_module_pref('bought', 1);
            $session['user']['gems'] -= $cost;
            debuglog("spent $cost gems on a gift for their lover");
            output(
                "`7Agreeing to buy the %s, you hand Abigail her payment. ",
                $trinket
            );
            output(
                "`7She beems with glee and darts off, `5\"Do not worry, `^%s`5, I will make sure that `^%s`5 receives your gift!\"`n`n`0",
                $session['user']['name'],
                $partner
            );
            if ($session['user']['marriedto'] != INT_MAX && $session['user']['marriedto'] != 0) {
                require_once('lib/systemmail.php');
                $subject = '`%Abigail has delivered a gift to you!';
                $body = [
                    "`^%s`2 has delivered a %s as a gift.",
                    $session['user']['name'],
                    $trinket
                ];
                systemmail($session['user']['marriedto'], $subject, $body);
                if (e_rand(1, 100) <= get_module_setting('charm_chance')) {
                    increment_module_pref('liked');
                }
                else {
                    set_module_pref('liked', -1);
                }
            }
            addnav('Return to whence you came', $link);
            break;
        default:
            $session['user']['specialinc'] = '';
            $gifts[SEX_FEMALE] = [
                'pair of cufflinks',
                'leather belt',
                'hat',
                'pair of boots',
            ];
            $gifts[SEX_MALE] = [
                'pair of earrings',
                'pair of satin slippers',
                'jeweled necklace',
                'pretty bracelet',
            ];
            $gifts = modulehook(
                'abigail-gifts',
                ['gifts' => $gifts]
            );
            $gifts[SEX_FEMALE] = translate_inline($gifts['gifts'][SEX_FEMALE]);
            $gifts[SEX_MALE] = translate_inline($gifts['gifts'][SEX_MALE]);
            $randomGift = e_rand(0, count($gifts[$session['user']['sex']])-1);
            $trinket = $gifts[$session['user']['sex']][$randomGift];
            set_module_pref('trinket', $trinket);
            output(
                "`7While you are wandering idly, minding your own business, you are approached by a diminutive elf in a green cloak. `n`n`0"
            );
            $greeting = translate_inline($session['user']['sex'] ? 'Madam' : 'Sir');
            output(
                "\"`&Happy day to ye, %s!",
                $greeting
            );
            output(
                "Can I interest you in a lovely %s for somebody special?",
                $trinket
            );
            output("It's a fine gift, crafted with care and skill!");
            if ($cost == 1) {
                output("And, for you, only `%%s`& gem!`7\"`n`n`0", $cost);
            }
            else {
                output("And, for you, only `%%s`& gems!`7\"`n`n`0", $cost);
            }
            output(
                "`7You survey the %s, admiring the fine craftsmanship, and try to imagine `^%s`7 wearing such a gift.",
                $trinket,
                $partner
            );
            if ($session['user']['gems'] > $cost) {
                addnav('Purchase this gift', $link . 'op=shop');
                addnav('Do not buy anything', $link . 'op=nope');
            }
            else {
                addnav('Walk away', $link . 'op=leave');
            }
            addnav('Shout at Abigail', $link . 'op=shout');
            break;
    }
}
