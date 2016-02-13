<?php

function funddrive_getmoduleinfo()
{
    $info = [
        'name' => 'Fund Drive Indicator',
        'version' => '1.0',
        'author' => 'Eric Stevens',
        'category' => 'Administrative',
        'description' =>
            'Adds a bar and message stating how much was \'donated\' to the server.',
        'download' => 'core_module',
        'settings' => [
            'message' => 'Message to be displayed above the funds:, text| Funds Goal:',
            'base' => 'How much do we have towards our goal?, viewonly| 0.00',
            'goal' => 'What is our goal amount?, int| 10.00',
        ],
    ];
    return $info;
}

function funddrive_install()
{
    module_addhook('donation_adjustments');
    module_addhook('everyfooter');
return true;
}

function funddrive_uninstall()
{
return true;
}

function funddrive_dohook($hook, $args)
{

    switch ($hook) {
        case 'donation_adjustments':
            global $session;
            if ($args['amount'] > 0) {
                increment_module_setting('base', $args['amount']);
            }
            break;
        case 'everyfooter';
            $settings = get_all_module_settings();
            if (!is_array($args['paypal'])) {
                $args['paypal'] = [];
            }
            $percent = round($settings['base'] / $settings['goal'], 2)*100;
            if ($percent > 100) {
                $percent = 100;
            }
            $fillWidth = round(1.5 * $percent, 0);
            $unfilled = 150 - $fillWidth;
            //You may want to modify the bar to fit your site's needs. - Stephen
            $bar = appoencode(
                "{$settings['message']}`n
                <div class='fund-drive' align='center'>
                    <table class='fund-drive-bar' style='border: solid 1px black; background-color: black; border-collapse: collapse; width: 150px; height: 10px;'>
                        <tr>
                            <td class='fund-drive-filled' width='{$fillWidth}px' style='background-color: #00FF00;'>
                            </td>
                            <td class='fund-drive-unfilled' width='{$unfilled}px'>
                            </td>
                        </tr>
                    </table>
                </div>",
                true
            );
            array_push($args['paypal'], $bar);
            break;
    }
    return $args;
}
?>
