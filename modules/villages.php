<?php

function villages_getmoduleinfo(): array
{
	$info = [
		'name' => 'Multiple Villages',
		'description' => 'Expands village system for multiple villages',
		'version' => '1.0',
		'author' => 'Eric Stevens,`@MarcTheSlayer`0, stephenKise',
		'category' => 'Gameplay',
		'download' => 'core_module',
		'allowanonymous' => true,
		'override_forced_nav' => true,
		'settings' => [
			'Villages Settings, title',
				'allowance' => 'Daily Travel Allowance, int|3',
				'cowardice' => 'Penalise cowardice for running away?,bool|1',
				'event_chance' => '% chance for random events,int|7',
				'safe_waylay' => '% chance waylay on safe trip,int|50',
				'danger_waylay' => '% chance waylay on dangerous trip,int|66',
        ],
		'prefs-mounts' => [
			'Villages Mount Preferences,title',
				'extra_travel' => 'How many free travels does this mount give?,int',
		],
		'prefs-drinks' => [
			'Villages Drink Preferences,title',
				'served_here' => 'Is this drink served in this village?,bool|1',
		],
		'prefs' => [
			'Villages User Preferences,title',
				'traveled' => 'How many times did they travel today?,int',
				'home_village' => 'Character\'s current home village:,text',
				'paid_cost' => 'Paid a turn/travel this trip:,viewonly',
			'Village Pref,title',
				'user_stat_home' => 'Show home village on stats?,bool|1',
				'user_stat_travel' => 'Show travel on stats?,bool|1',
        ],
	];
	return $info;
}


function villages_install()
{
	module_addhook('validatesettings');
	module_addhook('charstats');
	module_addhook('newday');
	module_addhook('village');
	module_addhook('count-travels');
	module_addhook('villages-use-travel');
	module_addhook('mountfeatures');
	module_addhook('drinks-check');
	module_addhook('stablelocs');
	module_addhook('camplocs');
	module_addhook('faq-toc');
	module_addhook('master-autochallenge');
	return true;
}

function villages_uninstall()
{
    global $mysqli_resource, $session;
	$village = mysqli_real_escape_string(
        $mysqli_resource,
        getsetting('villagename', LOCATION_FIELDS)
    );
	$inn = mysqli_real_escape_string(
        $mysqli_resource,
        getsetting('innname', LOCATION_INN)
    );
    $accountsTable = db_prefix('accounts');
    
	db_query("UPDATE $accountsTable SET location = '$village' WHERE location != '$inn'");
	$session['user']['location'] = $village;
	return true;
}

function villages_dohook($hookname,$args)
{
	global $session;

	$village = getsetting('villagename', LOCATION_FIELDS);
	$home = $session['user']['location'] == get_module_pref('home_village');
	$capital = $session['user']['location'] == $village;

	switch($hookname) {
		case 'validatesettings':
			if ($args['danger_waylay'] < $args['safe_waylay']) {
				$args['validation_error'] = "villages.validation_error";
			}
		    break;

		case 'charstats':
            villagesCharstats();
    		break;

		case 'newday':
			if ($args['resurrection'] != 'true') set_module_pref('traveled', 0);
			set_module_pref('paid_cost', 0);
		    break;

		case 'village':
			addnav($args['nav_headers']['gate']);
			addnav(
                'villages.nav_headers.travel',
                'runmodule.php?module=villages&op=travel'
            );
			if (get_module_pref('paid_cost') > 0) set_module_pref('paid_cost', 0);
    		break;

		case 'count-travels':
			global $playermount;
			$args['available'] += get_module_setting('allowance');
			if ($playermount && isset($playermount['mountid'])) {
				$mountId = $playermount['mountid'];
				$extra = get_module_objpref('mounts', $mountId, 'extra_travel');
				$args['available'] += $extra;
			}
			$args['used'] += get_module_pref('traveled');
    		break;

		case 'villages-use-travel':
			$info = modulehook('count-travels', []);
			if ($info['used'] < $info['available']) {
				set_module_pref('traveled', get_module_pref('traveled') + 1);
				if (isset($args['travel_text'])) output($args['travel_text']);
				$args['success'] = true;
				$args['type'] = 'travel';
			}
			else if($session['user']['turns'] > 0) {
				$session['user']['turns']--;
				if (isset($args['forest_text'])) output($args['forest_text']);
				$args['success'] = true;
				$args['type'] = 'forest';
			}
			else {
				if( isset($args['none_text']) ) output($args['none_text']);
				$args['success'] = false;
				$args['type'] = 'none';
			}
			$args['nocollapse'] = 1;
    		break;

		case 'mountfeatures':
			$extra = get_module_objpref('mounts', $args['id'], 'extra_travel');
			$args['features']['Travel'] = $extra;
		break;
		
		case 'drinks-check':
			if($session['user']['location'] == $village) {
				$val = get_module_objpref(
                    'drinks',
                    $args['drinkid'],
                    'served_here'
                );
				$args['allowdrink'] = $val;
			}
    		break;

		case 'stablelocs':
			$args[sanitize($village)] = $village;
    		break;

		case 'camplocs':
			$args[sanitize($village)] = $village;
    		break;

		case 'faq-toc':
			output('villages.faq.link', true);
    		break;

		case 'master-autochallenge':
            $currentLocation = $session['user']['location'];
			if (get_module_pref('home_village') != $currentLocation) {
				$info = modulehook(
                    'villages-use-travel',
				    [
                        'forest_text' => loadTranslation(
                            'villages.master_found_forest',
                            [$currentLocation]
                        ),
    					'travel_text' => loadTranslation(
                            'villages.master_found_travel',
                            [$currentLocation]
                        )
                    ]
                );
				if ($info['success']) {
					if ($info['type'] == 'travel') debuglog(
                        loadTranslation('villages.debug.lost_travel')
                    );
					if ($info['type'] == 'forest') debuglog(
                        loadTranslation('villages.debug.lost_forest')
                    );
				}
			}
		break;
	}

	return $args;
}

