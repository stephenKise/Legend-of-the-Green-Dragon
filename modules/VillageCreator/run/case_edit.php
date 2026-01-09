<?php
/**
 * Village Creator - Edit View
 * 
 * Handles the display of the village editing form.
 */
require_once 'modules/VillageCreator/functions.php';
require_once 'lib/showform.php';

global $session;

$villageId = (int)httpget('id');
$modulesTable = db_prefix('modules');
$moduleObjPrefsTable = db_prefix('module_objprefs');
$villagesTable = db_prefix('villages');
$row = [];
$addNew = 'invisible';

if ($villageId > 0) {
    //
    // Get village data and send it for checking.
    //
    $sql = "SELECT * FROM $villagesTable WHERE id = '$villageId'";
    $result = db_query($sql);
    $row = db_fetch_assoc($result);

    if (db_num_rows($result) !== 1) {
        output('`n`$Error: That village was not found!`0`n`n');
        $row = validateVillageData();
        $addNew = 'invisible';
    } else {
        // Deserialize fields
        $row['mods'] = is_serialized($row['block_mods']) ?
            @unserialize($row['block_mods']) :
            '';
        $row['navs'] = is_serialized($row['block_navs']) ?
            @unserialize($row['block_navs']) :
            '';
        
        // Helper to get array details and unset removed fields
        $navsArray = is_serialized($row['block_navs']) ?
            @unserialize($row['block_navs']) :
            [];
        
        // Unset deprecated fields to clean up row before form display
        unset(
            $row['block_navs'],
            $row['block_mods'],
            $row['text'],
            $row['stable_text'],
            $row['armor_text'],
            $row['weapon_text'],
            $row['mercenary_camp_text']
        );
        
        $row = validateVillageData($row);
        
        // Prepare block_navs for CSV textarea
        if (is_array($navsArray)) {
            $keys = [];
            // Check if associative (new format: ['file.php'=>true]) or indexed (old format: ['file.php'])
            $isAssoc = (array_keys($navsArray) !== range(0, count($navsArray) - 1)) &&
                !empty($navsArray);
            
            if ($isAssoc) {
                // Filter for true values if needed, but assuming presence means blocked
                $keys = array_keys($navsArray);
            } else {
                $keys = $navsArray;
            }
            $row['block_navs'] = implode(', ', $keys);
        } else {
            $row['block_navs'] = '';
        }
        $addNew = 'bool';
    }
} else {
    $row = validateVillageData();
    $addNew = 'invisible';
}

$sanitizedName = isset($row['sanitized_name']) ? $row['sanitized_name'] : '';
$noteTextEditor = loadTranslation(
    'village_creator.form.note_text_editor',
    [$sanitizedName]
);

// Construct Form
$form = [
    loadTranslation('village_creator.form.village_details') . ',title',
    'addnew' => loadTranslation('village_creator.form.add_new') . ',' . $addNew,
    'active' => loadTranslation('village_creator.form.active') . ',bool',
    'id' => loadTranslation('village_creator.form.id') . ',hidden',
    'module' => loadTranslation('village_creator.form.module') . ',viewonly',
    'author' => loadTranslation('village_creator.form.author') . ',string,30',
    'name' => loadTranslation('village_creator.form.name') . ',string,30',
    loadTranslation('village_creator.form.note_name') . ',note',
    'type' => loadTranslation('village_creator.form.type') . ',string,30',
    loadTranslation('village_creator.form.note_type') . ',note',
    'chat' => loadTranslation('village_creator.form.chat') . ',bool',
    'travel' => loadTranslation('village_creator.form.travel') . ',enum,0,Safe,1,Dangerous,2,Off',
    loadTranslation('village_creator.form.note_travel') . ',note',
    
    loadTranslation('village_creator.form.village_text') . ',title',
    $noteTextEditor . ',note',
    
    loadTranslation('village_creator.form.block_modules') . ',title',
    'modsall' => loadTranslation('village_creator.form.mods_all') . ',bool',
    'modsother' => loadTranslation('village_creator.form.mods_other') . ',textarearesizeable,30',
    loadTranslation('village_creator.form.note_mods') . ',note',
    
    loadTranslation('village_creator.form.block_navs') . ',title',
    loadTranslation('village_creator.form.navs_note') . ',note',
    'block_navs' => loadTranslation('village_creator.form.navs_label') . ',textarea,30',
];

//
// Get Module Preferences for Cities
//
$sql = "SELECT formalname, modulename
        FROM $modulesTable
        WHERE infokeys
        LIKE '%|prefs-city|%'
        ORDER BY formalname";
$result = db_query($sql);

while ($rowMod = db_fetch_assoc($result)) {
    $formalName = $rowMod['formalname'];
    $moduleName = modulename_sanitize($rowMod['modulename']);
    $info = get_module_info($moduleName);

    if (count($info['prefs-city']) > 0) {
        $form[] = $formalName . ',title'; // Module Title
        
        foreach ($info['prefs-city'] as $key => $val) {
            // Convert titles inside prefs to notes
            if (strpos($val, ',title') !== false) {
                $val = '`^`i' . str_replace(',title', '`i,note', $val);
            }
            
            $settingParts = is_array($val) ? explode('|', $val[0]) : explode('|', $val);
            $schema = $settingParts[0];
            $default = isset($settingParts[1]) ? $settingParts[1] : '';

            $form[$moduleName . '-' . $key] = $schema;
            $row[$moduleName . '-' . $key] = $default;
        }

        // Load existing values
        $sqlPrefs = "SELECT setting, value
                     FROM $moduleObjPrefsTable
                     WHERE modulename = '$moduleName'
                       AND objtype = 'city'
                       AND objid = '$villageId'";
        $resultPrefs = db_query($sqlPrefs);
        while ($rowPrefs = db_fetch_assoc($resultPrefs)) {
            $row["{$moduleName}_{$rowPrefs['setting']}"] = stripslashes(
                $rowPrefs['value']
            );
        }
    }
}

// Display Form
$from = 'runmodule.php?module=villageCreator';
rawoutput('<form action="' . $from . '&op=insert" method="POST">');
addnav('', $from . '&op=insert');

showform($form, $row);

// Old values logic
rawoutput('<input type="hidden" name="oldvalues" value="' . base64_encode(serialize($row)) . '" /></form>');

addnav(loadTranslation('village_creator.nav_headers.menu'));
addnav(loadTranslation('village_creator.navs.back'), $from);
?>
