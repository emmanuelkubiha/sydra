<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class ProfilController
{
    public function motDePasseForm(): void
    {
        Auth::requireLogin();

        View::render('profil/mot_de_passe', [
            'title' => 'Mon compte - Mot de passe',
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);

        unset($_SESSION['success'], $_SESSION['error']);
    }

    public function changerMotDePasse(): void
    {
        Auth::requireLogin();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['error'] = 'Session invalide. Reessayez.';
            header('Location: ?r=profil/mot-de-passe');
            exit;
        }

        $user = Auth::user();
        if ($user === null) {
            header('Location: ?r=login');
            exit;
        }

        $actuel = (string) ($_POST['mot_de_passe_actuel'] ?? '');
        $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
        $confirmation = (string) ($_POST['confirmation_mot_de_passe'] ?? '');

        if ($nouveau !== $confirmation) {
            $_SESSION['error'] = 'La confirmation du mot de passe ne correspond pas.';
            header('Location: ?r=profil/mot-de-passe');
            exit;
        }

        $exigences = $this->validerExigences($nouveau);
        if ($exigences !== null) {
            $_SESSION['error'] = $exigences;
            header('Location: ?r=profil/mot-de-passe');
            exit;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $user['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($actuel, (string) $row['password_hash'])) {
            $_SESSION['error'] = 'Le mot de passe actuel est incorrect.';
            header('Location: ?r=profil/mot-de-passe');
            exit;
        }

        $hash = password_hash($nouveau, PASSWORD_BCRYPT);

        $updated = false;
        try {
            $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = :hash WHERE id = :id');
            $updated = $stmt->execute(['hash' => $hash, 'id' => (int) $user['id']]);
        } catch (\Throwable $e) {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $updated = $stmt->execute(['hash' => $hash, 'id' => (int) $user['id']]);
        }

        if (!$updated) {
            $_SESSION['error'] = 'Impossible de modifier le mot de passe pour le moment.';
            header('Location: ?r=profil/mot-de-passe');
            exit;
        }

        $_SESSION['success'] = 'Mot de passe modifie avec succes.';
        header('Location: ?r=profil/mot-de-passe');
        exit;
    }

    private function validerExigences(string $motDePasse): ?string
    {
        if (strlen($motDePasse) < 10) {
            return 'Le mot de passe doit contenir au moins 10 caracteres.';
        }

        if (!preg_match('/[A-Z]/', $motDePasse)) {
            return 'Le mot de passe doit contenir au moins une majuscule.';
        }

        if (!preg_match('/[a-z]/', $motDePasse)) {
            return 'Le mot de passe doit contenir au moins une minuscule.';
        }

        if (!preg_match('/[0-9]/', $motDePasse)) {
            return 'Le mot de passe doit contenir au moins un chiffre.';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $motDePasse)) {
            return 'Le mot de passe doit contenir au moins un caractere special.';
        }

        return null;
    }
}
