<?php
    function sanitizeInput($data) {
        return htmlspecialchars(trim($data));
    }

    function displayAlert($message, $type = 'success') {
        $bgColor = $type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        return "<div class='p-4 mb-4 rounded-lg {$bgColor}'>{$message}</div>";
    }

    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
?>