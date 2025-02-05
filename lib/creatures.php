<?php
declare(strict_types=1);

function creature_stats(int $level): array
{
    $stats = [
        'creaturelevel' => $level,
        'creaturehealth' => creature_health($level),
        'creatureattack' => creature_attack($level),
        'creaturedefense' => creature_defense($level),
        'creatureexp' => creature_exp($level),
        'creaturegold' => creature_gold($level),
    ];
    return $stats;
}

function creature_health(int $level): int
{
    switch ($level) {
        case 1:
        case 2:
        case 3:
        case 4:
            $health = $level * 11 - 1;
            break;
        case 5:
            $health = 53;
            break;
        case 6:
        case 7:
        case 8:
        case 9:
            $health = $level * 10 + 4;
            break;
        case 10:
        case 11:
        case 12:
        case 13:
        case 14:
        case 15:
            $health = $level * 10 + 5;
            break;
        case 16:
            $health = 166;
            break;
        case 17:
            $health = 178;
            break;
        case 18:
            $health = 190;
            break;
    }
    return $health;
}

function creature_attack(int $level): int
{
    $attack = 1;
    for($i = 1; $i < $level; $i++) {
        $attack += 2;
    }
    return $attack;
}

// Not sure if this was intended, but defense only is ever 1 or 2.
// Seems as if it was meant to increase by 1/2 per level.
// @TODO: Design a better algorithm for defense.  
function creature_defense(int $level): int
{
    $defense = 0;
    switch ($level) {
        case 1:
        case 3:
        case 5:
        case 6:
        case 8:
        case 10:
        case 11:
        case 13:
        case 15:
        case 16:
        case 18:
            $defense++;
            break;
        case 2:
        case 4:
        case 7:
        case 9:
        case 12:
        case 14:
        case 17:
            $defense += 2;
            break;
    }
    return $defense;
}

function creature_exp(int $level): int
{
    switch($level) {
        case 1:
        case 2:
        case 3:
            $exp = $level * 10 + 4;
            break;
        case 4:
        case 5:
            $exp = $level * 10 + 5;
            break;
        case 6:
        case 7:
            $exp = $level * 11;
            break;
        case 8:
        case 9:
            $exp = $level * 12 - 7;
            break;
        case 10:
        case 11:
        case 12:
            $exp = round(13.5 * $level - 21.17);
            break;
        case 13:
        case 14:
        case 15:
        case 16:
            $exp = round(0.5 * ($level ** 2) + 2.5 * $level + 39);
            break;
        default:
            $exp = 0;
            break;
    }
    return intval($exp);
}

function creature_gold(int $level): int
{
    switch($level) {
        case 1:
        case 2:
        case 3:
        case 4:
            $gold = round(-4.5 * ($level ** 3) + 22 * ($level ** 2) + 26.5 * $level - 8);
            break;
        case 17:
        case 18:
            $gold = 0;
            break;
        default:
            $gold = round(-0.1673326 * ($level ** 2) + 36.590909 * $level + 19.904594);
            break;
    }
    return intval($gold);
}
