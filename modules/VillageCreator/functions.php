<?php
/**
 * Village Creator Functions
 * 
 * Core helper functions for the Village Creator module.
 */

/**
 * Validates and prepares village data for the form.
 * 
 * Ensures all necessary fields exist and flattens specific arrays
 * (like block_mods) into the format expected by showform().
 *
 * @param array|false $village The village data array or false.
 * @return array The processed village data.
 */
function validateVillageData($village = false)
{
    // Default structure
    if (!is_array($village)) {
        $village = [];
    }

    $defaults = [
        'id' => 0,
        'name' => '',
        'type' => '',
        'author' => '',
        'active' => 0,
        'chat' => 0,
        'travel' => 0,
        'module' => '',
        'block_mods' => '',
        'block_navs' => '',
    ];

    // Merge defaults
    $village = array_merge($defaults, $village);

    // Handle Block Mods
    // We expect 'mods' key which might be deserialized from 'block_mods' in the controller
    // or we construct it here.
    $mods = [
        'all' => 0,
        'other' => '',
    ];

    if (isset($village['mods']) && is_array($village['mods'])) {
        $mods = array_merge($mods, $village['mods']);
    }

    // Flatten 'mods' for showform (modsall, modsother)
    // showform expects keys like 'modsall' and 'modsother'
    $village['modsall'] = (int)$mods['all'];
    $village['modsother'] = stripslashes($mods['other']);

    // Remove complex arrays that confuse showform or are handled separately
    unset($village['mods']);

    // Legacy fields cleanup (just in case)
    $legacyFields = ['navs', 'vill', 'stab', 'arm', 'weap', 'merc'];
    foreach ($legacyFields as $field) {
        if (isset($village[$field])) {
            unset($village[$field]);
        }
    }

    return $village;
}
