<?php

declare(strict_types = 1);

namespace LOTGD;

/**
 * Interface to manage accounts.
 */
interface AccountInterface
{

    public function getID(): int;

    public function getName(): string;

    public function getHitpoints(): int;

    public function getMaxHitpoints(): int;

    public function getAttack(): int;

    public function getDefense(): int;

    public function getLevel(): int;

    public function getExperience(): int;

    public function saveUser();
}
