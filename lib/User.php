<?php

declare(strict_types = 1);

namespace LOTGD;

/**
 * Model for a user.
 */
class User implements AccountInterface, SessionInterface
{

    public $user;
    protected $ID;
    protected $login;
    protected $name;
    protected $hitpoints;
    protected $maxHitpoints;
    protected $attack;
    protected $defense;
    protected $level;
    protected $experience;

    public function __construct(int $acctid)
    {
        $this->ID = $acctid;
        $this->user = $this->start();
    }

    public function start(): array
    {
        // Unfortunately, using db_*() for now.
        // Will switch when this matures a bit more.
        $accounts = db_prefix('accounts');
        $sql = db_query("SELECT * FROM $accounts WHERE `acctid` = {$this->ID} LIMIT 1");
        $_SESSION['user'] = db_fetch_assoc($sql);
        return $_SESSION['user'];
    }

    public function getID(): int
    {
        return (int) $this->user['acctid'];
    }

    public function getName(): string
    {
        return $this->user['name'];
    }

    public function getHitpoints(): int
    {
        return (int) $this->user['hitpoints'];
    }

    public function getMaxHitpoints(): int
    {
        return (int) $this->user['maxHitpoints'];
    }

    public function getAttack(): int
    {
        return (int) $this->user['attack'];
    }

    public function getDefense(): int
    {
        return (int) $this->user['defense'];
    }

    public function getLevel(): int
    {
        return (int) $this->user['level'];
    }

    public function getExperience(): int
    {
        return (int) $this->user['experience'];
    }

    public function saveUser()
    {
        $accounts = db_prefix('accounts');
        $sql = "UPDATE $accounts SET ";
        foreach ($this->user as $key => $value) {
            if ($this->user[$key] != $_SESSION['user'][$key]) {
                $sql .= "`{$key}` = '" . addslashes($value) . "' ";
            }
        }
        db_query("$sql WHERE `acctid` = {$this->ID}");
    }

    public function __destruct()
    {
        if ($_SESSION['user'] != $this->user) {
            $this->saveUser();
        }
    }
}
