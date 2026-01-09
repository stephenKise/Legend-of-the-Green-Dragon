<?php
/**
 * Village Creator - Insert/Update Logic
 * 
 * Handles the database insertion and updates for villages.
 */

use Symfony\Component\Yaml\Yaml;

require_once 'lib/sanitize.php';
require_once 'lib/gamelog.php';

global $mysqli_resource, $session;

$accountsTable = db_prefix('accounts');
$modulesTable = db_prefix('modules');
$villagesTable = db_prefix('villages');
$villageId = (int)httppost('id');
$posts = httpallpost();
$op = ($villageId > 0) ? 'update' : 'insert';
$from = 'runmodule.php?module=villageCreator';

// Basic sanitization and preparation
$name = strip_tags(httppost('name'));
if (empty($name)) {
    $name = getsetting('villagename', LOCATION_FIELDS);
}

$sanitizedName = preg_replace('/[^a-zA-Z0-9]/', '', str_replace(' ', '', sanitize($name)));
$author = httppost('author') ? strip_tags(httppost('author')) : $session['user']['login'];
$type = full_sanitize(str_replace(['"', "'"], '', strip_tags(httppost('type'))));
$module = httppost('module') ?: 'village_creator';
$active = (int)httppost('active');
$chat = (int)httppost('chat');
$travel = (int)httppost('travel');
$addNew = (int)httppost('addnew');
$rawNavs = httppost('block_navs');

// Prepare core fields for DB
$coreFields = [
    'name' => $name,
    'sanitized_name' => $sanitizedName,
    'author' => $author,
    'type' => $type,
    'module' => $module,
    'active' => $active,
    'chat' => $chat,
    'travel' => $travel,
];

// Process Block Data
// Block Mods
$blockMods = [];
if (isset($posts['modsall']) && $posts['modsall']) {
    $blockMods['all'] = 1;
}
if (!empty($posts['modsother'])) {
    $blockMods['other'] = $posts['modsother'];
}
$serializedMods = !empty($blockMods) ? serialize($blockMods) : '';

// Block Navs (CSV to Associative Array)
$processedNavs = [];
$navsInput = explode(',', str_replace(["\r", "\n"], "", $rawNavs));
foreach ($navsInput as $navStub) {
    $navStub = trim($navStub);
    if ($navStub === '') {
        continue;
    }
    $processedNavs[$navStub] = true;
}

$serializedNavs = !empty($processedNavs) ? serialize($processedNavs) : '';

// --- Database Operation ---

