<?php
use Symfony\Component\Yaml\Yaml;

$villagesTable = db_prefix('villages');
$op = httpget('op');
$sop = httpget('sop');

global $language;
$lang = isset($language) ? $language : 'en';
$default_yaml_file = "translations/$lang/village.yaml";
$default_yaml = [];
if (file_exists($default_yaml_file)) {
    try {
        $default_yaml = Yaml::parseFile($default_yaml_file);
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
    $block_navs_raw = httppost('block_navs');
    $block_mods_raw = httppost('block_mods');

    // Basic sanitization similar to install.php
    $sanitized_name = sanitize($name);
    $sanitized_name = str_replace(' ', '', $sanitized_name);
    // $sanitized_name = strtolower($sanitized_name);
    // Extra safety for filename
    $sanitized_name = preg_replace('/[^a-zA-Z0-9]/', '', $sanitized_name);

    if ($name == "" || $sanitized_name == "") {
        output("`\$Error: Name cannot be empty and must contain valid characters.`0`n");
    } else {
        // Process textarea inputs into serialized arrays
        $block_navs_array = explode("\n", str_replace("\r", "", $block_navs_raw));
        $block_navs_array = array_filter(array_map('trim', $block_navs_array));
        // Reset keys for clean array
        $block_navs_array = array_values($block_navs_array);
        $block_navs = serialize($block_navs_array);

        $block_mods_array = explode("\n", str_replace("\r", "", $block_mods_raw));
        $block_mods_array = array_filter(array_map('trim', $block_mods_array));
        $block_mods_array = array_values($block_mods_array);
        $block_mods = serialize($block_mods_array);

        $sql_name = addslashes($name);
        $sql_sanitized_name = addslashes($sanitized_name);
        $sql_type = addslashes($type);
        $sql_author = addslashes($author);
        $sql_block_navs = addslashes($block_navs);
        $sql_block_mods = addslashes($block_mods);

        $sql = "INSERT INTO $villagesTable 
                (name, sanitized_name, type, author, active, chat, travel, block_navs, block_mods, module) 
                VALUES 
                ('$sql_name', '$sql_sanitized_name', '$sql_type', '$sql_author', 0, '$chat', '$travel', '$sql_block_navs', '$sql_block_mods', 'city_creator')";
        db_query($sql);
        
        // Construct new YAML data from form inputs
        $new_yaml = $default_yaml; // Start with defaults structure
        
        // Update top-level simple keys
        $simple_keys = ['title', 'description', 'clock', 'talk', 'inn_name', 'new_character', 'new_character_is_user'];
        foreach ($simple_keys as $key) {
            if (httppost("yaml_$key")) {
                $new_yaml[$key] = str_replace("\r", "", httppost("yaml_$key"));
            }
        }
        
        // Auto-set location fields
        $new_yaml['location'] = $name;
        $new_yaml['location_clean'] = "village_" . $sanitized_name;
        
        if (isset($new_yaml['commentary']) && is_array($new_yaml['commentary'])) {
             $new_yaml['commentary']['section'] = "village-$sanitized_name";
        }
        
        $dest = "translations/$lang/modules/village_$sanitized_name.yaml";
        $dest_dir = dirname($dest);

        // Ensure destination directory exists
        if (!file_exists($dest_dir)) {
            mkdir($dest_dir, 0777, true);
        }

        try {
            $yaml_content = Yaml::dump($new_yaml, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK); // inline 4, indent 2, flags
            if (file_put_contents($dest, $yaml_content) !== false) {
                output("`@Village generated successfully!`0`n");
                output("`2Created village '%s' and config file '%s'.`0`n", $name, $dest);
                output("`^You can edit the translations for this village in the `iTranslation Editor`i (requires superuser access).`0`n");
            } else {
                 output("`\$Error: Failed to save config file.`0`n");
            }
        } catch (Exception $e) {
             output("`\$Error: Failed to generate YAML content: " . $e->getMessage() . "`0`n");
        }
    }
}

addnav('Menu');
addnav('Back to Village Creator', $from);

$form = array(
    'Generate New Village,title',
    'name'=>'Name:,string,25',
    'author'=>'Author:,string,30',
    'type'=>'Type:,string,30',
    'chat'=>'Chat Enabled?,bool',
    'travel'=>'Travel Enabled?,bool',
    'block_navs'=>'Block Navs (one per line):,textarea,30',
    'block_mods'=>'Block Mods (one per line):,textarea,30',
    'Village Text Settings,title',
);

// Add YAML fields to form
$yaml_fields = [
    'title' => 'Title:,string,60',
    'description' => 'Description:,textarea,50',
    'inn_name' => 'Inn Name:,string,30',
    'clock' => 'Clock Text:,string',
    'talk' => 'Talk Header:,string',
    'new_character' => 'New Char (Others):,textarea,50',
    'new_character_is_user' => 'New Char (Self):,textarea,50',
];

foreach ($yaml_fields as $key => $def) {
    $form["yaml_$key"] = $def;
}

// Pre-fill form
$row = array(
    'name' => httppost('name'),
    'author' => httppost('author'),
    'type' => httppost('type') ? httppost('type') : 'Village',
    'chat' => httppost('chat'),
    'travel' => httppost('travel'),
    'block_navs' => httppost('block_navs'),
    'block_mods' => httppost('block_mods'),
);

// Pre-fill YAML fields
foreach ($yaml_fields as $key => $val) {
    if ($sop == "save") {
         $row["yaml_$key"] = httppost("yaml_$key");
    } else {
         $row["yaml_$key"] = isset($default_yaml[$key]) ? $default_yaml[$key] : '';
    }
}

if ($sop != 'save') {
    rawoutput('<form action="'.$from.'&op=generate&sop=save" method="POST">');
    addnav('',$from.'&op=generate&sop=save');
    require_once('lib/showform.php');
    showform($form, $row);
    rawoutput('</form>');
}
