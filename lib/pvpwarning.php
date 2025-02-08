<?php

/**
 * Provide a PvP warning message prior to combat, expose hook for warnings
 * 
 * @todo Remove the output from this function
 * @param bool $lostImmunity Has the player broken their PvP immunity
 * @return void
 */
function pvpwarning(bool $lostImmunity = false): void
{
	global $session;
	$immunityLength = getsetting('pvpimmunity', 5);
	$minExp = getsetting('pvpminexp', 1500);
	if (
        $session['user']['age'] <= $immunityLength
        && $session['user']['dragonkills'] == 0 
        && $session['user']['pk'] == 0 
        && $session['user']['experience'] <= $minExp
    ) {
	    modulehook('pvpwarning', ['isImmune' => !$lostImmunity]);
		if ($lostImmunity) {
			output(
                "`\$Warning!`^ Since you were under PvP immunity, but have
                chosen to attack another player, you have lost this immunity!
                `n`n"
            );
			$session['user']['pk'] = 1;
            return;
        }
		output(
            "`\$Warning!`^ Players are immune from PvP combat for their first
            %s days in the game or until they have earned %s experience,
            or until they attack another player.  If you choose to attack
            another player, you will lose this immunity!
            `n`n",
            $immunityLength,
            $minExp
        );
	}
}
