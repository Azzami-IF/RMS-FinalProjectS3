<?php

namespace Admin;

use Food;
use PDO;

class FoodEditController
{
    private Food $foodModel;
    /** @var array|false */
    private $data;

    public function __construct(PDO $db, int $id)
    {
        $this->foodModel = new Food($db);
        $this->data = $this->foodModel->find($id);
    }

    /** @return array|false */
    public function getData()
    {
        return $this->data;
    }
}