if ($op === 'update') {
    $oldValues = @unserialize(base64_decode(httppost('oldvalues')));

    // 1. Handle Renaming (YAML & Accounts)
    if ($coreFields['name'] !== $oldValues['name']) {
        // Update user locations currently in this village to the new name
        $safeOldName = mysqli_real_escape_string($mysqli_resource, $oldValues['name']);
        $safeNewName = mysqli_real_escape_string($mysqli_resource, $coreFields['name']);
        
        db_query("UPDATE $accountsTable SET location = '$safeNewName' WHERE location = '$safeOldName'");

        // YAML Rename Logic
        global $language;
        $lang = isset($language) ? $language : 'en';
        
        // Helper to replicate sanitization logic EXACTLY
        $sanitizeHelper = function ($n) {
            $s = sanitize((string)$n);
            $s = str_replace(' ', '', $s);
            return preg_replace('/[^a-zA-Z0-9]/', '', $s);
        };

        $oldSanitized = $sanitizeHelper($oldValues['name']);
        $newSanitized = $sanitizeHelper($coreFields['name']);

        $oldFile = "translations/$lang/modules/village_{$oldSanitized}.yaml";
        $newFile = "translations/$lang/modules/village_{$newSanitized}.yaml";

        if (file_exists($oldFile)) {
            try {
                rename($oldFile, $newFile);
                if (file_exists($newFile)) {
                    $yamlData = Yaml::parseFile($newFile);
                    $yamlData['location'] = $coreFields['name'];
                    $yamlData['location_clean'] = "village_{$newSanitized}";
                    
                    if (isset($yamlData['commentary']) && is_array($yamlData['commentary'])) {
                        $yamlData['commentary']['section'] = "village-{$newSanitized}";
                    }
                    
                    file_put_contents($newFile, Yaml::dump($yamlData, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
                    output("`^Configuration file renamed from '`@%s`^' to '`@%s`^'.`n", basename($oldFile), basename($newFile));
                }
            } catch (Exception $e) {
                output("`\$Error renaming configuration file: %s`0`n", $e->getMessage());
            }
        }
    }

    // 2. Perform Update
    $updateClauses = [];
    foreach ($coreFields as $field => $val) {
        $updateClauses[] = "`$field` = '" . mysqli_real_escape_string($mysqli_resource, $val) . "'";
    }
    $updateClauses[] = "block_mods = '" . mysqli_real_escape_string($mysqli_resource, $serializedMods) . "'";
    $updateClauses[] = "block_navs = '" . mysqli_real_escape_string($mysqli_resource, $serializedNavs) . "'";

    $sql = "UPDATE $villagesTable SET " . implode(', ', $updateClauses) . " WHERE id = '$villageId'";
    db_query($sql);

    if (db_affected_rows() > 0) {
        output(loadTranslation('village_creator.messages.update_success'));
    } else {
        output(loadTranslation('village_creator.messages.update_no_change'));
    }
} else {
    // Insert New
    $cols = array_keys($coreFields);
    $vals = array_map(function ($v) use ($mysqli_resource) {
        return mysqli_real_escape_string($mysqli_resource, $v);
    }, array_values($coreFields));

    // Add block fields
    $cols[] = 'block_mods';
    $vals[] = mysqli_real_escape_string($mysqli_resource, $serializedMods);
    $cols[] = 'block_navs';
    $vals[] = mysqli_real_escape_string($mysqli_resource, $serializedNavs);

    $sql = "INSERT INTO $villagesTable (" . implode(',', $cols) . ") VALUES ('" . implode("','", $vals) . "')";
    db_query($sql);
    $villageId = db_insert_id();

    if ($villageId) {
        output(loadTranslation('village_creator.messages.create_success'));
    } else {
        output(loadTranslation('village_creator.messages.create_fail'));
    }
}

// --- Module Preferences Handling ---
// Only update preferences if they changed (UPDATE mode) or set them (INSERT mode)
if ($op === 'insert') {
    $oldValues = []; // No old values for insert
}

$sqlModules = "SELECT formalname, modulename FROM $modulesTable WHERE infokeys LIKE '%|prefs-city|%' ORDER BY formalname";
$resultModules = db_query($sqlModules);

while ($rowMod = db_fetch_assoc($resultModules)) {
    $modName = $rowMod['modulename'];
    $len = strlen($modName);

    foreach ($posts as $key => $val) {
        if (substr($key, 0, $len) == $modName) {
            $settingName = substr($key, $len + 1); // e.g. 'cities-newest' -> 'newest'
            
            // Check if changed
            $oldVal = isset($oldValues[$key]) ? $oldValues[$key] : null;
            if ($val != $oldVal) {
                set_module_objpref('', $villageId, $settingName, $val, $modName);
                if ($op === 'update') {
                    output(loadTranslation('village_creator.messages.module_change', [$modName, $settingName]));
                }
            }
        }
    }
}

// Invalidate Cache
invalidatedatacache("village_id_{$sanitizedName}");
modulehook('invalidatecache', ['id' => $villageId, 'name' => $name]);

addnav(loadTranslation('village_creator.messages.menu'));
addnav(loadTranslation('village_creator.navs.add_village'), $from . '&op=edit');
addnav(loadTranslation('village_creator.messages.back'), $from);
