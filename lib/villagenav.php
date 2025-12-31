<?php

function villagenav(bool $extra = false)
{
	global $session;
	$location = $session['user']['location'];
	if ($extra === false) $extra = '';
	$args = modulehook('villagenav');
	if (array_key_exists('handled', $args) && $args['handled']) return;
	if ($session['user']['alive']) {
		addnav(
            loadTranslation('common.navs.return_to', [$location]),
            "village.php$extra"
        );
	} else {
		addnav('common.navs.shades', 'shades.php');
	}
}
