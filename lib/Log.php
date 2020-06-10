<?php

declare(strict_types = 1);

namespace LOTGD;

class Log implements LoggerInterface
{

    protected static $database = 'gamelog';

    public static function write(string $category, string $message, User $user)
    {
        $database = db_prefix(self::$database);
        $category = addslashes($category);
        $message = addslashes($message);
        db_query(
            "INSERT INTO $database (`category`, `message`, `who`)
            VALUES ('{$category}', '{$message}', {$user->getID()})"
        );
    }

    public static function read(int $logid): array
    {
        $database = db_prefix(self::$database);
        $sql = db_query("SELECT * FROM {$database} WHERE `logid` = {$logid}");
        return db_fetch_assoc($sql);
    }

    public static function findByCategory(
        string $category,
        int $offset,
        int $limit
    ): array {
        $database = db_prefix(self::$database);
        $category = addslashes($category);
        $sql = db_query(
            "SELECT * FROM {$database}
            WHERE `category` = '{$category}' LIMIT $offset, $limit"
        );
        debug("SELECT * FROM {$database} WHERE `category` = '{$category}'");
        if (db_num_rows($sql) == 0) {
            return [];
        }
        $returnArray = [];
        while ($row = db_fetch_assoc($sql)) {
            array_push($returnArray, $row);
        }
        return $returnArray;
    }
}