function villagesCharstats(): void
{
    global $session;
    if (!$session['user']['alive']) return;
    $navHeader = 'common.stat_headers.personal';
	if (get_module_pref('user_stat_home')) {
        $homeVillage = get_module_pref('home_village');
        if (!$homeVillage) $homeVillage = getsetting('villagename', LOCATION_FIELDS);
		addcharstat($navHeader);
		addcharstat('villages.stats.home_village', $homeVillage);
	}
	if(get_module_pref('user_stat_travel'))	{
		$travelArgs = modulehook('count-travels', ['available' => 0, 'used' => 0]);
		$free = max(0, $travelArgs['available'] - $travelArgs['used']);
		addcharstat($navHeader);
		addcharstat('villages.stats.free_travel', $free);
	}
    return;
}

function villagesDangerScale($danger)
{
    
	global $session;
	$scaledDanger = ( $danger ) ?
        get_module_setting('danger_waylay') :
        get_module_setting('safe_waylay');
	if ($session['user']['dragonkills'] <= 1) {
		$scaledDanger = round(.50 * $scaledDanger, 0);
	}
	else if ($session['user']['dragonkills'] <= 30) {
		$scalef = 50 / 29;
		$scale = (($session['user']['dragonkills'] - 1) * $scalef + 50) / 100;
		$scaledDanger = round($scale * $scaledDanger, 0);
	}
	return $scaledDanger;
}

