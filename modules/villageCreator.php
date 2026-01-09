<?php
/**
 * Village Creator Module
 *
 * Allows administrators to create, edit, and manage custom villages.
 */

function villageCreator_getmoduleinfo(): array
{
    $info = [
        'name' => 'Village Creator',
        'description' => 'Create all the villages you want.',
        'version' => '1.0.3',
        'author' => '`@MarcTheSlayer`2, `&Stephen Kise`0',
        'category' => 'Gameplay',
        'download' => '',
        'requires' => [
            'villages' => '1.2|`2Eric Stevens`2, `@MarcTheSlayer`2, `&Stephen Kise`0',
        ],
        'prefs' => [
            'inn_at' => 'Village name of the inn player last entered:,text',
        ],
    ];

    return $info;
}

function villageCreator_install(): bool
{
    require_once 'modules/VillageCreator/install.php';
    return true;
}

function villageCreator_uninstall()
{
    global $session, $mysqli_resource;
    output('village_creator.headers.uninstall');

    $village = mysqli_real_escape_string(
        $mysqli_resource,
        getsetting('villagename', LOCATION_FIELDS)
    );
    $accountsTable = db_prefix('accounts');
    $villagesTable = db_prefix('villages');

    db_query(
        "UPDATE $accountsTable SET location = '$village' WHERE location != '$village'"
    );
    $session['user']['location'] = $village;

    $result = db_query("SELECT id, name FROM $villagesTable");
    while ($row = db_fetch_assoc($result)) {
        modulehook(
            'village-invalidate-cache',
            ['id' => $row['id'], 'name' => $row['name']]
        );
    }

    db_query("DROP TABLE IF EXISTS $villagesTable");
    return true;
}

$villageData = [];

function getVillageData(string|bool $village = false)
{
    global $villageData;
    $villagesTable = db_prefix('villages');

    if (
        isset($villageData['name']) &&
        ($villageData['name'] == $village || $villageData['id'] == $village)
    ) {
        return $villageData;
    }

    $where = false;
    if (is_numeric($village) && $village > 0) {
        $where = "id = '$village'";
    } elseif (is_string($village)) {
        $where = "name = '$village' LIMIT 1";
    }

    if ($where) {
        $sql = "SELECT * FROM $villagesTable WHERE $where";
        $sanitizedName = sanitize($village);
        $result = db_query_cached($sql, "village_id_$sanitizedName", 300);
        $villageData = db_fetch_assoc($result);
    }

    return $villageData;
}

