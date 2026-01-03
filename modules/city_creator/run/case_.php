<?php
	addnav('Editor');
	addnav('Add a City',$from.'&op=edit');

	//
	// Secondary ops.
	//
	switch( $sop )
	{
		case 'del':
			// Get name of city being deleted.
			$sql = "SELECT cityname
					FROM " . db_prefix('cities') . "
					WHERE cityid = '$cityid'";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);

			db_query("UPDATE " . db_prefix('accounts') . " SET location = '".getsetting('villagename', LOCATION_FIELDS)."' WHERE location = '{$row['cityname']}'");
			db_query("DELETE FROM " . db_prefix('cities') . " WHERE cityid = '$cityid'");
			if( db_affected_rows() > 0 )
			{
				output('`n`@City successfully deleted.`0`n`n');
				// Hook to invalidate any cache files.
				modulehook('cityinvalidatecache',array('cityid'=>$cityid,'cityname'=>$row['cityname']));
				// Hook to allow modules to delete any prefs a player might have.
				modulehook('citydeleted',array('cityid'=>$cityid,'cityname'=>$row['cityname']));
				// Delete object prefs for this city.
				module_delete_objprefs('cities',$cityid);
			}
			else
			{
				db_query("UPDATE " . db_prefix('cities') . " SET cityactive = 0 WHERE cityid = '$cityid'");
				modulehook('cityinvalidatecache',array('cityid'=>$cityid,'cityname'=>$row['cityname']));
				output('`n`$City `#%s `$was not deleted because: `&%s`$, deactivated instead.`0`n`n', $row['cityname'], db_error(LINK));
			}
		break;

		case 'activate':
			db_query("UPDATE " . db_prefix('cities') . " SET cityactive = 1 WHERE cityid = '$cityid'");

			$sql = "SELECT cityname
					FROM " . db_prefix('cities') . "
					WHERE cityid = '$cityid'";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			modulehook('cityinvalidatecache',array('cityid'=>$cityid,'cityname'=>$row['cityname']));
			output('`n`2City `#%s `2has been `@Activated`2.`0`n`n', $row['cityname']);
		break;

		case 'deactivate':
			db_query("UPDATE " . db_prefix('cities') . " SET cityactive = 0 WHERE cityid = '$cityid'");

			$sql = "SELECT cityname
					FROM " . db_prefix('cities') . "
					WHERE cityid = '$cityid'";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			modulehook('cityinvalidatecache',array('cityid'=>$cityid,'cityname'=>$row['cityname']));
			output('`n`2City `#%s `2has been `@Deactivated`2.`0`n`n', $row['cityname']);
		break;
	}

	$opshead = translate_inline('Ops');
	$id = translate_inline('City ID');
	$name = translate_inline('City Name');
	$routes = translate_inline('Routes');
	$travel = translate_inline('Travel To');
	$traveltype = translate_inline(array('Safe','Dangerous','Off'));
	$requirements = translate_inline('Requirements');
	$author = translate_inline('Author');

	$activity = translate_inline('Activity');
	$edit = translate_inline('Edit');
	$del = translate_inline('Del');
	$deac = translate_inline('Deactivate');
	$act = translate_inline('Activate');
	$visit = translate_inline('Visit');
	$yesno = translate_inline(array('`@Yes','`$No'));
	$conf = translate_inline('This city was installed by another module, to remove it please uninstall the module!');
	$conf2 = translate_inline('Are you sure you wish to delete this city? Any object prefs will also be deleted!');

	$city_routes_active = FALSE;
	if( is_module_active('city_routes') )
	{
		$city_routes_active = TRUE;
		$sql = "SELECT cityname
				FROM " . db_prefix('cities') . "
				WHERE cityactive = 1;";
		$result = db_query($sql);
		$active_cities = array();
		while( $row = db_fetch_assoc($result) ) $active_cities[] = $row['cityname'];
	}

	//
	// Table header links for ordering.
	//
	$order = httpget('order');
	$order2 = ( $order == 1 ) ? 'DESC' : 'ASC';
	$sortby = httpget('sortby');
	$orderby = 'cityname '.$order2;
	if( $sortby != '' )
	{
		if( $sortby == 'cityid' ) $orderby = 'cityid '.$order2;
	}

	addnav('',$from.'&sortby=cityid&order='.($sortby=='cityid'?!$order:1));
	addnav('',$from.'&sortby=cityname&order='.($sortby=='cityname'?!$order:1));

	//
	// Get city data and output to page.
	//
	$sql = "SELECT cityid, cityname, cityactive, cityauthor, citytravel
			FROM " . db_prefix('cities') . "
			ORDER BY $orderby";
	$result = db_query($sql);

	if( db_num_rows($result) > 0 )
	{
		rawoutput('<table border="0" cellpadding="2" cellspacing="1" bgcolor="#999999" align="center">');
		rawoutput("<tr class=\"trhead\"><td>$opshead</td><td align=\"center\"><a href=\"$from&sortby=cityid&order=".($sortby=='cityid'?!$order:1)."\">$id</a></td><td align=\"center\"><a href=\"$from&sortby=cityname&order=".($sortby=='cityname'?!$order:1)."\">$name</a></td><td align=\"center\">$travel</td>");
		if( $city_routes_active == TRUE ) rawoutput("<td align=\"center\">$routes</td>");
		rawoutput("<td align=\"center\">$requirements</td><td align=\"center\">$author</td></tr>");

		$i = 0;
		while( $row = db_fetch_assoc($result) )
		{
			rawoutput('<tr class="'.($i%2?'trlight':'trdark').'">');
			rawoutput('<td align="center" nowrap="nowrap">[ <a href="'.$from.'&op=edit&cityid='.$row['cityid'].'">'.$edit.'</a> |');
			addnav('',$from.'&op=edit&cityid='.$row['cityid']);

			if( $row['cityactive'] == 1 )
			{
				rawoutput('<a href="'.$from.'&sop=deactivate&cityid='.$row['cityid'].'">'.$deac.'</a>');
				addnav('',$from.'&sop=deactivate&cityid='.$row['cityid']);
			}
			else
			{

				if( array_key_exists('module', $row) && $row['module'] )
				{
					rawoutput('<a href="'.$from.'&sop=del&cityid='.$row['cityid'].'" onClick="return confirm(\''.$conf.'\');">'.$del.'</a> |');
				}
				else
				{
					rawoutput('<a href="'.$from.'&sop=del&cityid='.$row['cityid'].'" onClick="return confirm(\''.$conf2.'\');">'.$del.'</a> |');
				}
				addnav('',$from.'&sop=del&cityid='.$row['cityid']);

				rawoutput('<a href="'.$from.'&sop=activate&cityid='.$row['cityid'].'">'.$act.'</a>');
				addnav('',$from.'&sop=activate&cityid='.$row['cityid']);
			}

			rawoutput(' | <a href="runmodule.php?module=cities&op=travel&city='.$row['cityname'].'&su=1">'.$visit.'</a>');
			addnav('','runmodule.php?module=cities&op=travel&city='.$row['cityname'].'&su=1');
			rawoutput(' ]</td><td align="center">'.$row['cityid'].'</td><td align="center">');
			output_notl('%s', $row['cityname']);
			rawoutput('</td><td align="center">'.$traveltype[$row['citytravel']].'</td>');

			// Decided to incorporate this piece here in it's own column for easy readability.
			if( $city_routes_active == TRUE )
			{
				rawoutput('<td align="center">');
				$sql = "SELECT value
						FROM " . db_prefix('module_objprefs') . "
						WHERE modulename = 'city_routes'
							AND objtype = 'city'
							AND setting = 'routes'
							AND objid = '{$row['cityid']}'";
				$result2 = db_query_cached($sql,'city_routes-'.$row['cityid'],86400);
				$row2 = db_fetch_assoc($result2);
				$routes = $row2['value'];
				if( $routes != '' )
				{
					$routes = explode(',', $routes);
					foreach( $routes as $route )
					{
						if( in_array($route, $active_cities) ) output_notl('`@%s`n', $route);
						else output_notl('`$%s`n', $route);
					}
				}
				else
				{
					output('`&All');
				}
				rawoutput('</td>');
			}

			rawoutput('<td>');
			// Hook to add info to the requirements column.
			modulehook('cityrequirements', array('cityid'=>$row['cityid']));

			rawoutput('</td><td align="center">');
			output_notl('%s', $row['cityauthor']);
			rawoutput('</td></tr>');
			$i++;
		}
		rawoutput('</table><br />');

		if( $city_routes_active == TRUE )
		{
			output('`&`bCity Routes:`b `7Green means the city is active, red inactive.`0`n`n');
		}

		output('`2If you wish to delete a city, you have to deactivate it first. If there is anyone in this city when it is deleted then they will be transported to the Capital, %s.`n`n', getsetting('villagename', LOCATION_FIELDS));
		output('The city ID is unique to each city. If you change it then just remember that any object prefs assigned to it wont show.');
	}
	else
	{
		output('`n`3There are no cities installed, how about adding a few?`n`n');
	}

	addnav('Install');
	addnav('Eric Stevens\' Cities',$from.'&op=installcities');

	addnav('Help');
	addnav('ReadMe',$from.'&op=readme');
?>