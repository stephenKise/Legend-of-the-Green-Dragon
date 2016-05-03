<?php

function changelog_getmoduleinfo()
{
    $info = [
        'name' => 'Changelog',
        'author'=> '`&`bStephen Kise`b',
        'version' => '0.1b',
        'category' => 'Administrative',
        'description' =>
            'Display all changes made on the server.',
        'download' => 'nope',
        'settings' => [
            'infonav' => 'Do you want to display this changelog in the village?, bool| 1',
            'category' => 'What category should we list all changes under?, text| Changelog',
            'format' => 'How should we format each message?, text| `%%s `@%s`0`n`n',
            'Note that you `Q`bneed`b two &quot;%s&quot;`0 for the changlog to work properly!, note',
        ],
    ];
    return $info;
}

function changelog_install()
{
    module_addhook('header-modules');
    module_addhook('village');
    module_addhook('header-about');
    module_addhook('newday-runonce');
    return true;
}

function changelog_uninstall()
{
    return true;
}

function changelog_dohook($hook, $args)
{
    switch ($hook) {
        case 'header-modules':
            $module = httppost('module') ?: httpget('module');
            $op = httpget('op');
            if ($module != '') {
                if (substr($op, -1) == 'e') {
                    $op = substr($op, 0, -1);
                }
                else if ($op == 'mass') {
                    $method = array_keys(httpallpost())[1];
                    if (substr($method, -1) == 'e') {
                        $method = substr($method, 0, -1);
                    }
                    $op = "mass $method";
                    $plural = 's';
                }
                require_once('lib/gamelog.php');
                if (is_array($module)) {
                    $lastModule = array_pop($module);
                    $module = implode(', ', $module);
                    $module .= ",`@ and `^$lastModule";
                }
                gamelog(
                    sprintf_translate(
                        '`Q%sed`@ the `^%s`@ module%s.',
                        $op,
                        $module,
                        $plural
                    ),
                    get_module_setting('category')
                );
            }
            break;
        case 'village':
            if (get_module_setting('infonav')) {
                addnav($args['infonav']);
                addnav('View Changelog', 'runmodule.php?module=changelog&ret=village');
            }
            break;
        case 'header-about':
            addnav('About LoGD');
            addnav('View Changelog', 'runmodule.php?module=changelog&ret=about');
            break;
        case 'newday-runonce':
            $gamelog = db_prefix('gamelog');
            $date = date('Y-m-d H:i:s', strtotime('now'));
            $category = get_module_setting('category');
            db_query(
                "UPDATE $gamelog SET date = '$date' WHERE category = '$category'"
            );
            break;
    }
    return $args;
}

function changelog_run() {
    $op = httpget('op');
    $ret = httpget('ret');
    $offset = httpget('offset')?:1;
    $offset = ($offset - 1) * 25;
    $offset = filter_var($offset, FILTER_SANITIZE_NUMBER_INT);
    $gamelog = db_prefix('gamelog');
    $accounts = db_prefix('accounts');
    $category = addslashes(get_module_setting('category'));
    page_header('Server Changelog');
    $sql = db_query(
        "SELECT count(logid) AS n FROM $gamelog WHERE category = '$category'"
    );
    $row = db_fetch_assoc($sql);
    addnav('Go back', "$ret.php");
    addnav('Changes');
    for ($i = 1; $i < ($row['n'] / 25 + 1); $i++) {
        addnav(
            sprintf(
                "%sPage %s (%s-%s)",
                ($offset / 25 + 1 == $i ? '`^' : ''),
                $i,
                ($i-1) * 25 + 1,
                ($i * 25 < $row['n'] ? $i * 25 : $row['n'])
            ),
            "runmodule.php?module=changelog&offset=$i&ret=$ret"
        );
    }
    $sql = db_query(
        "SELECT g.*, a.name FROM $gamelog AS g
        LEFT JOIN $accounts AS a ON g.who = a.acctid
        WHERE category = '$category' ORDER BY logid+0 DESC LIMIT $offset, 25"
    );
    output("`c`@`bChangelog`b`c`n");
    while ($row = db_fetch_assoc($sql)) {
        output(
            get_module_setting('format'),
            $row['name'],
            $row['message']
        );
    }
    page_footer();
}