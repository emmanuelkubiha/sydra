<?php
/**
 * api/add_codification_rule.php
 * 
 * Rôle : API endpoint pour insérer une nouvelle règle de codification.
 * Utilisé via AJAX depuis le modal de codification_admin.php.
 */

session_start();
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

// Vérification des droits
if (!isset($_SESSION['role_code']) || !in_array($_SESSION['role_code'], ['ADMIN', 'GTMP_LEAD'])) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    $csrf = (string) ($_POST['csrf'] ?? '');
    if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Jeton CSRF invalide.']);
        exit;
    }

    $term = trim($_POST['term'] ?? '');
    $code = trim($_POST['replacement_code'] ?? '');

    if (!empty($term) && !empty($code)) {
        try {
            $pdo = db($config);
            
            // Vérifier si la règle existe déjà (insensible à la casse)
            $check = $pdo->prepare('SELECT COUNT(*) FROM codification_rules WHERE LOWER(term) = LOWER(?)');
            $check->execute([$term]);
            if ((int) $check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Ce terme sensible fait déjà l\'objet d\'une règle active.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO codification_rules (term, replacement_code, is_active) VALUES (?, ?, 1)");
            if ($stmt->execute([$term, $code])) {
                echo json_encode(['success' => true, 'message' => 'Règle ajoutée avec succès.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de la règle.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
