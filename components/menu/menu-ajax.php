<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;

$db = new Database();
$conn = $db->connect();

$query = "SELECT * FROM module WHERE active = 1 ORDER BY position ASC";

$result = $db->dbQuery($query);

$modules = $db->dbFetchAll($result);

header('Content-Type: application/json');
echo json_encode($modules);
