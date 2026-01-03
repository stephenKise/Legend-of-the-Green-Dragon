<?php
/**
	Modified by MarcTheSlayer
	16/02/2012 - v1.1.0
	+ This module has been modified to work with my 'city_creator' module.
	18/04/2013 - v1.2
	+ Didn't notice that in module manager 1.1.0 was being seen as the same as 1.1 Oops. :)
*/
function cities_getmoduleinfo()
{
	$info = array(
		"name"=>"Multiple Cities",
		"description"=>"Allows you to have more than one city in your game.",
		"version"=>"1.2",
		"author"=>"Eric Stevens`2, modified by `@MarcTheSlayer",
		"category"=>"Cities",
		"download"=>"",
		"allowanonymous"=>TRUE,
		"override_forced_nav"=>TRUE,
		"settings"=>array(
			"Cities Settings,title",
				"allowance"=>"Daily Travel Allowance,int|3",
				"coward"=>"Penalise Cowardice for running away?,bool|1",
				"travelspecialchance"=>"Chance for a special during travel,range,1,100,1|7",
				"safechance"=>"Chance to be waylaid on a safe trip,range,1,100,1|50",
				"dangerchance"=>"Chance to be waylaid on a dangerous trip,range,1,100,1|66",
		),
		"prefs-mounts"=>array(
			"Cities Mount Preferences,title",
				"extratravel"=>"How many free travels does this mount give?,int",
		),
		"prefs-drinks"=>array(
			"Cities Drink Preferences,title",
				"servedhere"=>"Is this drink served in this City?,bool|1",
		),
		"prefs"=>array(
			"Cities User Preferences,title",
				"traveltoday"=>"How many times did they travel today?,int",
				"homecity"=>"User's current home city:,text",
				"paidcost"=>"Paid a turn/travel this trip:,viewonly",
			"City Pref,title",
				"user_showhome"=>"Show home city on stats?,bool|1",
				"user_showtravel"=>"Show travel on stats?,bool|1",
		)
	);
	return $info;
}

function cities_install()
{
	module_addhook('validatesettings');
	module_addhook('charstats');
	module_addhook('newday');
	module_addhook('village');
	module_addhook('count-travels');
	module_addhook('cities-usetravel');
	module_addhook('mountfeatures');
	module_addhook('drinks-check');
	module_addhook('stablelocs');
	module_addhook('camplocs');
	module_addhook('faq-toc');
	module_addhook('master-autochallenge');
	return TRUE;
}

function cities_uninstall()
{
	$city = getsetting('villagename', LOCATION_FIELDS);
	$inn = getsetting('innname', LOCATION_INN);
	db_query("UPDATE " . db_prefix('accounts') . " SET location = '".addslashes($city)."' WHERE location != '".addslashes($inn)."'");
	$session['user']['location'] = $city;
	return TRUE;
}

