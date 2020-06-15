<?php

declare(strict_types=1);


namespace Bitsnbytes\Helpers;


use Bitsnbytes\Models\User\UserNotFoundException;
use Bitsnbytes\Models\User\UserRepository;
use PDO;

class AuthManager
{
    private SessionManager $session_manager;
    private PDO $pdo;
    private UserRepository $user_repository;

    public function __construct(SessionManager $session_manager, PDO $pdo, UserRepository $user_repository)
    {
        $this->session_manager = $session_manager;
        $this->pdo = $pdo;
        $this->user_repository = $user_repository;
    }

    /**
     * Lookup <b>$username</b> in database and fetch corresponding <tt>User</tt> instance. If username and password are
     * correct, a new session is started and the user id is stored in <tt>$_SESSION['user_id']</tt>.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool <b>TRUE</b> if username and password correct, <b>FALSE</b> if username or password incorrect.
     */
    public function tryLogin(string $username, string $password): bool
    {
        try {
            $user = $this->user_repository->fetchUserByUsername($username);
        } catch (UserNotFoundException $exception) {
            return false;
        }

        if (password_verify($this->prehashPassword($password), $user->password_hash)) {
            $this->session_manager->regenerate_id();
            $_SESSION['user_id'] = $user->uid;
            return true;
        }
        return false;
    }

    /**
     * Create a base64 encoded SHA-256 hash of the raw password. This is necessary as bcrypt (used by
     * <tt>password_hash</tt> in <tt>AuthManager::hashPassword</tt> truncates passwords to 72 chars and on <tt>NUL</tt>
     * bytes which both can occur when not pre-hashing or just pre-hashing with SHA-256.
     *
     * @see https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
     *
     * @param string $password
     *
     * @return string binary-safe password hash which can be used as input for <tt>password_hash()</tt> or
     *                <tt>password_verify()</tt>
     */
    private function prehashPassword(string $password): string
    {
        return base64_encode(hash('sha256', $password, true));
    }

    /**
     * Safely hash password with state of the art security.
     *
     * @param string $password
     *
     * @return string|null Hashed and salted password, <b>NULL</b> on failure
     */
    private function hashPassword(string $password): ?string
    {
        $hash = password_hash($this->prehashPassword($password), PASSWORD_DEFAULT);
        if($hash === false) {
            return null;
        }
        return $hash;
    }
}