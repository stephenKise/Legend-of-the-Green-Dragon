<?php

/*
Staff List
File: stafflist.php
Author:  Red Yates aka Deimos
Date:    9/8/2004
Version: 1.2 (10/3/2004)

Just a means of listing staff members. In order to put people on the list,
you have to edit their prefs for this module, setting their rank to
something more than 0. The list will be sorted by ranks, so rank them in
groups, like Moderators = 1, SrMods = 2, JrAdmin = 3, Admin = 4, SrAdmin = 5,
Owner = 6, or something like that. Or, to force an order, you could give
everyone a number, but that is kind of silly.  Anyone less than rank 1 won't
be listed. You might want to set the pref ranks before activating the module.
Also included is a space for a blurb at the bottom.

v1.1
Query optimization and such, with help from Kendaer and MightyE
v1.2
Added feature to show if staff is online (suggested by Anyanka of Central)
*/

require_once('lib/villagenav.php');

function stafflist_getmoduleinfo(): array
{
	$info = [
		'name' => 'Staff List',
		'version' => '1.2',
		'author' => '`$Red Yates, little addition by Haku',
		'allowanonymous' => true,
		'category' => 'Administrative',
		'download' => 'core_module',
		'settings' => [
			'Staff List Settings, title',
			'bio_link' => 'Link staff names to their bios, bool| 1',
			'show_online' => 'Show if staff member is online, bool| 1',
			'You can edit the text below the staff list in the Translation Editor, note',
		],
		'prefs' => [
			'Staff List User Preferences, title',
			'rank' => 'Arbitrary ranking number (higher means higher on list),int|0',
			'desc' => 'Description to be put in the staff list|I work here?',
		],
	];
	return $info;
}

function stafflist_install(): bool
{
	module_addhook('village');
	module_addhook('about');
	module_addhook('player-login');
	module_addhook('player-logout');
    module_addhook('validateprefs');
	return true;
}

function stafflist_uninstall(): bool
{
	return true;
}

function stafflist_dohook(string $hookname, array $args): array
{
	global $session;
	switch ($hookname) {
        case 'validateprefs':
            if (httpget('module') == 'stafflist') {
                invalidatedatacache('staff_list');
            }
            break;
    	case 'player-login':
    	case 'player-logout':
    		// Invalidate the staff list when someone on staff logs in or out
    		if (get_module_pref('rank') > 0) {
    			invalidatedatacache('staff_list');
    		}
    		break;
    	case 'village':
    		addnav($args['nav_headers']['info']);
    		addnav(
                'staff_list.navs.staff_list', 
                'runmodule.php?module=stafflist&from=village'
            );
    		break;
    	case 'about':
    		addnav(
                'staff_list.navs.staff_list', 
                'runmodule.php?module=stafflist&from=about'
            );
    		break;
	}
	return $args;
}

function stafflist_run(){
	page_header('staff_list.title');
    $accountsTable = db_prefix('accounts');
    $prefsTable = db_prefix('module_userprefs');
	
	$from = httpget('from');
	if ($from == 'about') {
		addnav(
            loadTranslation('common.return_to', ['whence you came']), 
            'about.php'
        );
	} else if ($from == 'village') {
		villagenav();
	}
		
    $sql = "SELECT p1.userid, (p1.value + 0) AS staff_rank, p2.value AS description,
        u.name, u.login, u.sex, u.laston, u.loggedin 
        FROM $accountsTable as u, $prefsTable as p1, $prefsTable as p2
        WHERE (p1.value + 0) > 0
        AND p1.modulename = 'stafflist'
        AND p1.setting = 'rank'
        AND p1.userid = u.acctid
        AND p2.modulename = 'stafflist'
        AND p2.setting = 'desc'
        AND p2.userid = u.acctid
        ORDER BY staff_rank DESC, u.acctid ASC";
	$result = db_query_cached($sql, 'staff_list', 600);
	$count = db_num_rows($result);
	
	output('staff_list.header');
    if ($count == 0) {
        output('staff_list.no_staff');
        page_footer();
        exit;
    }
    
    $staffMembers = [];
	for ($i = 0; $i < $count; $i++) {
		$row = db_fetch_assoc($result);
        $staffMemberName = $row['name'];
		$loggedIn = (
            date('U') - strtotime($row['laston']) < getsetting('LOGINTIMEOUT', 900) &&
            $row['loggedin']
        );
        $rowClass = $i % 2 ?
            loadTranslation('staff_list.row_dark') :
            loadTranslation('staff_list.row_light');
        if (get_module_setting('bio_link')) {
            addnav(
                '',
                loadTranslation(
                    'staff_list.bio_uri',
                    [
                        rawurlencode($staffMemberName),
                        urlencode($_SERVER['REQUEST_URI'])
                    ]
                )
            );
            $row['name'] = loadTranslation(
                'staff_list.bio_link',
                [
                    rawurlencode($staffMemberName),
                    urlencode($_SERVER['REQUEST_URI']),
                    $row['name']
                ]
            );
        }
        if ($loggedIn && get_module_setting('show_online') == 1) {
            $row['name'] .= loadTranslation('staff_list.online');
        }
        array_push(
            $staffMembers,
            loadTranslation(
                'staff_list.staff_row',
                [$rowClass, $row['name'], $row['description']]
            )
        );
	}
    output('staff_list.staff_table', join($staffMembers));

    output('staff_list.blurb');
	page_footer();
}
