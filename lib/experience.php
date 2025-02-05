<?php

/**
 * Returns the experience needed to advance to the next level.
 *
 * @param int $currentLvl The current level of the player.
 * @param int $currentDk Player's current dragon kills.
 * @return int The amount of experience needed to advance to the next level.
 */
function exp_for_next_level(int $currentLvl, int $currentDk): int
{
	if ($currentLvl < 1) return 0;
    $expArray = [
        1 => 100,
        2 => 400,
        3 => 1002,
        4 => 1912,
        5 => 3140,
        6 => 4707,
        7 => 6641,
        8 => 8985,
        9 => 11795,
        10 => 15143,
        11 => 19121,
        12 => 23840,
        13 => 29437,
        14 => 36071,
        15 => 43930
    ];

	foreach ($expArray as $lvl => $expRequired) {
		$expArray[$lvl] = round(
            $expRequired + ($currentDk / 4) * $lvl * 100,
            0
        );
	}
	if ($currentLvl > 15) $currentLvl = 15;
	return $expArray[$currentLvl];
}
