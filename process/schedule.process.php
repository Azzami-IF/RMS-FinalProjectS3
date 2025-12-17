<?php
session_start();

require_once '../config/database.php';
require_once '../classes/Schedule.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$db = (new Database(require '../config/env.php'))->getConnection();
$schedule = new Schedule($db);

$schedule->create(
    $_SESSION['user']['id'],
    $_POST['food_id'],
    $_POST['schedule_date']
);

header('Location: ../dashboard.php');
