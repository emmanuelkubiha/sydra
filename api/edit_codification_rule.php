<?php
/**
 * api/edit_codification_rule.php
 * 
 * Rôle : API endpoint pour modifier une règle de codification existante.
 * Utilisé via AJAX depuis le modal de modification dans codification_admin.php.
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

    $id = (int) ($_POST['id'] ?? 0);
    $term = trim($_POST['term'] ?? '');
    $code = trim($_POST['replacement_code'] ?? '');
    $isActive = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

    if ($id > 0 && !empty($term) && !empty($code)) {
        try {
            $pdo = db($config);
            
            // Vérifier si un autre terme identique existe déjà (insensible à la casse)
            $check = $pdo->prepare('SELECT COUNT(*) FROM codification_rules WHERE LOWER(term) = LOWER(?) AND id != ?');
            $check->execute([$term, $id]);
            if ((int) $check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Un autre terme identique existe déjà dans le dictionnaire.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE codification_rules SET term = ?, replacement_code = ?, is_active = ? WHERE id = ?");
            if ($stmt->execute([$term, $code, $isActive, $id])) {
                echo json_encode(['success' => true, 'message' => 'Règle modifiée avec succès.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la règle.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Données incomplètes.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