function villages_run()
{
	global $session;

	$op = httpget('op');
	$village = urldecode(httpget('village'));
	$continue = httpget('continue');
	$danger = httpget('d');
	$su = httpget('su');
    $villageUri = urlencode($village);
    $continueTravelUri = "runmodule.php?module=villages&op=travel&village=$villageUri&d=$danger&continue=1";

	if ($op != 'faq') {
		require_once('lib/forcednavigation.php');
		do_forced_nav(false, false);
	}

	// I really don't like this being out here, but it has to be since
	// events can define their own op=.... and we might need to handle them
	// otherwise things break.
	require_once('lib/events.php');
	if ($session['user']['specialinc'] != '' || httpget('eventhandler')) {
		$in_event = handle_event(
            'travel',
            "$continueTravelUri&",
            'Travel'
        );
		if ($in_event) {
			addnav(
                'villages.navs.continue',
                $continueTravelUri
            );
			module_display_events('travel', $continueTravelUri);
			page_footer();
		}
	}

	if ($op == 'travel') {
		$args = modulehook(
            'count-travels',
            ['available' => 0, 'used' => 0]
        );
		$free = max(0, $args['available'] - $args['used']);
		if ($village == '') {
			require_once('lib/villagenav.php');
			page_header('villages.headers.travel');
			modulehook('collapse{', ['name'=>'traveldesc']);
			output('villages.travel_warning');
			modulehook('}collapse');
			addnav('villages.nav_headers.forget');
			villagenav();
			modulehook('pre-travel');
			if (
                !($session['user']['superuser'] & SU_EDIT_USERS) &&
                ($session['user']['turns']<=0) &&
                $free == 0
            ) {
				output('villages.cannot_travel');
			}
			else {
				addnav('villages.nav_headers.travel');
				modulehook('travel');
			}
            $villageUri = urlencode($village);
			module_display_events(
                'travel',
                "$continueTravelUri"
            );
			page_footer();
		}
		else {
			if (
                $continue != '1' &&
                $su != '1' &&
                !get_module_pref('paid_cost')
            ) {
				set_module_pref('paid_cost', 1);
				if ($free > 0) {
					// Only increment travel used if they are still within
					// their allowance.
					set_module_pref(
                        'traveled',
                        get_module_pref('traveled') + 1
                    );
					//do nothing, they're within their travel allowance.
				}
				else if ($session['user']['turns'] > 0) {
					$session['user']['turns']--;
				}
				else {
					output('villages.errors.no_turns');
					debuglog('villages.debug.no_turns');
				}
			}
			// Let's give the lower DK people a slightly better chance.
			$scaledDanger = villagesDangerScale($danger);
			if (e_rand(0,100) < $scaledDanger && $su != '1') {
				// They've been waylaid.
                $travelEvent = module_events(
                    'travel',
                    get_module_setting('event_chance'),
                    "$continueTravelUri&"
                );
				if ($travelEvent != 0) {
					page_header('villages.headers.event');
					if (checknavs()) {
						page_footer();
					}
					else {
						// Reset the special for good.
						$session['user']['specialinc'] = '';
						$session['user']['specialmisc'] = '';
						$skipvillagedesc = true;
						$op = '';
						httpset('op', '');
						addnav('Continue', $continueTravelUri);
						module_display_events('travel', $continueTravelUri);
						page_footer();
					}
				}

				$args = [
                    'soberval' => 0.9,
                    'sobermsg' => 'villages.sober_up',
                ];
				modulehook('soberup', $args);
				require_once('lib/forestoutcomes.php');
                $creaturesTable = db_prefix('creatures');
                $randomCreature = e_rand();
				$result = db_query(
                    "SELECT *
					FROM $creaturesTable
					WHERE creaturelevel = '{$session['user']['level']}'
					AND forest = 1
					ORDER BY rand($randomCreature)
					LIMIT 1"
                );
				restore_buff_fields();
				if (db_num_rows($result) == 0) {
					// There is nothing in the database to challenge you,
					// let's give you a doppleganger.
                    $character = $session['user'];
                    $dopplegangerName = "{$character['name']}`0's doppleganger";
					$badguy = [
                        'creaturename' => $dopplegangerName,
                        'creatureweapon' => $character['weapon'],
                        'creaturelevel' => $character['level'],
    					'creaturegold' => 0,
    					'creatureexp' => round($character['experience'] / 10, 0),
    					'creaturehealth' => $character['maxhitpoints'],
    					'creatureattack' => $character['attack'],
    					'creaturedefense' => $character['defense'],
                    ];
				}
				else
				{
					$badguy = db_fetch_assoc($result);
					$badguy = buffbadguy($badguy);
				}
				calculate_buff_fields();
				$badguy['playerstarthp'] = $session['user']['hitpoints'];
				$badguy['diddamage'] = 0;
				$badguy['type'] = 'travel';
				$session['user']['badguy'] = serialize($badguy);
				$battle = true;
			}
			else {
				set_module_pref('paid_cost', 0);
				// They arrive with no further scathing.
                
				$session['user']['location'] = $village;
				invalidatedatacache('list_characters_online');
				redirect('village.php');
			}
		}
	}
	else if ($op == 'fight' || $op == 'run' ) {
		if ($op == 'run' && e_rand(1,5) < 3) {
			// They managed to get away.
			page_header('villages.headers.escape');
			output('villages.flee');
			if (get_module_setting('cowardice')) {
				modulehook(
                    'villages-use-travel',
    				[
                        'forest_text' => [
                            '{{villages.cowardice_forest}}',
                            $session['user']['location']
                        ],
    					'travel_text' => [
                            '{{villages.cowardice_travel}}',
                            $session['user']['location']
                       ]
                    ]
                );
			}
			output('villages.fled_to', $session['user']['location']);

			addnav(
                loadTranslation(
                    'villages.navs.enter',
                    [$session['user']['location']]
                ),
                'village.php'
            );
			page_footer();
		}
		$battle = true;
	}
	else if ($op == 'faq') {
		villagesFaq();
	}
	else if ($op == '') {
		page_header('villages.headers.travel');
		output('villages.fight_aborted');
		addnav('villages.navs.continue_journey', $continueTravelUri);
		module_display_events('travel', $continueTravelUri);
		page_footer();
	}

	if ($battle) {
		page_header('villages.headers.waylaid');
		require_once('battle.php');
		if ($victory) {
			require_once('lib/forestoutcomes.php');
			forestvictory(
                $newenemies,
                loadTranslation('villages.waylaid_victory')
            );
			addnav(
                'villages.navs.continue_journey',
                $continueTravelUri    
            );
			module_display_events('travel', $continueTravelUri);
		}
		else if ($defeat) {
			require_once('lib/forestoutcomes.php');
			forestdefeat(
                $newenemies,
                loadTranslation('travelling to %s', [$village])
            );
		}
		else {
			require_once('lib/fightnav.php');
			fightnav(true, true, $continueTravelUri);
		}
		page_footer();
	}

}

function villagesFaq()
{
	popup_header('villages.faq.header');
    output('villages.faq.return_link');
	output(
        loadTranslation(
            'villages.faq.content',
            [
                get_module_setting('allowance'),
                getsetting('villagename', LOCATION_FIELDS)
            ]
        )
    );
	rawoutput("<hr>");
    output('villages.faq.return_link');
	popup_footer();
}