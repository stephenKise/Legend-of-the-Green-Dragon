<?php

declare(strict_types = 1);

namespace LOTGD;

/**
 * Interface to manage the $_SESSION;
 */
interface SessionInterface
{

    public function start(): array;
}
