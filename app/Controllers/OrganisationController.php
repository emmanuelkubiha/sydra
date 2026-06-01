<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class OrganisationController
{
    public function index(): void
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        $rows = $pdo->query('SELECT id, name, email, contact_email, website, logo_url, is_active, created_at FROM organizations ORDER BY id DESC')->fetchAll();

        View::render('organisations/index', [
            'title' => 'Organisations',
            'rows' => $rows,
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);

        unset($_SESSION['success'], $_SESSION['error']);
    }

    public function store(): void
    {
        Auth::requireLogin();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            header('Location: ?r=organisations');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $contactEmail = trim((string) ($_POST['contact_email'] ?? ''));
        $website = trim((string) ($_POST['website'] ?? ''));
        $logoUrl = trim((string) ($_POST['logo_url'] ?? ''));

        if (isset($_FILES['logo_file']) && is_array($_FILES['logo_file']) && (int) ($_FILES['logo_file']['error'] ?? 4) === 0) {
            $tmp = (string) ($_FILES['logo_file']['tmp_name'] ?? '');
            $nameFile = (string) ($_FILES['logo_file']['name'] ?? 'logo.png');
            $ext = strtolower(pathinfo($nameFile, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                $safe = 'org_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $targetDir = __DIR__ . '/../../public/uploads/logos';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }
                $targetPath = $targetDir . '/' . $safe;
                if (@move_uploaded_file($tmp, $targetPath)) {
                    $logoUrl = 'uploads/logos/' . $safe;
                }
            }
        }

        if ($name === '') {
            $_SESSION['error'] = 'Nom organisation obligatoire.';
            header('Location: ?r=organisations');
            exit;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO organizations (name, email, contact_email, website, logo_url, is_active) VALUES (:name, :email, :contact_email, :website, :logo_url, 1)');
        $stmt->execute([
            'name' => $name,
            'email' => $email !== '' ? $email : null,
            'contact_email' => $contactEmail !== '' ? $contactEmail : null,
            'website' => $website !== '' ? $website : null,
            'logo_url' => $logoUrl !== '' ? $logoUrl : null,
        ]);

        $_SESSION['success'] = 'Organisation creee.';
        header('Location: ?r=organisations');
        exit;
    }

    public function toggle(): void
    {
        Auth::requireLogin();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['error'] = 'Token CSRF invalide.';
            header('Location: ?r=organisations');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['is_active'] ?? 0);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE organizations SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $active, 'id' => $id]);

        $_SESSION['success'] = 'Organisation mise a jour.';
        header('Location: ?r=organisations');
        exit;
    }
}
