<?php
/**
 * Village Creator - Index View
 * 
 * Handles listing, deleting, activating, and deactivating villages.
 */

global $session, $mysqli_resource, $language;

$defaultVillage = getsetting('villagename', LOCATION_FIELDS);
$accountsTable = db_prefix('accounts');
$villagesTable = db_prefix('villages');
$modulesTable = db_prefix('modules');

$sop = httpget('sop');
$id = (int)httpget('id');
$from = 'runmodule.php?module=villageCreator';

$lang = isset($language) ? $language : 'en';

// --- Secondary Operations (SOP) ---
if ($sop) {
    switch ($sop) {
        case 'del':
            $result = db_query(
                "SELECT name, sanitized_name 
                FROM $villagesTable 
                WHERE id = '$id'"
            );
            if ($row = db_fetch_assoc($result)) {
                $villageName = $row['name'];
                $sanitizedName = $row['sanitized_name'];

                // 1. Move players to default village
                // 2. Delete village row
                // 3. Delete YAML file
                // 4. Invalidate caches
                // 5. Delete Module Prefs

                $safeDefault = mysqli_real_escape_string(
                    $mysqli_resource,
                    $defaultVillage
                );
                $safeName = mysqli_real_escape_string(
                    $mysqli_resource,
                    $villageName
                );

                db_query(
                    "UPDATE $accountsTable
                    SET location = '$safeDefault'
                    WHERE location = '$safeName'"
                );
                db_query("DELETE FROM $villagesTable WHERE id = '$id'");

                if (db_affected_rows() > 0) {
                    output(loadTranslation('village_creator.index.delete_success'));
                    
                    // Delete YAML
                    $yamlFile = "translations/$lang/modules/village_{$sanitizedName}.yaml";
                    if (file_exists($yamlFile)) {
                        unlink($yamlFile);
                    }

                    // Cleaning
                    modulehook(
                        'village-invalidate-cache',
                        ['id' => $id, 'name' => $villageName]
                    );
                    invalidatedatacache("village_id_{$sanitizedName}");
                    modulehook(
                        'village-deleted',
                        ['id' => $id, 'name' => $villageName]
                    );
                    module_delete_objprefs('cities', $id);

                } else {
                    // Fallback: Soft Deactivate if delete fails
                    db_query("UPDATE $villagesTable SET active = 0 WHERE id = '$id'");
                    modulehook('village-invalidate-cache', ['id' => $id, 'name' => $villageName]);
                    output(
                        loadTranslation(
                            'village_creator.index.delete_fail',
                            [$villageName, db_error(LINK)]
                        )
                    );
                }
            }
            break;

        case 'activate':
            db_query("UPDATE $villagesTable SET active = 1 WHERE id = '$id'");
            $row = db_fetch_assoc(
                db_query(
                    "SELECT name, sanitized_name
                    FROM $villagesTable
                    WHERE id = '$id'"
                )
            );
            if ($row) {
                modulehook(
                    'village-invalidate-cache',
                    ['id' => $id, 'name' => $row['name']]
                );
                invalidatedatacache("village_id_{$row['sanitized_name']}");
                output(
                    loadTranslation(
                        'village_creator.index.activated',
                        [$row['name']]
                    )
                );
            }
            break;

        case 'deactivate':
            db_query("UPDATE $villagesTable SET active = 0 WHERE id = '$id'");
            $row = db_fetch_assoc(
                db_query(
                    "SELECT name, sanitized_name
                    FROM $villagesTable
                    WHERE id = '$id'"
                )
            );
            if ($row) {
                modulehook(
                    'village-invalidate-cache',
                    ['id' => $id, 'name' => $row['name']]
                );
                invalidatedatacache("village_id_{$row['sanitized_name']}");
                output(
                    loadTranslation(
                        'village_creator.index.deactivated',
                        [$row['name']]
                    )
                );
            }
            break;
    }
}

// --- Sorting ---
$order = httpget('order');
$orderDir = ($order == 1) ? 'DESC' : 'ASC';
$sortBy = httpget('sortby');
$orderByClause = 'name ' . $orderDir;

if ($sortBy == 'id') {
    $orderByClause = 'id ' . $orderDir;
}

// Prepare Sort Links
$nextOrder = (!$order);
$linkId = loadTranslation('village_creator.index.id', [$from, $nextOrder]);
$linkName = loadTranslation('village_creator.index.name', [$from, $nextOrder]);

