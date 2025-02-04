<?php
require_once '../config/Database.php';
use Config\Database;

$db = Database::getInstance()->getConnection();

$quiz_id = $_GET['quiz_id'] ?? null;
if (!$quiz_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Quiz ID missing']);
    exit();
}

$stmt = $db->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY ordre ASC");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($questions);
