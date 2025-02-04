<?php
namespace Fonction;

class Fonction {

    public function __construct() {
    }

    /**
     * Gestion de l'image
     */

    public function gestionImage($uploadedImage) {
        $imageTmpPath = $uploadedImage['image']['tmp_name'];
        $imageName = $uploadedImage['image']['name'];
        $imageSize = $uploadedImage['image']['size'];
        $imageType = $uploadedImage['image']['type'];
    
        // Vérifiez le type de fichier (par exemple, jpg, png)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($imageType, $allowedTypes)) {
            // Définir le chemin de destination
            $uploadDir = '../uploads/'; // Assurez-vous que ce dossier existe et est accessible en écriture
            $destinationPath = $uploadDir . basename($imageName);
    
            // Déplacez le fichier téléchargé vers le dossier de destination
            if (move_uploaded_file($imageTmpPath, $destinationPath)) {
                echo "Image téléchargée avec succès.";
                // Retourner le chemin complet de l'image
                return $destinationPath;
            } else {
                echo "Erreur lors du téléchargement de l'image.";
                return null;
            }
        } else {
            echo "Type de fichier non autorisé. Veuillez télécharger une image au format JPG, PNG ou GIF.";
            return null;
        }
    }

    public function convertTempToMinutes($temps) {
        // Séparer les heures et les minutes
        list($heures, $minutes) = explode(':', $temps);
    
        // Convertir les heures en minutes
        $totalMinutes = ($heures * 60) + $minutes;
    
        return $totalMinutes;
    }
}
