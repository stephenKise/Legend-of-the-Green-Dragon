<?php

function superusernav()
{
	global $SCRIPT_NAME, $session;
	addnav('common.nav_headers.navigation');
	if ($session['user']['superuser'] &~ SU_DOESNT_GIVE_GROTTO) {
		$script = substr($SCRIPT_NAME, 0, strpos($SCRIPT_NAME, '.'));
		if ($script != 'superuser') {
			$args = modulehook('grottonav');
			if (!array_key_exists('handled', $args) || !$args['handled']) {
				addnav('common.navs.grotto', 'superuser.php');
			}
		}
	}
	$args = modulehook('mundanenav');
	if (!array_key_exists('handled', $args) || !$args['handled'])
		addnav('common.navs.mundane', 'village.php');
}