function cities_dohook($hookname,$args)
{
	global $session;

	$city = getsetting('villagename', LOCATION_FIELDS);
	$home = $session['user']['location'] == get_module_pref('homecity');
	$capital = $session['user']['location'] == $city;

	switch( $hookname )
	{
		case 'validatesettings':
			if( $args['dangerchance'] < $args['safechance'] )
			{
				$args['validation_error'] = "Danger chance must be equal to or greater than the safe chance.";
			}
		break;

		case 'charstats':
			if( $session['user']['alive'] )
			{
				if( get_module_pref('user_showhome') )
				{
                    $homeCity = get_module_pref('homecity');
                    if (!$homeCity) $homeCity = getsetting('villagename', LOCATION_FIELDS);
					addcharstat('Personal Info');
					addcharstat('Home City', $homeCity);
				}
				if( get_module_pref('user_showtravel') )
				{
					$args = modulehook('count-travels', array('available'=>0,'used'=>0));
					$free = max(0, $args['available'] - $args['used']);
					addcharstat('Extra Info');
					addcharstat('Free Travel', $free);
				}
			}
		break;

		case 'newday':
			if( $args['resurrection'] != 'true' )
			{
				set_module_pref('traveltoday', 0);
			}
			set_module_pref('paidcost', 0);
		break;

		case 'village':
			addnav($args['nav_headers']['gate']);
			addnav('cities.nav_headers.travel', 'runmodule.php?module=cities&op=travel');
			if (get_module_pref('paidcost') > 0) set_module_pref('paidcost', 0);
		break;

		case 'count-travels':
			global $playermount;
			$args['available'] += get_module_setting('allowance');
			if( $playermount && isset($playermount['mountid']) )
			{
				$id = $playermount['mountid'];
				$extra = get_module_objpref('mounts', $id, 'extratravel');
				$args['available'] += $extra;
			}
			$args['used'] += get_module_pref('traveltoday');
		break;

		case 'cities-usetravel':
			$info = modulehook('count-travels',array());
			if ( $info['used'] < $info['available'] )
			{
				set_module_pref('traveltoday',get_module_pref('traveltoday')+1);
				if( isset($args['traveltext']) ) output($args['traveltext']);
				$args['success'] = TRUE;
				$args['type'] = 'travel';
			}
			else if( $session['user']['turns'] > 0 )
			{
				$session['user']['turns']--;
				if( isset($args['foresttext']) ) output($args['foresttext']);
				$args['success'] = TRUE;
				$args['type'] = 'forest';
			}
			else
			{
				if( isset($args['nonetext']) ) output($args['nonetext']);
				$args['success'] = FALSE;
				$args['type'] = 'none';
			}
			$args['nocollapse'] = 1;
			return $args;
		break;

		case 'mountfeatures':
			$extra = get_module_objpref('mounts', $args['id'], 'extratravel');
			$args['features']['Travel'] = $extra;
		break;
		
		case 'drinks-check':
			if( $session['user']['location'] == $city )
			{
				$val = get_module_objpref('drinks', $args['drinkid'], 'servedhere');
				$args['allowdrink'] = $val;
			}
		break;

		case 'stablelocs':
			$args[$city] = sprintf_translate("The City of %s", $city);
		break;

		case 'camplocs':
			$args[$city] = sprintf_translate("The City of %s", $city);
		break;

		case 'faq-toc':
			$t = translate_inline("`@Frequently Asked Questions on Multiple Villages`0");
			output_notl("&#149;<a href='runmodule.php?module=cities&op=faq'>$t</a><br />", TRUE);
		break;

		case 'master-autochallenge':
			if( get_module_pref('homecity') != $session['user']['location'] )
			{
				$info = modulehook('cities-usetravel',
				array("foresttext"=>array("`n`n`^Startled to find your master in %s`^, your heart skips a beat, costing a forest fight from shock.", $session['user']['location']),
						"traveltext"=>array("`n`n`%Surprised at finding your master in %s`%, you feel a little less inclined to be gallivanting around the countryside today.", $session['user']['location'])));
				if( $info['success'] )
				{
					if( $info['type'] == 'travel' ) debuglog("Lost a travel because of being truant from master.");
					elseif( $info['type'] == 'forest' ) debuglog("Lost a forest fight because of being truant from master.");
					else debuglog("Lost something, not sure just what, because of being truant from master.");
				}
			}
		break;
	}

	return $args;
}

function cities_dangerscale($danger)
{
	global $session;
	$dlevel = ( $danger ) ? get_module_setting('dangerchance') : get_module_setting('safechance');
	if( $session['user']['dragonkills'] <= 1 )
	{
		$dlevel = round(.50*$dlevel, 0);
	}
	elseif ( $session['user']['dragonkills'] <= 30 )
	{
		$scalef = 50/29;
		$scale = (($session['user']['dragonkills']-1)*$scalef + 50)/100;
		$dlevel = round($scale*$dlevel, 0);
	} // otherwise, dlevel is unscaled.
	return $dlevel;
}

