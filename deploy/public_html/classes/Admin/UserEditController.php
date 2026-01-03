<?php

namespace Admin;

use User;
use PDO;

class UserEditController
{
    private User $userModel;
    /** @var array|false */
    private $userData;

    public function __construct(PDO $db, int $id)
    {
        $this->userModel = new User($db);
        $this->userData = $this->userModel->find($id);
    }

    /** @return array|false */
    public function getUserData()
    {
        return $this->userData;
    }
}
