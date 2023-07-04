<?php

function savesetting(string $settingname, $value)
{
    global $settings, $mysqli_resource;
    if (!file_exists('dbconnect.php')
        || $mysqli_resource === null
        || defined('IS_INSTALLER'))
        return false;
    $table = db_prefix('settings');
    loadsettings();
    if (!isset($settings[$settingname])) {
            $sql = db_query(
                "INSERT INTO $table (setting, value)
                VALUES ('" . addslashes($settingname) . "', '" . addslashes($value) . "')"
            );
    }
    else if (isset($settings[$settingname])) {
            $sql = db_query(
                "UPDATE $table SET value = '" . addslashes($value) . "' WHERE setting = '" . addslashes($settingname) . "'"
            );
    }
    else {
        return false;
    }
    $settings[$settingname] = $value;
    invalidatedatacache('game-settings');
    if (db_affected_rows()>0) {
        return true;
    }
    else {
        return false;
    }
}

function loadsettings()
{
    global $settings, $mysqli_resource;
    if (!file_exists('dbconnect.php')
        || $mysqli_resource === null
        || defined('IS_INSTALLER')) 
        return;
    if (!is_array($settings)) {
        $settings = datacache('game-settings');
        if (!is_array($settings)) {
            $settings = [];
            $sql = db_query(
                "SELECT * FROM " . db_prefix('settings')
            );
            while ($row = db_fetch_assoc($sql)) {
                $settings[$row['setting']] = $row['value'];
            }
            db_free_result($sql);
            updatedatacache('game-settings', $settings);
        }
    }
}

function clearsettings()
{
    global $settings;
    unset($settings);
}

function getsetting(string $settingname, $default)
{
    if (!file_exists('dbconnect.php')) return $default;
    global $settings, $DB_USEDATACACHE, $DB_DATACACHEPATH;
    if ($settingname == 'usedatacache') {
        return $DB_USEDATACACHE;
    }
    else if ($settingname == 'datacachepath') {
        return $DB_DATACACHEPATH;
    }
    if (!isset($settings[$settingname])) {
        loadsettings();
    }
    else {
        return $settings[$settingname];
    }
    if (!isset($settings[$settingname])) {
        savesetting($settingname, $default);
        return $default;
    }
    else {
        return $settings[$settingname];
    }
}
