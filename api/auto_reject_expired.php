<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function auto_reject_respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_auto_status(string $value): string
{
    $value = strtolower(trim($value));
    $value = str_replace(['é', 'è', 'ê', 'à', 'ù', 'ô', 'î', 'ï', 'ç', "'", '-'], ['e', 'e', 'e', 'a', 'u', 'o', 'i', 'i', 'c', ' ', ' '], $value);
    return preg_replace('/\s+/', ' ', $value) ?? $value;
}

function resolve_auto_reject_role(PDO $pdo, int $userId): string
{
    $columnsStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
    $columnsStmt->execute(['table_name' => 'users']);
    $columns = array_map('strtolower', $columnsStmt->fetchAll(PDO::FETCH_COLUMN));

    if (in_array('role_id', $columns, true)) {
        $stmt = $pdo->prepare('SELECT COALESCE(r.code, "") AS role_code FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        return strtoupper(trim((string) ($stmt->fetchColumn() ?: '')));
    }

    if (in_array('role', $columns, true)) {
        $stmt = $pdo->prepare('SELECT COALESCE(role, "") FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        return strtoupper(trim((string) ($stmt->fetchColumn() ?: '')));
    }

    return '';
}

$isCli = PHP_SAPI === 'cli';

try {
    $config = require __DIR__ . '/../config/config.php';
    require __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/mail.php';

    $pdo = db($config);

    if (!$isCli) {
        session_start();

        $authUserId = (int) ($_SESSION['auth_user_id'] ?? $_SESSION['user_id'] ?? 0);
        $authRole = resolve_auto_reject_role($pdo, $authUserId);
        $isDecisionRole = in_array($authRole, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD', 'CLUSTER_PROTECTION'], true);

        if ($authUserId <= 0 || !$isDecisionRole) {
            auto_reject_respond([
                'ok' => false,
                'success' => false,
                'message' => 'Acces non autorise.',
            ], 403);
        }
    }

    $reportColsStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
    $reportColsStmt->execute(['table_name' => 'reports']);
    $reportCols = array_map('strtolower', $reportColsStmt->fetchAll(PDO::FETCH_COLUMN));

    if (!in_array('review_deadline', $reportCols, true)) {
        auto_reject_respond([
            'ok' => true,
            'success' => true,
            'message' => 'Colonne review_deadline absente: aucune action executee.',
            'processed' => 0,
            'rejected' => 0,
            'emails_sent' => 0,
            'emails_failed' => 0,
        ]);
    }

    $historyColsStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
    $historyColsStmt->execute(['table_name' => 'report_status_history']);
    $historyCols = array_map('strtolower', $historyColsStmt->fetchAll(PDO::FETCH_COLUMN));

    $notifColsStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
    $notifColsStmt->execute(['table_name' => 'notifications']);
    $notifCols = array_map('strtolower', $notifColsStmt->fetchAll(PDO::FETCH_COLUMN));

    $statusIdExpr = in_array('status_id', $reportCols, true);
    $reporterCol = in_array('reporter_user_id', $reportCols, true) ? 'reporter_user_id' : (in_array('user_id', $reportCols, true) ? 'user_id' : null);
    if ($reporterCol === null) {
        throw new RuntimeException('Colonne reporter introuvable dans reports.');
    }

    $statusRejectedId = 4;
    if ($statusIdExpr) {
        try {
            $statusStmt = $pdo->prepare('SELECT id FROM report_statuses WHERE UPPER(code) = :code LIMIT 1');
            $statusStmt->execute(['code' => 'REJECTED']);
            $statusRejectedId = (int) ($statusStmt->fetchColumn() ?: 4);
        } catch (Throwable $e) {
            $statusRejectedId = 4;
        }
    }

    $sql = 'SELECT r.id,
                   r.workflow_status,
                   r.review_deadline,
                   r.' . $reporterCol . ' AS reporter_user_id,
                   COALESCE(u.email, "") AS reporter_email,
                   COALESCE(u.organization_name, u.full_name, "Organisation") AS reporter_name
            FROM reports r
            LEFT JOIN users u ON u.id = r.' . $reporterCol . '
            WHERE r.review_deadline IS NOT NULL
              AND r.review_deadline <= NOW()';

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processed = 0;
    $rejected = 0;
    $emailsSent = 0;
    $emailsFailed = 0;
    $errors = [];

    foreach ($rows as $row) {
        $processed++;

        $currentStatus = normalize_auto_status((string) ($row['workflow_status'] ?? ''));
        $inReviewStatuses = ['en revue', 'en revision', 'demande information', 'under review'];
        if (!in_array($currentStatus, $inReviewStatuses, true)) {
            continue;
        }

        $reportId = (int) ($row['id'] ?? 0);
        if ($reportId <= 0) {
            continue;
        }

        $historyComment = 'Rejet automatique : Délai de réponse expiré suite à une demande d\'informations.';

        try {
            $pdo->beginTransaction();

            $set = ['workflow_status = :workflow_status', 'review_deadline = NULL'];
            $params = [
                'workflow_status' => 'Rejeté',
                'id' => $reportId,
            ];

            if ($statusIdExpr) {
                $set[] = 'status_id = :status_id';
                $params['status_id'] = $statusRejectedId;
            }

            $updateSql = 'UPDATE reports SET ' . implode(', ', $set) . ' WHERE id = :id';
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($params);

            if ($historyCols !== []) {
                $historyData = [
                    'report_id' => $reportId,
                    'action' => 'REJECT',
                    'status_label' => 'Rejeté',
                    'comment' => $historyComment,
                    'event_note' => $historyComment,
                    'changed_by' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $insertCols = [];
                $insertVals = [];
                $insertBind = [];
                foreach ($historyData as $col => $value) {
                    if (in_array($col, $historyCols, true)) {
                        $insertCols[] = $col;
                        $insertVals[] = ':' . $col;
                        $insertBind[$col] = $value;
                    }
                }

                if ($insertCols !== []) {
                    $insertHistorySql = 'INSERT INTO report_status_history (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
                    $insertHistoryStmt = $pdo->prepare($insertHistorySql);
                    $insertHistoryStmt->execute($insertBind);
                }
            }

            if ($notifCols !== []) {
                $notificationData = [
                    'user_id' => (int) ($row['reporter_user_id'] ?? 0),
                    'report_id' => $reportId,
                    'status_code' => 'REJECTED',
                    'title' => 'Alerte rejetée automatiquement',
                    'message' => $historyComment,
                    'target_url' => 'index.php?page=rapportage-details&id=' . $reportId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_read' => 0,
                ];

                $cols = [];
                $vals = [];
                $bind = [];
                foreach ($notificationData as $col => $value) {
                    if (in_array($col, $notifCols, true)) {
                        $cols[] = $col;
                        $vals[] = ':' . $col;
                        $bind[$col] = $value;
                    }
                }

                if ($cols !== []) {
                    $notifSql = 'INSERT INTO notifications (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
                    $notifStmt = $pdo->prepare($notifSql);
                    try {
                        $notifStmt->execute($bind);
                    } catch (PDOException $e) {
                        if ((string) $e->getCode() !== '23000') {
                            throw $e;
                        }
                    }
                }
            }

            $pdo->commit();
            $rejected++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Report #' . $reportId . ': ' . $e->getMessage();
            continue;
        }

        $email = trim((string) ($row['reporter_email'] ?? ''));
        if ($email !== '') {
            $detailsUrl = rtrim((string) ($config['app_url'] ?? ''), '/') . '/index.php?page=rapportage-details&id=' . $reportId;
            $deadlineValue = (string) ($row['review_deadline'] ?? '');
            $deadlineHuman = $deadlineValue;
            if ($deadlineValue !== '') {
                try {
                    $deadlineHuman = (new DateTime($deadlineValue))->format('d/m/Y H:i');
                } catch (Throwable $e) {
                    $deadlineHuman = $deadlineValue;
                }
            }

            $mailPayload = [
                'nom' => (string) ($row['reporter_name'] ?? 'Organisation'),
                'details_url' => $detailsUrl,
                'review_deadline' => $deadlineValue,
                'review_deadline_human' => $deadlineHuman,
            ];

            $mailResult = envoyerNotificationEmail('alerte_rejet_automatique', $email, $mailPayload);
            if ((bool) ($mailResult['success'] ?? false)) {
                $emailsSent++;
            } else {
                $emailsFailed++;
                $errors[] = 'Mail report #' . $reportId . ': ' . (string) ($mailResult['error'] ?? 'Erreur SMTP inconnue');
            }
        }
    }

    auto_reject_respond([
        'ok' => true,
        'success' => true,
        'message' => 'Traitement auto-rejet termine.',
        'processed' => $processed,
        'rejected' => $rejected,
        'emails_sent' => $emailsSent,
        'emails_failed' => $emailsFailed,
        'errors' => $errors,
    ]);
} catch (Throwable $e) {
    auto_reject_respond([
        'ok' => false,
        'success' => false,
        'message' => 'Echec du traitement auto-rejet.',
        'error' => $e->getMessage(),
    ], 500);
}
