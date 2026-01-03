<?php
require_once "common.php";
require_once "lib/translations.php";

check_su_access(SU_IS_TRANSLATOR);

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

$op = httpget('op') ?: '';
$file = httppost('file') ?: httpget('file');
$message = '';
$language = $session['user']['language'] ?? getsetting('defaultlanguage', 'en');

$errors = []; // For validation errors

if ($op == "save" && $file != '') {
    $content = httppost('content');
    $path = "translations/$language/$file";

    // Security check
    $real_translations = realpath("translations");
    $real_path = realpath(dirname($path));

    if (strpos($real_path . DIRECTORY_SEPARATOR, $real_translations . DIRECTORY_SEPARATOR) !== 0 ||
        substr($file, -5) !== '.yaml') {
        $message = "`\$Invalid file path!`0";
    } else {
        // === VALIDATE YAML BEFORE SAVING ===
        try {
            Yaml::parse($content);
            // If we get here, YAML is valid → safe to save
            if (file_put_contents("translations/$language/$file", $content) !== false) {
                invalidateTranslationCache(); // Clear cache
                $message = "`^Translation saved successfully!`0";
                $errors = [];
            } else {
                $message = "`\$Failed to save file. Check folder permissions.`0";
            }
        } catch (ParseException $e) {
            $errors[] = "YAML Parse Error: " . $e->getMessage();
            $message = "`\$YAML syntax error — file NOT saved.`0";

            // Optional: highlight problematic line
            if ($e->getParsedLine() !== null) {
                $errors[] = "Error near line " . $e->getParsedLine() . " (check indentation, colons, or block scalars)";
            }
        }
    }
}

page_header("Translation Editor");

if ($message) {
    output("$message");
    output_notl("<div style='padding:12px; background:#333; color:#fff; margin:10px 0; border-left:5px solid #c00;'>$message</div>", true);
}

if (!empty($errors)) {
    output_notl("<div style='padding:10px; background:#fee; border:1px solid #c66; color:#800; margin:10px 0;'>", true);
    output_notl("<strong>Fix these issues:</strong><ul style='margin:8px 0;'>", true);
    foreach ($errors as $err) {
        output_notl("<li>" . htmlspecialchars($err) . "</li>", true);
    }
    output_notl("</ul></div>", true);
}

output("Select a translation file to edit:`n`n");

// Build file list
$files = [];
$languages = array_filter(scandir("translations"), fn($d) => $d !== '.' && $d !== '..' && is_dir("translations/$d"));

foreach ($languages as $lang) {
    $dir = "translations/$lang";
    if (!is_dir($dir)) continue;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile() && $fileInfo->getExtension() === 'yaml') {
            // Get relative path from translations/$lang/
            // getPathname returns e.g. translations/en/modules/foo.yaml
            // We want modules/foo.yaml if inside subdir, or foo.yaml if at root
            $fullPath = $fileInfo->getPathname();
            // normalized path in case of windows/unix separator diffs, though usually fine
            $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $fullPath);
            // Fallback if generic replace failed (e.g. slight mismatch in separators)
             if ($relativePath === $fullPath) {
                $relativePath = substr($fullPath, strlen($dir) + 1);
            }
            
            $files[$relativePath] = "$lang → " . str_replace('.yaml', '', $relativePath);
        }
    }
}

ksort($files);

output_notl("<form method='post' action='translations.php?op=edit'>", true);
addnav('', "translations.php?op=edit");
output_notl("<select name='file' onchange='this.form.submit()' style='width:400px;font-size:1.1em;'>", true);
output_notl("<option value=''>-- Choose a file --</option>", true);
foreach ($files as $name => $label) {
    $selected = ($file === $name) ? "selected" : "";
    output_notl("<option value='$name' $selected>$label</option>", true);
}
output_notl("</select>", true);
output_notl("</form><br/>", true);

output_notl("<hr><h3>Create new file</h3>", true);
output_notl("<form method='post'>", true);
output_notl("Language: <select name='newlang'>", true);
foreach ($languages as $l) {
    $sel = ($l === $language) ? "selected" : "";
    output_notl("<option value='$l' $sel>$l</option>", true);
}
output_notl("</select> ", true);
output_notl("Filename: <input name='newname' placeholder='newmodule' required>.yaml ", true);
output_notl("<button type='submit' name='op' value='create'>Create</button>", true);
output_notl("</form>", true);

if ($op == "create") {
    $lang = httppost('newlang');
    $name = preg_replace('/[^a-z0-9_-]/', '', strtolower(httppost('newname')));
    if ($name) {
        $path = "translations/$lang/$name.yaml";
        if (!file_exists($path)) {
            file_put_contents($path, "# New translation file for $lang\n\n");
            redirect("translations.php?op=edit&file=$name");
        } else {
            $message = "`\$File already exists!`0";
        }
    }
}

// Edit form
if (($op == 'edit' || $op == 'save') && $file != '' && file_exists("translations/$language/$file")) {
    // If we had a save error, use the submitted content; otherwise load from file
    $content = ($op == 'save' && !empty(httppost('content'))) ? httppost('content') : file_get_contents("translations/$language/$file");

    output_notl("<form method='post' action='translations.php?op=save'>", true);
    addnav('', 'translations.php?op=save');
    output_notl("<input type='hidden' name='file' value='$file'>", true);

    output_notl("<textarea name='content' style='width:100%; height:75vh; font-family:Consolas,monospace; font-size:1em, true; padding:10px;'>", true);
    rawoutput($content);
    output_notl("</textarea><br/><br/>", true);

    output_notl("<button type='submit' style='font-size:1.2em; padding:12px 24px; background:#060; color:white; border:none;'>", true);
    output_notl("Save Changes", true);
    output_notl("</button>", true);

    output_notl(" &nbsp; <a href='translations.php'>← Back to list</a>", true);
    output_notl("</form>", true);
}

addnav("G?Return to the Grotto", "superuser.php");
page_footer();