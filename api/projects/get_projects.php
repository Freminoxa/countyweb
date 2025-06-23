<?php
header('Content-Type: application/json');
require '../config/database.php';

$db = (new Database())->getConnection();
$query = "SELECT * FROM projects ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$projects = $stmt->fetchAll();

echo json_encode($projects);
?>