function villageCreator_dohook(string $hookName, array $args)
{
    global $session;
    $villagesTable = db_prefix('villages');
    $accountsTable = db_prefix('accounts');

    switch ($hookName) {
        case 'header-superuser':
            if ($session['user']['superuser'] & SU_EDIT_USERS) {
                addnav('village_creator.nav_headers.creators');
                addnav(
                    '{{village_creator.navs.creator}}',
                    'runmodule.php?module=villageCreator'
                );
                addnav('village_creator.nav_headers.editors');
            }
            break;

        case 'changesetting':
            if ($args['setting'] == 'villagename') {
                db_query(
                    "UPDATE $villagesTable
                    SET name = '{$args['new']}'
                    WHERE name = '{$args['old']}'"
                );
                if ($session['user']['location'] == $args['old']) {
                    $session['user']['location'] = $args['new'];
                }
            }
            break;

        case 'village-invalidate-cache':
            invalidatedatacache("village_id_{$args['id']}");
            invalidatedatacache("village_id_{$args['name']}");
            invalidatedatacache('village_locations');
            invalidatedatacache('village_travel');
            break;

        case 'everyhit-loggedin':
            global $SCRIPT_NAME;
            $script = substr($SCRIPT_NAME, 0, strrpos($SCRIPT_NAME, '.'));
            if ($script != 'village') {
                break;
            }

            // Block/unblock modules
            $row = getVillageData($session['user']['location']);
            $row['block_mods'] = @unserialize($row['block_mods']);

            if (!is_array($row['block_mods'])) {
                break;
            }

            if (isset($row['block_mods']['all']) && $row['block_mods']['all'] == 1) {
                // Block all modules.
                blockmodule(true);
                unset($row['block_mods']['all']);
                
                // Now unblock the modules you want.
                if (isset($row['block_mods']['other']) && !empty($row['block_mods']['other'])) {
                    unblockmodule('villageCreator'); // Always unblock self
                    $others = explode(',', $row['block_mods']['other']);
                    foreach ($others as $module) {
                        unblockmodule($module);
                        debug(loadTranslation('village_creator.debug.unblock_modules', [$module]));
                    }
                }
            } else {
                unset($row['block_mods']['all']);
                if (isset($row['block_mods']['other']) && !empty($row['block_mods']['other'])) {
                    $others = explode(',', $row['block_mods']['other']);
                    foreach ($others as $module) {
                        blockmodule($module);
                        debug(loadTranslation('village_creator.debug.block_modules', [$module]));
                    }
                }
            }
            break;

        case 'header-village':
            $row = getVillageData($session['user']['location']);
            // Block navs.
            if (!isset($row['block_navs'])) {
                break;
            }
            $row['block_navs'] = is_serialized($row['block_navs']) ?
                @unserialize($row['block_navs']) :
                [];

            if (is_array($row['block_navs'])) {
                if (
                    isset($row['block_navs']['other']) &&
                    !empty($row['block_navs']['other'])
                ) {
                    // Block other navs first.
                    $otherNavs = explode(',', $row['block_navs']['other']);
                    $count = count($otherNavs);
                    if ($count > 0) {
                        for ($i = 0; $i < $count; $i++) {
                            if (isset($otherNavs[$i + 1]) && $otherNavs[$i + 1] == 'true') {
                                blocknav($otherNavs[$i], true);
                                debug(loadTranslation('village_creator.debug.block_nav_partial', [$otherNavs[$i]]));
                                $i++;
                                continue;
                            }
                            blocknav($otherNavs[$i]);
                            debug(loadTranslation('village_creator.debug.block_nav', [$otherNavs[$i]]));
                        }
                    }
                }
                unset($row['block_navs']['other']);
                
                // Now block original village navs.
                foreach ($row['block_navs'] as $nav => $value) {
                    if ($value == 1) {
                        blocknav($nav);
                        debug(loadTranslation('village_creator.debug.block_nav_core', [$nav]));
                    }
                }
            }
            break;

        case 'villagetext':
            global $TRANSLATION_CACHE, $language;
            if ($session['user']['location'] == getsetting('villagename', LOCATION_FIELDS)) {
                return $args;
            }
            
            $currentLocation = sanitize($session['user']['location']);
            $currentLocation = str_replace(' ', '', $currentLocation);
            
            if (!file_exists("translations/{$language}/modules/village_{$currentLocation}.yaml")) {
                return $args;
            }

            loadNamespace("village_{$currentLocation}");
            $newCharacter = isset($args['new_character_data']) ? $args['new_character_data'] : [];
            unset($args);
            
            $i18n = $TRANSLATION_CACHE[$language]["village_{$currentLocation}"];
            $i18n['title'] = sprintf(
                $i18n['title'],
                $session['user']['location']
            );
            $i18n['description'] = str_replace(
                '`%',
                '`%%',
                $i18n['description']
            );
            $i18n['description'] = sprintf(
                $i18n['description'],
                $session['user']['location'],
                $session['user']['location']
            );
            
            if (isset($newCharacter['name'])) {
                $i18n['new_character'] = sprintf(
                    $i18n['new_character'],
                    $newCharacter['name']
                );
            }
            
            $args = $i18n;
            break;

        case 'travel':
            $village = getVillageData($session['user']['location']);

            addnav('Safer Travel');
            addnav('More Dangerous Travel');
            addnav('Superuser Travel');

            $result = db_query_cached(
                "SELECT id, name, sanitized_name, travel
                FROM $villagesTable
                WHERE active = 1",
                'village_travel',
                86400
            );
            
            while ($row = db_fetch_assoc($result)) {
                if ($session['user']['location'] == $row['name']) {
                    continue;
                }

                $name = translate_inline($row['name']);
                $hotkey = substr(sanitize($name), 0, 1);
                
                $prereq = modulehook('village-prerequisite', [
                    'current_id' => $village['id'],
                    'current_name' => $village['name'],
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'travel' => $row['travel'],
                    'blocked' => false
                ]);

                if ($prereq['blocked'] == 0) {
                    if ($prereq['travel'] == 0) {
                        addnav('village_creator.nav_headers.travel_safe');
                        addnav(
                            ['%s?Go to %s', $hotkey, $name],
                            'runmodule.php?module=villages&op=travel&village=' . urlencode($row['name'])
                        );
                    } elseif ($prereq['travel'] == 1) {
                        addnav('village_creator.nav_headers.travel_dangerous');
                        addnav(
                            ['%s?Go to %s', $hotkey, $name],
                            'runmodule.php?module=villages&op=travel&village=' . urlencode($row['name']) . '&d=1'
                        );
                    } else {
                        debug("Travel to $name is Off.");
                    }
                }
                
                if ($session['user']['superuser'] & SU_DEVELOPER) {
                    addnav('village_creator.nav_headers.travel_superuser');
                    addnav(
                        loadTranslation('village_creator.navs.go_to', [$hotkey, $name]),
                        'runmodule.php?module=villages&op=travel&village=' . urlencode($row['name']) . '&su=1'
                    );
                }
            }
            break;

        case 'validlocation':
            $sql = "SELECT id, name, sanitized_name, travel
                    FROM $villagesTable
                    WHERE active = 1";
            $result = db_query_cached($sql, 'village_travel', 10);
            
            while ($row = db_fetch_assoc($result)) {
                if (isset($args['all']) && $args['all'] == 1) {
                    $args[$row['name']] = 'village-' . str_replace(' ', '', $row['sanitized_name']);
                } else {
                    $prereq = modulehook('valid-village-locations', [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'travel' => $row['travel'],
                        'blocked' => 0
                    ]);
                    
                    if ($prereq['blocked'] == 1) {
                        continue;
                    }
                    $args[$row['name']] = "village-{$row['sanitized_name']}";
                }
            }
            break;

        case 'validforestloc':
            $sql = "SELECT name, sanitized_name, type, block_navs
                    FROM $villagesTable
                    WHERE active = 1";
            $result = db_query_cached($sql, 'village_locations', 86400);
            
            while ($row = db_fetch_assoc($result)) {
                $row['block_navs'] = @unserialize(stripslashes($row['block_navs']));
                if (!is_array($row['block_navs'])) {
                    $row['block_navs'] = ['all' => 0, 'forest.php' => 0];
                }
                
                if (
                    (isset($row['block_navs']['all']) && $row['block_navs']['all'] != 1) ||
                    (isset($row['block_navs']['forest.php']) && $row['block_navs']['forest.php'] != 1)
                ) {
                    $args[$row['name']] = sprintf('The %s of %s', $row['type'], $row['name']);
                }
            }
            break;

        case 'moderate':
            $result = db_query(
                "SELECT name, type
                FROM $villagesTable
                WHERE active = 1
                ORDER BY name"
            );
            while ($row = db_fetch_assoc($result)) {
                $village = strtolower(str_replace(' ', '', $row['name']));
                $args["village-$village"] = sprintf('%s %s', $row['type'], $row['name']);
            }
            break;

        case 'blockcommentarea':
            $result = db_query(
                "SELECT name
                FROM $villagesTable
                WHERE chat = 1"
            );
            while ($row = db_fetch_assoc($result)) {
                $village = strtolower(str_replace(' ', '', $row['name']));
                if ("village-$village" == $args['section']) {
                    debug("Section: {$args['section']}<br />Village: {$village}<br />Chat disabled.");
                    $args['block'] = 'yes';
                }
            }
            break;

        case 'player-login':
            if ($session['user']['location'] == getsetting('innname', LOCATION_INN)) {
                $session['user']['location'] = get_module_pref(
                    'inn_at',
                    'villageCreator',
                    $session['user']['acctid']
                );
            }
            break;

        case 'innrooms':
            set_module_pref('inn_at', $session['user']['location']);
            debug('Location saved as: ' . $session['user']['location']);
            break;

        case 'showformextensions':
            $args['villagetextarea'] = 'villageCreatorTextarea';
            $args['villagelocation'] = 'villageCreatorLocation';
            break;
    }

    return $args;
}

