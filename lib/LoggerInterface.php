<?php

declare(strict_types = 1);

namespace LOTGD;

interface LoggerInterface
{

    public static function write(string $category, string $message, User $user);

    public static function read(int $logid): array;

    public static function findByCategory(
        string $category,
        int $offset,
        int $limit
    ): array;
}
