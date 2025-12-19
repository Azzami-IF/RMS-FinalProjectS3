<?php

namespace Admin;

use User;
use PDO;

class UserEditController
{
    private User $userModel;
    private array|false $userData;

    public function __construct(PDO $db, int $id)
    {
        $this->userModel = new User($db);
        $this->userData = $this->userModel->find($id);
    }

    public function getUserData(): array|false
    {
        return $this->userData;
    }
}
