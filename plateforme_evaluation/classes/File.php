<?php
namespace classes;

use Exception;

    // Gestion des fichiers
    class FileManager {
        private $uploadDir = 'uploads/';
        
        public function uploadImage($file) {
            if (!$this->isValidImage($file)) {
                throw new Exception("Format de fichier non valide");
            }
            
            $fileName = uniqid() . '_' . basename($file['name']);
            $targetPath = $this->uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return $targetPath;
            }
            throw new Exception("Erreur lors du téléchargement du fichier");
        }
        
        private function isValidImage($file) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            return in_array($file['type'], $allowedTypes);
        }
    }

?>