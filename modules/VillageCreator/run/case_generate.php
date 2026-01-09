<?php
/**
 * Village Creator - Generate View
 * 
 * Handles the generation of new villages and their YAML configuration files.
 */

use Symfony\Component\Yaml\Yaml;

$villagesTable = db_prefix('villages');
$op = httpget('op');
$sop = httpget('sop');
$from = 'runmodule.php?module=villageCreator';

global $language;
$lang = isset($language) ? $language : 'en';

$defaultYamlFile = "translations/$lang/village.yaml";
$defaultYaml = [];

if (file_exists($defaultYamlFile)) {
    try {
        $defaultYaml = Yaml::parseFile($defaultYamlFile);
    } catch (Exception $e) {
        // Fallback or empty if parse fails
    }
}

if ($sop == "save") {
    $name = httppost('name');
    $author = httppost('author');
    $type = httppost('type');
    $chat = httppost('chat');
    $travel = httppost('travel');
    $blockNavsRaw = httppost('block_navs');
    $blockModsRaw = httppost('block_mods');

    // Basic sanitization similar to install.php
    $sanitizedName = sanitize($name);
    $sanitizedName = str_replace(' ', '', $sanitizedName);
    $sanitizedName = preg_replace('/[^a-zA-Z0-9]/', '', $sanitizedName);

    if ($name == "" || $sanitizedName == "") {
        output(loadTranslation('village_creator.messages.gen_error_name'));
    } else {
        // Process textarea inputs into serialized arrays
        $blockNavsArray = explode(",", str_replace(["\r", "\n"], "", $blockNavsRaw));
        $blockNavsArray = array_filter(array_map('trim', $blockNavsArray));
        
        $blockNavsMap = [];
        foreach ($blockNavsArray as $nav) {
             $blockNavsMap[$nav] = true;
        }
        $blockNavs = serialize($blockNavsMap);

        $blockModsArray = explode(",", str_replace(["\r", "\n"], "", $blockModsRaw));
        $blockModsArray = array_filter(array_map('trim', $blockModsArray));
        $blockModsMap = [];
        foreach ($blockModsArray as $mod) {
             $blockModsMap[$mod] = true;
        }
        $blockMods = serialize($blockModsMap);

        $sqlName = addslashes($name);
        $sqlSanitizedName = addslashes($sanitizedName);
        $sqlType = addslashes($type);
        $sqlAuthor = addslashes($author);
        $sqlBlockNavs = addslashes($blockNavs);
        $sqlBlockMods = addslashes($blockMods);

        db_query(
            "INSERT INTO $villagesTable 
            (name, sanitized_name, type, author, active, chat,
                travel, block_navs, block_mods, module) 
            VALUES 
            ('$sqlName', '$sqlSanitizedName', '$sqlType', '$sqlAuthor', 0, '$chat',
                '$travel', '$sqlBlockNavs', '$sqlBlockMods', 'village_creator')"
        );
        
        // Construct new YAML data from form inputs
        $newYaml = $defaultYaml;
        
        // Update top-level simple keys
        $simpleKeys = ['title', 'description', 'inn_name', 'clock', 'talk', 'new_character', 'new_character_is_user'];
        foreach ($simpleKeys as $key) {
            if (httppost("yaml_$key")) {
                $newYaml[$key] = str_replace("\r", "", httppost("yaml_$key"));
            }
        }
        
        // Auto-set location fields
        $newYaml['location'] = $name;
        $newYaml['location_clean'] = "village_" . $sanitizedName;
        
        if (isset($newYaml['commentary']) && is_array($newYaml['commentary'])) {
             $newYaml['commentary']['section'] = "village-$sanitizedName";
        }
        
        $dest = "translations/$lang/modules/village_$sanitizedName.yaml";
        $destDir = dirname($dest);

        // Ensure destination directory exists
        if (!file_exists($destDir)) {
            mkdir($destDir, 0777, true);
        }

        try {
            $yamlContent = Yaml::dump($newYaml, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            if (file_put_contents($dest, $yamlContent) !== false) {
                output(loadTranslation('village_creator.messages.gen_success'));
                output(loadTranslation('village_creator.messages.gen_details', [$name, $dest]));
                output(loadTranslation('village_creator.messages.gen_hint'));
            } else {
                 output(loadTranslation('village_creator.messages.gen_save_fail'));
            }
        } catch (Exception $e) {
             output(loadTranslation('village_creator.messages.gen_yaml_fail', [$e->getMessage()]));
        }
    }
}

addnav(loadTranslation('village_creator.nav_headers.menu'));
addnav(loadTranslation('village_creator.navs.back'), $from);

$form = [
    loadTranslation('village_creator.form.generate_new') . ',title',
    'name' => loadTranslation('village_creator.form.name') . ',string,25',
    'author' => loadTranslation('village_creator.form.author') . ',string,30',
    'type' => loadTranslation('village_creator.form.type') . ',string,30',
    'chat' => loadTranslation('village_creator.form.chat') . ',bool',
    'travel' => loadTranslation('village_creator.form.travel') . ',bool',
    'block_navs' => loadTranslation('village_creator.form.block_navs') . ' (comma separated):,textarea,30',
    'block_mods' => loadTranslation('village_creator.form.block_modules') . ' (comma separated):,textarea,30',
    loadTranslation('village_creator.form.village_text_settings') . ',title',
];

// Add YAML fields to form
$yamlFields = [
    'title' => loadTranslation('village_creator.form.yaml_title') . ',string,60',
    'description' => loadTranslation('village_creator.form.yaml_desc') . ',textarea,50',
    'inn_name' => loadTranslation('village_creator.form.yaml_inn') . ',string,30',
    'clock' => loadTranslation('village_creator.form.yaml_clock') . ',string',
    'talk' => loadTranslation('village_creator.form.yaml_talk') . ',string',
    'new_character' => loadTranslation('village_creator.form.yaml_new_char_others') . ',textarea,50',
    'new_character_is_user' => loadTranslation('village_creator.form.yaml_new_char_self') . ',textarea,50',
];

foreach ($yamlFields as $key => $def) {
    $form["yaml_$key"] = $def;
}

// Pre-fill form
$row = [
    'name' => httppost('name'),
    'author' => httppost('author'),
    'type' => httppost('type') ? httppost('type') : 'Village',
    'chat' => httppost('chat'),
    'travel' => httppost('travel'),
    'block_navs' => httppost('block_navs'),
    'block_mods' => httppost('block_mods'),
];

// Pre-fill YAML fields
foreach ($yamlFields as $key => $val) {
    if ($sop == "save") {
         $row["yaml_$key"] = httppost("yaml_$key");
    } else {
         $row["yaml_$key"] = isset($defaultYaml[$key]) ? $defaultYaml[$key] : '';
    }
}

if ($sop != 'save') {
    rawoutput('<form action="' . $from . '&op=generate&sop=save" method="POST">');
    addnav('', $from . '&op=generate&sop=save');
    require_once('lib/showform.php');
    showform($form, $row);
    rawoutput('</form>');
}
?>
