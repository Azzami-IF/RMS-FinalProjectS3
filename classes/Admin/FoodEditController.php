<?php

namespace Admin;

use Food;
use PDO;

class FoodEditController
{
    private Food $foodModel;
    private array|false $data;

    public function __construct(PDO $db, int $id)
    {
        $this->foodModel = new Food($db);
        $this->data = $this->foodModel->find($id);
    }

    public function getData(): array|false
    {
        return $this->data;
    }
}