addnav('', "{$from}&sortby=id&order={$nextOrder}");
addnav('', "{$from}&sortby=name&order={$nextOrder}");

// --- Display List ---
$result = db_query(
    "SELECT id, name, sanitized_name, active, author, travel, module
    FROM $villagesTable
    ORDER BY $orderByClause"
);

if (db_num_rows($result) > 0) {
    $rowsHtml = '';
    $i = 0;
    
    // Translation strings for reuse
    $tEdit = loadTranslation('village_creator.index.edit');
    $tConfUninstall = addslashes(
        loadTranslation(
            'village_creator.index.confirm_uninstall'
        )
    );
    $tConfDelete = addslashes(
        loadTranslation(
            'village_creator.index.confirm_delete'
        )
    );

    $tTravelTypes = [
        0 => loadTranslation('village_creator.travel_types.0'),
        1 => loadTranslation('village_creator.travel_types.1'),
        2 => loadTranslation('village_creator.travel_types.2'),
    ];

    while ($row = db_fetch_assoc($result)) {
        $rowClass = ($i % 2) ? 'trlight' : 'trdark';
        
        // Actions Column
        $editLink = "{$from}&op=edit&id={$row['id']}";
        addnav('', $editLink);

        $toggleAction = '';
        if ($row['active'] == 1) {
            // Deactivate
            $link = "{$from}&sop=deactivate&id={$row['id']}";
            $toggleAction = loadTranslation(
                'village_creator.index.deactivate',
                [$link]
            );
            addnav('', $link);
        } else {
             // Activate / Delete
            $delLink = "{$from}&sop=del&id={$row['id']}";
            $confirmMsg = ($row['module'] && $row['module'] !== '_creator') ?
                $tConfUninstall :
                $tConfDelete;
            $deleteAction = loadTranslation(
                'village_creator.index.delete',
                [$delLink, $confirmMsg]
            );
            addnav('', $delLink);

            $actLink = "{$from}&sop=activate&id={$row['id']}";
            $activateAction = loadTranslation(
                'village_creator.index.activate',
                [$actLink]
            );
            addnav('', $actLink);
             
            $toggleAction = "{$deleteAction} | {$activateAction}";
        }

        $visitLink = "runmodule.php?module=villages&op=travel&village=" .
            urlencode($row['name']) .
            "&su=1";
        addnav('', $visitLink);
        $visitAction = loadTranslation(
            'village_creator.index.visit',
            [$visitLink]
        );
        $requirementsInfo = modulehook('village-requirements', ['id' => $row['id']]);
        // Note: modulehook returns data but usually outputs directly if not gathered. 
        // Here we can't easily capture the output of the hook if it uses rawoutput/output unless we buffer.
        // However, the original code called modulehook inside rawoutput('<td>').
        // Since we are building a string, we cannot do that easily if the hook outputs directly.
        // BUT `modulehook` mostly returns arrays. Usually modules don't output directly in a hook unless it's a specific display hook.
        // The previous code did: rawoutput('<td>'); modulehook(...); rawoutput('</td>');
        // This implies the hook MIGHT output.
        // If the hook outputs, we can't put it in `sprintf`.
        // We might have to handle the table construction slightly less purely via sprintf for that column if we want to support direct output hooks.
        // OR we wrap the hook.
        // For now, let's assume standard LotGD hooks might output.
        // To handle this with the YAML template, we might need to break the template row or buffer output.
        // Buffering modulehook output:
        ob_start();
        modulehook('village-requirements', ['id' => $row['id']]);
        $reqContent = ob_get_contents();
        ob_end_clean();

        // Construct Row
        $rowsHtml .= sprintf(
            loadTranslation('village_creator.index.row'),
            $rowClass,
            $editLink,
            $toggleAction,
            $visitAction,
            $row['id'],
            $row['name'],
            $tTravelTypes[$row['travel']],
            $reqContent, // Requirements
            $row['author']
        );
        $i++;
    }

    // Render Table
    $table = sprintf(
        loadTranslation('village_creator.index.table'),
        loadTranslation('village_creator.index.ops'),
        $linkId,
        $linkName,
        loadTranslation('village_creator.index.travel'),
        loadTranslation('village_creator.index.requirements'),
        loadTranslation('village_creator.index.author'),
        $rowsHtml
    );
    rawoutput($table);

} else {
    output(loadTranslation('village_creator.index.no_cities'));
}


addnav('Help');
addnav('ReadMe', "{$from}&op=readme");

addnav('New');
addnav('Generate New Village', "{$from}&op=generate");
