<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

require_once '../config/Database.php';
require_once '../classes/Auth.php';

use Config\Database;
use Classes\Auth;

header('Content-Type: application/json');

// Ensure user is authenticated
$user_id = Auth::getCurrentUserId();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$db = Database::getInstance()->getConnection();

try {
    // Update the final result
    $stmt = $db->prepare("UPDATE resultats 
        SET score = ?, 
        temps_pris = TIMESTAMPDIFF(SECOND, created_at, NOW()) 
        WHERE user_id = ? AND quiz_id = ?");
    $stmt->execute([
        $input['score'], 
        $user_id, 
        $input['quiz_id']
    ]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}