function cities_run()
{
	global $session;

	$op = httpget('op');
	$city = urldecode(httpget('city'));
	$continue = httpget('continue');
	$danger = httpget('d');
	$su = httpget('su');

	if( $op != 'faq' )
	{
		require_once('lib/forcednavigation.php');
		do_forced_nav(false, false);
	}

	// I really don't like this being out here, but it has to be since
	// events can define their own op=.... and we might need to handle them
	// otherwise things break.
	require_once('lib/events.php');
	if ( $session['user']['specialinc'] != '' || httpget('eventhandler') )
	{
		$in_event = handle_event('travel',"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1&",'Travel');
		if ( $in_event )
		{
			addnav('Continue',"runmodule.php?module=cities&op=travel&city=".urlencode($city)."&d=$danger&continue=1");
			module_display_events('travel',"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
			page_footer();
		}
	}

	if( $op == 'travel' )
	{
		$args = modulehook('count-travels', array('available'=>0,'used'=>0));
		$free = max(0, $args['available'] - $args['used']);
		if( $city == '' )
		{
			require_once('lib/villagenav.php');
			page_header('Travel');
			modulehook('collapse{', array('name'=>'traveldesc'));
			output("`%Travelling the world can be a dangerous occupation.");
			output("Although other villages might offer things not found in your current one, getting from village to village is no easy task, and might subject you to various dangerous creatures or brigands.");
			output("Be sure you're willing to take on the adventure before you set out, as not everyone arrives at their destination intact.");
			output("Also, pay attention to the signs, some roads are safer than others.`n");
			modulehook('}collapse');
			addnav('Forget about it');
			villagenav();
			modulehook('pre-travel');
			if( !($session['user']['superuser']&SU_EDIT_USERS) && ($session['user']['turns']<=0) && $free == 0 )
			{
				// this line rewritten so as not to clash with the hitch module.
				output("`nYou don't feel as if you could face the prospect of walking to another city today, it's far too exhausting.`n");
			}
			else
			{
				addnav('Travel');
				modulehook('travel');
			}
			module_display_events('travel',"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
			page_footer();
		}
		else
		{
			if( $continue != '1' && $su != '1' && !get_module_pref('paidcost') )
			{
				set_module_pref('paidcost', 1);
				if( $free > 0 )
				{
					// Only increment travel used if they are still within
					// their allowance.
					set_module_pref('traveltoday',get_module_pref('traveltoday')+1);
					//do nothing, they're within their travel allowance.
				}
				elseif( $session['user']['turns'] > 0 )
				{
					$session['user']['turns']--;
				}
				else
				{
					output("`n`2Hey, looks like you managed to travel with out having any forest fights. How'd you swing that?");
					debuglog("Travelled without having any forest fights, how'd they swing that?");
				}
			}
			// Let's give the lower DK people a slightly better chance.
			$dlevel = cities_dangerscale($danger);
			if( e_rand(0,100) < $dlevel && $su != '1' )
			{
				// They've been waylaid.
				if( module_events('travel', get_module_setting('travelspecialchance'),"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1&") != 0 )
				{
					page_header('Something Special!');
					if( checknavs() )
					{
						page_footer();
					}
					else
					{
						// Reset the special for good.
						$session['user']['specialinc'] = '';
						$session['user']['specialmisc'] = '';
						$skipvillagedesc = TRUE;
						$op = '';
						httpset('op', '');
						addnav('Continue',"runmodule.php?module=cities&op=travel&city=".urlencode($city)."&d=$danger&continue=1");
						module_display_events('travel',"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
						page_footer();
					}
				}

				$args = array('soberval'=>0.9,'sobermsg'=>"`&Facing your bloodthirsty opponent, the adrenaline rush helps to sober you up slightly.",'schema'=>'module-cities');
				modulehook('soberup', $args);
				require_once('lib/forestoutcomes.php');
				$sql = "SELECT *
						FROM " . db_prefix('creatures') . "
						WHERE creaturelevel = '{$session['user']['level']}'
							AND forest = 1
						ORDER BY rand(".e_rand().")
						LIMIT 1";
				$result = db_query($sql);
				restore_buff_fields();
				if( db_num_rows($result) == 0 )
				{
					// There is nothing in the database to challenge you,
					// let's give you a doppleganger.
					$badguy = array();
					$badguy['creaturename'] = "An evil doppleganger of ".$session['user']['name'];
					$badguy['creatureweapon'] = $session['user']['weapon'];
					$badguy['creaturelevel'] = $session['user']['level'];
					$badguy['creaturegold'] = 0;
					$badguy['creatureexp'] = round($session['user']['experience']/10, 0);
					$badguy['creaturehealth'] = $session['user']['maxhitpoints'];
					$badguy['creatureattack'] = $session['user']['attack'];
					$badguy['creaturedefense'] = $session['user']['defense'];
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
				$battle = TRUE;
			}
			else
			{
				set_module_pref('paidcost', 0);
				// They arrive with no further scathing.
				$session['user']['location'] = $city;
				redirect('village.php');
			}
		}
	}
	elseif( $op == 'fight' || $op == 'run' )
	{
		if( $op == 'run' && e_rand(1,5) < 3 )
		{
			// They managed to get away.
			page_header('Escape');
			output("`n`2You set off running through the forest at a breakneck pace heading back the way you came.`n`n");
			if( get_module_setting('coward') )
			{
				modulehook('cities-usetravel',
				array('foresttext'=>array("In your terror, you lose your way and become lost, losing time for a forest fight.`n`n", $session['user']['location']),
					'traveltext'=>array("In your terror, you lose your way and become lost, losing precious travel time.`n`n", $session['user']['location'])));
			}
			output("After running for what seems like hours, you finally arrive back at %s.", $session['user']['location']);

			addnav(array("Enter %s",$session['user']['location']),'village.php');
			page_footer();
		}
		$battle = TRUE;
	}
	elseif( $op == 'faq' )
	{
		cities_faq();
	}
	elseif( $op == '' )
	{
		page_header('Travel');
		output("A divine light ends the fight and you return to the road.");
		addnav('Continue your journey',"runmodule.php?module=cities&op=travel&city=".urlencode($city)."&continue=1&d=$danger");
		module_display_events('travel',"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
		page_footer();
	}

	if( $battle )
	{
		page_header("You've been waylaid!");
		require_once('battle.php');
		if( $victory )
		{
			require_once('lib/forestoutcomes.php');
			forestvictory($newenemies,"This fight would have yielded an extra turn except it was during travel.");
			addnav('Continue your journey',"runmodule.php?module=cities&op=travel&city=".urlencode($city)."&continue=1&d=$danger");
			module_display_events('travel',"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger&continue=1");
		}
		elseif( $defeat )
		{
			require_once('lib/forestoutcomes.php');
			forestdefeat($newenemies,array('travelling to %s',$city));
		}
		else
		{
			require_once('lib/fightnav.php');
			fightnav(TRUE,TRUE,"runmodule.php?module=cities&city=".urlencode($city)."&d=$danger");
		}
		page_footer();
	}

}

function cities_faq()
{
	popup_header("Multi-Village Questions");
	$c = translate_inline("Return to Contents");
	rawoutput("<a href='petition.php?op=faq'>$c</a><hr>");
	output("`n`n`c`bQuestions about the multiple village system`b`c`n");
	output("`^1. Why, oh why did you activate such a (choose one [wondrous, horrible]) feature?`n");
	output("`@For kicks, of course. We like to mess with your head.`n");
	output("But seriously, have you looked at the user list?  On lotgd.net, we've got over 6,000 people cramming themselves into the Village Square and trying to get their voices heard! Too much! Too much!`n");
	output("In the interests of sanity, we've made more chat boards. And in the interests of game continuity, we've put them into separate villages with many cool new features.`n`n");
	output("If you are a smaller server, this might not be right for you, but we think it works okay there too.`n`n");
	output("`^2. How do I go to other villages?`n");
	output("`@Walk, skate, take the bus...`n");
	output("Or press the Travel link (in the City Gates or Village Gates category) in the navigation bar.`n`n");
	output("`^3. How does travelling work?`n");
	output("`@Pretty well, actually. Thanks for asking.`n");
	output("You get some number of  free travels per day (%s on this server) in which you can travel to any other village you want.", get_module_setting('allowance'));
	output("Also, it is possible for the admin to give additional free travels with some mounts.");
	output("After that, you use up one forest fight per travel.");
	output("After that...well, we hope you like where you end up.");
	output("Since all major economic transactions come through `Q%s`@ (the capital of the region), the roads to and from there have been fortified to protect against monsters from wandering onto them.", getsetting("villagename", LOCATION_FIELDS));
	output("That was a while back though, and the precautions are no longer perfect.`n");
	output("Travel between the other villages have no such precautions.`n");
	output("In either case, you might want to heal yourself before travelling.");
	output("You have been warned.`n`n");
	output("`^4. Where's (the Inn, the forest, my training master, etc.)?`n");
	output("`@Look around. Do you see it? No? Then it's not here.`n");
	output("The problem's usually:`n");
	output("a) It's actually there, you just missed it the first time around.`n");
	output("b) It's in another village, try travelling.`n");
	output("c) It's not on this server, check out the modules installed on this server in the About link on the login page.`n");
	output("d) Are you sure you didn't just see that feature in a dream?`n`n");
	output("`^5. I've used up my free travels and forest fights. How do I travel now?`n");
	output("`@We hope you like where you've ended up, because you're stuck there until the next new day.`n`n");
	output("`^6. Can I pay for more travels?`n");
	output("`@Maybe, check out the Hunter's Lodge.");
	rawoutput("<hr><a href='petition.php?op=faq'>$c</a>");
	popup_footer();
}
?>