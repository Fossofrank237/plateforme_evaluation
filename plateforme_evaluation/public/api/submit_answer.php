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
    // First, check if a result record exists for this quiz and user
    $stmt = $db->prepare("SELECT id FROM resultats WHERE user_id = ? AND quiz_id = ?");
    $stmt->execute([$user_id, $input['quiz_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $resultat_id = $result ? $result['id'] : null;

    // If no result record exists, create one
    if (!$resultat_id) {
        $stmt = $db->prepare("INSERT INTO resultats (user_id, quiz_id, score) VALUES (?, ?, 0)");
        $stmt->execute([$user_id, $input['quiz_id']]);
        $resultat_id = $db->lastInsertId();
    }

    // Record the detailed answer
    $stmt = $db->prepare("INSERT INTO reponses_details 
        (resultat_id, question_id, reponse_donnee, est_correcte) 
        VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $resultat_id, 
        $input['question_id'], 
        $input['answer'], 
        $input['is_correct'] ? 1 : 0
    ]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}