function villageCreator_run()
{
    global $session;

    page_header('Village Creator');

    $op = httpget('op');
    $sop = httpget('sop');
    $id = httpget('id');

    $from = 'runmodule.php?module=villageCreator';

    include "modules/VillageCreator/run/case_$op.php";

    require_once 'lib/superusernav.php';
    superusernav();

    page_footer();
}

function villageCreatorTextarea($name, $val, $info)
{
    $cols = 0;
    if (isset($info[2])) {
        $cols = $info[2];
    }
    if (!$cols) {
        $cols = 70;
    }
    rawoutput('<script type="text/javascript">function increase(target, value){  if (target.rows + value > 3 && target.rows + value < 50) target.rows = target.rows + value;}</script>');
    rawoutput("<textarea id='textarea$name' class='input' name='$name' cols='$cols' rows='5'>" . htmlentities($val, ENT_COMPAT, getsetting('charset', 'ISO-8859-1')) . "</textarea>");
    rawoutput("<input type='button' onClick=\"increase(textarea$name,1);\" value='+' accesskey='+'><input type='button' onClick=\"increase(textarea$name,-1);\" value='-' accesskey='-'>");
}

function villageCreatorLocation($name, $val, $info)
{
    $villagesTable = db_prefix('villages');
    $inactive = translate_inline(' (Inactive)');
    $vloc = [];
    $vname = getsetting('villagename', LOCATION_FIELDS);
    $vloc[$vname] = 'village';
    $vloc['all'] = 1;
    $vloc = modulehook('validlocation', $vloc);
    
    $sql = "SELECT name
            FROM $villagesTable
            WHERE active = 0";
    $result = db_query($sql);
    $inactiveArray = [];
    while ($row = db_fetch_assoc($result)) {
        $vloc[$row['name']] = 'village-' . strtolower(str_replace(' ', '', $row['name']));
        $inactiveArray[$row['name']] = 1;
    }
    unset($vloc['all']);
    reset($vloc);
    
    rawoutput("<select name='$name'>");
    foreach ($vloc as $loc => $val2) {
        rawoutput(
            "<option value='$loc'" . ($loc == $val ? ' selected' : '') . ">" .
            htmlentities($loc . (isset($inactiveArray[$loc]) ? $inactive : ''), ENT_COMPAT, getsetting('charset', 'ISO-8859-1')) .
            "</option>"
        );
    }
    rawoutput('</select>');
}
