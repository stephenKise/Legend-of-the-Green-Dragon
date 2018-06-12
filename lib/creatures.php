<?php

declare(strict_types = 1);

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
    for ($i = 1; $i < $level; $i++) {
        $attack += 2;
    }
    return $attack;
}

function creature_defense(int $level): int
{
    $defense = 0;
    switch ($level) {
        case 18:
            $defense++;
        case 17:
            $defense += 2;
        case 16:
            $defense++;
        case 15:
            $defense++;
        case 14:
            $defense += 2;
        case 13:
            $defense++;
        case 12:
            $defense += 2;
        case 11:
            $defense++;
        case 10:
            $defense++;
        case 9:
            $defense += 2;
        case 8:
            $defense++;
        case 7:
            $defense += 2;
        case 6:
            $defense++;
        case 5:
            $defense++;
        case 4:
            $defense += 2;
        case 3:
            $defense++;
        case 2:
            $defense += 2;
        case 1:
            $defense++;
    }
    return $defense;
}

function creature_exp(int $level): int
{
    switch ($level) {
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
    switch ($level) {
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
