<?php

declare(strict_types=1);

namespace Bitsbytes\Models;

use PDO;

abstract class Model
{
    protected PDO $pdo;

    public function __construct(PDO $db)
    {
        $this->pdo = $db;
    }
}