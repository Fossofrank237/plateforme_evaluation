<?php
namespace classes;

// Contrôleur AJAX
class AJAXController {
    public function handleRequest($action) {
        header('Content-Type: application/json');
        
        switch ($action) {
            case 'submit_answer':
                return $this->handleAnswerSubmission();
            case 'validate_captcha':
                return $this->validateCaptcha();
            default:
                http_response_code(400);
                return json_encode(['error' => 'Action non valide']);
        }
    }
    
    private function handleAnswerSubmission() {
        if (!isset($_POST['quiz_id'], $_POST['question_id'], $_POST['answer'])) {
            return json_encode(['error' => 'Données manquantes']);
        }
        
        // Logique de validation de la réponse
        // Retourner le résultat
        return json_encode([
            'success' => true,
            'correct' => true,
            'next_question' => $this->getNextQuestion($_POST['quiz_id'], $_POST['question_id'])
        ]);
    }
    
    private function validateCaptcha() {
        if (!isset($_POST['captcha_code'])) {
            return json_encode(['error' => 'Code captcha manquant']);
        }
        
        // Logique de validation du captcha
        return json_encode(['valid' => true]);
    }
    
    private function getNextQuestion($quizId, $currentQuestionId) {
        // Logique pour obtenir la question suivante
        return [];
    }
}