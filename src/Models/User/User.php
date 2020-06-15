<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\User;


class User
{
    public int $uid;
    public string $username;
    public string $password_hash;

    /**
     * User constructor.
     *
     * @param int    $uid
     * @param string $username
     * @param string $password_hash
     */
    public function __construct(int $uid, string $username, string $password_hash)
    {
        $this->uid = $uid;
        $this->username = $username;
        $this->password_hash = $password_hash;
    }
}