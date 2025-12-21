<?php
require_once __DIR__ . '/classes/PageBootstrap.php';
PageBootstrap::fromRootDir(__DIR__);
session_destroy();

header('Location: login.php');
exit;
