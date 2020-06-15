<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\User;


use Bitsnbytes\Models\Model;
use PDO;

class UserRepository extends Model
{
    /**
     * Fetch a single user from the database by searching for the username. Throws an <tt>UserNotFoundException</tt>
     *
     * @param string $username
     *
     * @return User
     * @throws UserNotFoundException
     */
    public function fetchUserByUsername(string $username): User
    {
        $stmt = $this->pdo->prepare('SELECT uid, username, password FROM users WHERE username = :username');
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $rslt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rslt === false) {
            throw new UserNotFoundException();
        }

        return $this->convertAssocToUser($rslt);
    }

    /**
     * Convert the associative array returned from <tt>PDOStatement::fetch</tt> into an instance of <tt>User</tt>.
     *
     * @param array<string> $query_result <p>Result from <tt>PDOStatement::fetch</tt> which <i>must</i> contain the keys
     *                                    <ul><li><b>uid</b> ID of user
     *                                    <li><b>username</b> Username
     *                                    <li><b>password</b> Hashed password
     *                                    </ul>
     *
     * @return User Valid entry with unchanged <tt>$query_result</tt> data
     */
    private function convertAssocToUser(array $query_result): User
    {
        return new User(
            intval($query_result['uid']),
            $query_result['username'],
            $query_result['password']
        );
    }

    /**
     * Fetch a single user from the database by searching for the user id. Throws an <tt>UserNotFoundException</tt>
     *
     * @param int $uid
     *
     * @return User
     * @throws UserNotFoundException
     */
    public function fetchUserByID(?int $uid): User
    {
        if($uid === null) {
            return new User(-1, 'Anonymous', 'xxxxx');
        }
        $stmt = $this->pdo->prepare('SELECT uid, username, password FROM users WHERE uid = :uid');
        $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
        $stmt->execute();
        $rslt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rslt === false) {
            throw new UserNotFoundException();
        }

        return $this->convertAssocToUser($rslt);
    }
}