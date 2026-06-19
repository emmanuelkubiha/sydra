<?php
/**
 * api/delete_codification_rule.php
 * 
 * Rôle : API endpoint pour supprimer une règle de codification.
 * Utilisé via AJAX depuis le modal de suppression dans codification_admin.php.
 */

session_start();
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

// Vérification des droits
if (!isset($_SESSION['role_code']) || !in_array($_SESSION['role_code'], ['ADMIN', 'GTMP_LEAD'], true)) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    // CSRF verification
    $csrf = (string) ($_POST['csrf'] ?? '');
    if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Jeton CSRF invalide.']);
        exit;
    }

    if ($id > 0) {
        try {
            $pdo = db($config);
            
            // Vérifier si la règle existe
            $check = $pdo->prepare('SELECT COUNT(*) FROM codification_rules WHERE id = ?');
            $check->execute([$id]);
            if ((int) $check->fetchColumn() > 0) {
                $delete = $pdo->prepare('DELETE FROM codification_rules WHERE id = ?');
                if ($delete->execute([$id])) {
                    echo json_encode(['success' => true, 'message' => 'Règle supprimée avec succès.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors du retrait de la règle.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Règle introuvable.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID invalide.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
