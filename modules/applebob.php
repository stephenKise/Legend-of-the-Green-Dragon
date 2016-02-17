<?php

function applebob_getmoduleinfo()
{
    $info = [
        'name' => 'Sichae\'s Apple Bobbing',
        'author' => 'Chris Vorndran & Shannon Brown',
        'version' => '1.0',
        'category' => 'Village',
        'download' => 'core_module',
        'settings' => [
            'allowed' => 'How many apples may the player eat?, int| 3',
            'cost' => 'Price to play?, int| 2',
            'location' => 'Where does the stand appear?, location| ' . getsetting('villagename', LOCATION_FIELDS),
        ],
        'prefs' => [
            'ate_today' => 'How much has the user ate today?, int| 0',
        ],
    ];
    return $info;
}

function applebob_install()
{
    module_addhook('newday');
    module_addhook('village');
    return true;
}

function applebob_uninstall()
{
    return true;
}

function applebob_dohook($hook, $args)
{
    switch($hook){
        case "newday":
            set_module_pref('ate_today', 0);
            break;
        case "village":
            global $session;
            if ($session['user']['location'] == get_module_setting('location')) {
            tlschema($args['schemas']['marketnav']);
            addnav($args['marketnav']);
            tlschema();
            addnav('A?Sichae\'s Apple Bobbing','runmodule.php?module=applebob');
            }
            break;
    }
    return $args;
}

function applebob_run()
{
    require_once('lib/villagenav.php');
    global $session;
    $op = httpget('op');
    $cost = get_module_setting('cost');
    $eatAllowed = get_module_setting('allowed');
    $eatToday = get_module_pref('ate_today');
    page_header("Sichae's Apple Stand");
    output("`&`c`bApple Bobbing Stand`b`c");
    switch ($op) {
        case 'bob':
            increment_module_pref('ate_today', 1);
            $session['user']['gold'] -= $cost;
            debuglog("spent $cost gold on an apple.");
            $appleChance = e_rand(1, 10);
            $color = e_rand(1, 4);
            $colors = [
                1 => '`4red',
                2 => '`2green',
                3 => '`^yellow',
                4 => '`@green',
            ];
            output(
                "`7You hand Sichae your %s gold, and place your hands on the edge of the barrel.",
                $cost
            );
            output("Taking a deep breath, you plunge your head forwards into the chilly water, and vainly attempt to grab hold of an apple with your teeth.");
            output(
                "You finally emerge from the water with a %s`7 coloured apple in your mouth, gasping for breath.",
                $colors[$color]
            );
            output("Sichae smiles at your success.`n`n");
            output("`&\"Well done, fair warrior!\"`n`n");
            if ($appleChance == 1) {
                output("`7She grins mischievously, `&\"'Tis a rare warrior that plucks the hallowed blue apple!");
                output("There you have my finest achievement.");
                output("Go forth and slay all in your path, enchanted one!\"`n`n");
                output("`7Your jaw slackens in astonishment at the thought of a blue apple, but you manage to catch the fruit in one hand as it falls.");
                output("As you do, its delicious flavor hits you with surprise.");
                output("Your muscles tingle and a warm buzz flows into your very bones.`n`n");
                output("You feel `5mystical!");
                apply_buff(
                    'sichae',
                    [
                        'name' => '`!Blue Apple Mystique',
                        'rounds' => 20,
                        'defmod' => 1.03,
                        'roundmsg' => '`!The Blue Apple\'s power tingles in your bones.',
                    ]
                );
                apply_buff('sichae',array("name"=>"`!Blue Apple Mystique","rounds"=>20,"defmod"=>1.03,"roundmsg"=>"`!The Blue Apple's power tingles in your bones."));
            }
            else if ($color == 4) {
                output("`7As she says this though, you realize that something is very odd about this apple.");
                output("It looks and tastes just like an ordinary green apple, but you begin to feel very strange.`n`n");
                output("Bizarre creatures appear before your eyes.");
                output("You realize that someone has poisoned the apple!`n`n");
                output("All of the imaginary monsters from your nightmares close in on you, and you feel the terrifying urge to flee this place!");
                apply_buff(
                    'sichae',
                    [
                        'name' => '`@Poisoned Appled',
                        'rounds' => 20,
                        'defmod' => 0.97,
                        'roundmsg' => '`@Strange hallucinations taunt you as you fight.',
                    ]
                );
                blocknav("runmodule.php?module=applebob&op=bob");
            }
            addnav(
                ['Try again (%s gold)', $cost],
                'runmodule.php?module=applebob&op=bob'
            );
            villagenav();
            break;
        default:
            output("`7You begin to approach the apple stand, peering into the barrels with interest.");
            output("Inside are apples of red, yellow and green.");
            output(
                "Sichae stands with her hands on her hips, and regards you with a mysterious smile.`n`n"
            );
            output(
                "Her silken garments of jade and blue swish in the cool breeze, and her lithe muscles flex as she pads over to where you stand.`n`n"
            );
            output("`&\"Ah! A visitor to the realms! So you think you can do this, do you?");
            output("It shall be amusing to see you try.\"`n`n");
            output("`7She arches her delicate neck back, and laughs a deep and beautiful sound, that immediately makes you relax.");
            output("She motions to the barrel in front of you.");
            output(
                "`&\"%s gold to show me what talent you posess.",
                $cost
            );
            output("And one of the apples is special indeed...\"");
            if ($eatToday >= $eatAllowed) {
                output("`7Much as you'd like to play, your stomach protests fitfully.");
            }
            else if ($session['user']['gold'] < $cost) {
                output("`7Unfortunately your pockets do not seem to be full enough to play!");
            }
            else {
                addnav(
                    ['Try your luck (`^%s gold`0)', $cost],
                    'runmodule.php?module=applebob&bob=bob'
                );
            }
            villagenav();
            break;
    }
    page_footer();
}
?>
