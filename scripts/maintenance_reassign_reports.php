<?php
declare(strict_types=1);

// Script ponctuel: réaffecte les rapports d'un compte lead/admin vers un compte rapporteur.

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

$pdo = db($config);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userColsStmt = $pdo->query('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users"');
$userCols = array_map('strtolower', $userColsStmt->fetchAll(PDO::FETCH_COLUMN));
$hasRoleCol = in_array('role', $userCols, true);
$hasRoleIdCol = in_array('role_id', $userCols, true);
$hasIsActiveCol = in_array('is_active', $userCols, true);

echo "=== Maintenance: Reassign reports from IT@FOSIP-DRC ===\n";

$targetEmail = 'it@fosip-drc';

$sourceStmt = $pdo->prepare('SELECT id, full_name, email, organization_name, organization_id, role, role_id FROM users WHERE LOWER(TRIM(email)) = :email LIMIT 1');
$sourceStmt->execute(['email' => $targetEmail]);
$sourceUser = $sourceStmt->fetch(PDO::FETCH_ASSOC);

if (!is_array($sourceUser)) {
    echo "Source user not found for email: {$targetEmail}\n";
    exit(0);
}

$sourceUserId = (int) $sourceUser['id'];
echo "Source user ID: {$sourceUserId} ({$sourceUser['email']})\n";

$sourceOrgId = null;
if (array_key_exists('organization_id', $sourceUser)) {
    $sourceOrgId = (int) ($sourceUser['organization_id'] ?? 0);
}

$roleUserExpr = $hasRoleCol ? 'UPPER(COALESCE(u.role, ""))' : '""';
$joinRolesSql = $hasRoleIdCol ? 'LEFT JOIN roles r ON r.id = u.role_id' : '';
$roleCodeExpr = $hasRoleIdCol ? 'UPPER(COALESCE(r.code, ""))' : '""';
$activeCondition = $hasIsActiveCol ? 'u.is_active = 1' : '1=1';

$destStmt = $pdo->prepare(
    'SELECT u.id, u.email, COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation") AS org_name
    FROM users u
    ' . $joinRolesSql . '
    WHERE u.id <> :source_id
             AND ' . $activeCondition . '
      AND (
          ' . $roleCodeExpr . ' IN ("ORG_REPORTER", "REPORTER")
          OR ' . $roleUserExpr . ' IN ("ORG_REPORTER", "REPORTER")
      )
    ORDER BY u.id ASC
    LIMIT 1'
);
$destStmt->execute(['source_id' => $sourceUserId]);
$destUser = $destStmt->fetch(PDO::FETCH_ASSOC);

if (!is_array($destUser)) {
    $destFallbackStmt = $pdo->prepare(
        'SELECT u.id, u.email, COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation") AS org_name
         FROM users u
         WHERE u.id <> :source_id
                     AND ' . $activeCondition . '
           AND COALESCE(NULLIF(TRIM(u.organization_name), ""), "") <> ""
                 ORDER BY u.id ASC
         LIMIT 1'
    );
    $destFallbackStmt->execute(['source_id' => $sourceUserId]);
    $destUser = $destFallbackStmt->fetch(PDO::FETCH_ASSOC);
}

if (!is_array($destUser)) {
    echo "No destination reporter user found (including fallback by organization_name).\n";
    exit(1);
}

$destUserId = (int) $destUser['id'];
echo "Destination user ID: {$destUserId} ({$destUser['email']})\n";
echo "Destination org name: " . (string) ($destUser['org_name'] ?? 'Organisation') . "\n";

$reportColsStmt = $pdo->query('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "reports"');
$reportCols = array_map('strtolower', $reportColsStmt->fetchAll(PDO::FETCH_COLUMN));

$hasReporterUserId = in_array('reporter_user_id', $reportCols, true);
$hasUserId = in_array('user_id', $reportCols, true);
$hasOrgId = in_array('organization_id', $reportCols, true);

$ownerCol = $hasReporterUserId ? 'reporter_user_id' : ($hasUserId ? 'user_id' : null);
if ($ownerCol === null) {
    echo "No owner column found in reports table (reporter_user_id/user_id).\n";
    exit(1);
}

$destOrgId = 0;
if (in_array('organization_id', $userCols, true)) {
    $destOrgStmt = $pdo->prepare('SELECT organization_id FROM users WHERE id = :id LIMIT 1');
    $destOrgStmt->execute(['id' => $destUserId]);
    $destOrgId = (int) ($destOrgStmt->fetchColumn() ?: 0);
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE ' . $ownerCol . ' = :source_id');
$countStmt->execute(['source_id' => $sourceUserId]);
$toMove = (int) $countStmt->fetchColumn();
echo "Reports to reassign: {$toMove}\n";

if ($toMove > 0) {
    $pdo->beginTransaction();
    try {
        $set = [$ownerCol . ' = :dest_id'];
        $params = ['dest_id' => $destUserId, 'source_id' => $sourceUserId];

        if ($hasOrgId && $destOrgId > 0) {
            $set[] = 'organization_id = :dest_org_id';
            $params['dest_org_id'] = $destOrgId;
        }

        $moveSql = 'UPDATE reports SET ' . implode(', ', $set) . ' WHERE ' . $ownerCol . ' = :source_id';
        $moveStmt = $pdo->prepare($moveSql);
        $moveStmt->execute($params);
        $moved = $moveStmt->rowCount();

        $pdo->commit();
        echo "Reports reassigned: {$moved}\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$renameStmt = $pdo->prepare('UPDATE users SET organization_name = :org_name WHERE LOWER(TRIM(COALESCE(full_name, ""))) = :person_name OR LOWER(TRIM(COALESCE(organization_name, ""))) = :person_name');
$renameStmt->execute([
    'org_name' => 'Organisation Partenaire Murhula',
    'person_name' => 'jean-baptiste murhula',
]);
echo "Renamed organization_name rows: " . $renameStmt->rowCount() . "\n";

$verifyMovedStmt = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE ' . $ownerCol . ' = :source_id');
$verifyMovedStmt->execute(['source_id' => $sourceUserId]);
$remaining = (int) $verifyMovedStmt->fetchColumn();
echo "Remaining reports still assigned to source: {$remaining}\n";

$verifyNameStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(TRIM(COALESCE(full_name, ""))) = :person_name OR LOWER(TRIM(COALESCE(organization_name, ""))) = :person_name');
$verifyNameStmt->execute(['person_name' => 'jean-baptiste murhula']);
$remainingName = (int) $verifyNameStmt->fetchColumn();
echo "Remaining rows with person label Jean-Baptiste Murhula: {$remainingName}\n";

echo "Done.\n";
