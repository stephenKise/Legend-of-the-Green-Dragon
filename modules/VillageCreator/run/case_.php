<?php

$defaultVillage = getsetting('villagename', LOCATION_FIELDS);
$accountsTable = db_prefix('accounts');
$villagesTable = db_prefix('villages');
//
// Secondary ops.
//
switch ($sop) {
	case 'del':
		// Get name of city being deleted.
		$sql = "SELECT name
				FROM $villagesTable
				WHERE id = '$id'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
        $sql = "UPDATE $accountsTable SET location = '$defaultVillage' WHERE location = '{$row['name']}'";
		db_query($sql);
		db_query("DELETE FROM $villagesTable WHERE id = '$id'");
		if( db_affected_rows() > 0 )
		{
			output('`n`@City successfully deleted.`0`n`n');
			// Hook to invalidate any cache files.
			modulehook('village-invalidate-cache',array('id'=>$id,'name'=>$row['name']));
			// Hook to allow modules to delete any prefs a player might have.
			modulehook('citydeleted',array('id'=>$id,'name'=>$row['name']));
			// Delete object prefs for this city.
			module_delete_objprefs('cities',$id);
		}
		else
		{
			db_query("UPDATE $villagesTable SET active = 0 WHERE id = '$id'");
			modulehook('village-invalidate-cache',array('id'=>$id,'name'=>$row['name']));
			output('`n`$City `#%s `$was not deleted because: `&%s`$, deactivated instead.`0`n`n', $row['name'], db_error(LINK));
		}
	break;

	case 'activate':
		db_query("UPDATE $villagesTable SET active = 1 WHERE id = '$id'");

		$sql = "SELECT name
				FROM $villagesTable
				WHERE id = '$id'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		modulehook('village-invalidate-cache',array('id'=>$id,'name'=>$row['name']));
		output('`n`2City `#%s `2has been `@Activated`2.`0`n`n', $row['name']);
	break;

	case 'deactivate':
		db_query("UPDATE $villagesTable SET active = 0 WHERE id = '$id'");

		$sql = "SELECT name
				FROM $villagesTable
				WHERE id = '$id'";
		$result = db_query($sql);
		$row = db_fetch_assoc($result);
		modulehook('village-invalidate-cache',array('id'=>$id,'name'=>$row['name']));
		output('`n`2City `#%s `2has been `@Deactivated`2.`0`n`n', $row['name']);
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
	$sql = "SELECT name
			FROM $villagesTable
			WHERE active = 1;";
	$result = db_query($sql);
	$active_cities = array();
	while( $row = db_fetch_assoc($result) ) $active_cities[] = $row['name'];
}

//
// Table header links for ordering.
//
$order = httpget('order');
$order2 = ( $order == 1 ) ? 'DESC' : 'ASC';
$sortby = httpget('sortby');
$orderby = 'name '.$order2;
if( $sortby != '' )
{
	if( $sortby == 'id' ) $orderby = 'id '.$order2;
}

addnav('',$from.'&sortby=id&order='.($sortby=='id'?!$order:1));
addnav('',$from.'&sortby=name&order='.($sortby=='name'?!$order:1));

//
// Get city data and output to page.
//
$sql = "SELECT id, name, sanitized_name, active, author, travel
		FROM $villagesTable
		ORDER BY $orderby";
$result = db_query($sql);

if( db_num_rows($result) > 0 )
{
	rawoutput('<table border="0" cellpadding="2" cellspacing="1" bgcolor="#999999" align="center">');
	rawoutput("<tr class=\"trhead\"><td>$opshead</td><td align=\"center\"><a href=\"$from&sortby=id&order=".($sortby=='id'?!$order:1)."\">$id</a></td><td align=\"center\"><a href=\"$from&sortby=name&order=".($sortby=='name'?!$order:1)."\">$name</a></td><td align=\"center\">$travel</td>");
	if( $city_routes_active == TRUE ) rawoutput("<td align=\"center\">$routes</td>");
	rawoutput("<td align=\"center\">$requirements</td><td align=\"center\">$author</td></tr>");

	$i = 0;
	while( $row = db_fetch_assoc($result) )
	{
		rawoutput('<tr class="'.($i%2?'trlight':'trdark').'">');
		rawoutput('<td align="center" nowrap="nowrap">[ <a href="'.$from.'&op=edit&id='.$row['id'].'">'.$edit.'</a> |');
		addnav('',$from.'&op=edit&id='.$row['id']);

		if( $row['active'] == 1 )
		{
			rawoutput('<a href="'.$from.'&sop=deactivate&id='.$row['id'].'">'.$deac.'</a>');
			addnav('',$from.'&sop=deactivate&id='.$row['id']);
		}
		else
		{

			if( array_key_exists('module', $row) && $row['module'] )
			{
				rawoutput('<a href="'.$from.'&sop=del&id='.$row['id'].'" onClick="return confirm(\''.$conf.'\');">'.$del.'</a> |');
			}
			else
			{
				rawoutput('<a href="'.$from.'&sop=del&id='.$row['id'].'" onClick="return confirm(\''.$conf2.'\');">'.$del.'</a> |');
			}
			addnav('',$from.'&sop=del&id='.$row['id']);

			rawoutput('<a href="'.$from.'&sop=activate&id='.$row['id'].'">'.$act.'</a>');
			addnav('',$from.'&sop=activate&id='.$row['id']);
		}

		rawoutput(' | <a href="runmodule.php?module=villages&op=travel&village='.urlencode($row['name']).'&su=1">'.$visit.'</a>');
		addnav('','runmodule.php?module=villages&op=travel&village='.urlencode($row['name']).'&su=1');
		rawoutput(' ]</td><td align="center">'.$row['id'].'</td><td align="center">');
		output_notl('%s', $row['name']);
		rawoutput('</td><td align="center">'.$traveltype[$row['travel']].'</td>');

		// Decided to incorporate this piece here in it's own column for easy readability.
		if( $city_routes_active == TRUE )
		{
			rawoutput('<td align="center">');
			$sql = "SELECT value
					FROM " . db_prefix('module_objprefs') . "
					WHERE modulename = 'city_routes'
						AND objtype = 'city'
						AND setting = 'routes'
						AND objid = '{$row['id']}'";
			$result2 = db_query_cached($sql,'city_routes-'.$row['id'],86400);
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
		modulehook('cityrequirements', array('id'=>$row['id']));

		rawoutput('</td><td align="center">');
		output_notl('%s', $row['author']);
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

addnav('Help');
addnav('ReadMe',$from.'&op=readme');

addnav('New');
addnav('Generate New Village', "{$from}&op=generate");
