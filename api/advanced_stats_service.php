<?php

declare(strict_types=1);

function advanced_stats_get_columns(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute(['table' => $table]);
    return array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
}

function advanced_stats_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute(['table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function advanced_stats_resolve_role(PDO $pdo, int $userId): string
{
    $roleCode = strtoupper((string) ($_SESSION['role'] ?? $_SESSION['role_code'] ?? ''));
    if ($roleCode !== '') {
        return $roleCode;
    }

    $userCols = advanced_stats_get_columns($pdo, 'users');
    if (in_array('role', $userCols, true)) {
        $stmt = $pdo->prepare('SELECT UPPER(COALESCE(role, "")) FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $roleCode = strtoupper((string) $stmt->fetchColumn());
    }

    if ($roleCode === '' && in_array('role_id', $userCols, true)) {
        $stmt = $pdo->prepare('SELECT UPPER(COALESCE(r.code, ""))
                               FROM users u
                               LEFT JOIN roles r ON r.id = u.role_id
                               WHERE u.id = :id
                               LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $roleCode = strtoupper((string) $stmt->fetchColumn());
    }

    return $roleCode;
}

function advanced_stats_resolve_org_id(PDO $pdo, int $userId): int
{
    $sessionOrgId = (int) ($_SESSION['organization_id'] ?? $_SESSION['org_id'] ?? 0);
    if ($sessionOrgId > 0) {
        return $sessionOrgId;
    }

    $userCols = advanced_stats_get_columns($pdo, 'users');
    if (!in_array('organization_id', $userCols, true)) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT COALESCE(organization_id, 0) FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    return (int) $stmt->fetchColumn();
}

function advanced_stats_normalize_text(string $value): string
{
    $value = strtolower(trim($value));
    return str_replace(['é', 'è', 'ê'], 'e', $value);
}

function advanced_stats_context(PDO $pdo): array
{
    $reportCols = advanced_stats_get_columns($pdo, 'reports');
    $userCols = advanced_stats_get_columns($pdo, 'users');
    $orgCols = advanced_stats_table_exists($pdo, 'organizations') ? advanced_stats_get_columns($pdo, 'organizations') : [];

    $reporterFk = null;
    foreach (['user_id', 'author_id', 'created_by', 'reporter_id', 'reporter_user_id'] as $candidate) {
        if (in_array($candidate, $reportCols, true)) {
            $reporterFk = $candidate;
            break;
        }
    }

    if ($reporterFk === null) {
        throw new RuntimeException('Colonne de rattachement utilisateur introuvable dans reports.');
    }

    $hasStatusId = in_array('status_id', $reportCols, true);
    $hasWorkflowStatus = in_array('workflow_status', $reportCols, true);
    $hasReportOrgId = in_array('organization_id', $reportCols, true);
    $hasUserOrgId = in_array('organization_id', $userCols, true);
    $hasTerritory = in_array('territory', $reportCols, true);
    $hasUrgency = in_array('urgency_level', $reportCols, true);
    $hasVictims = in_array('victims_count', $reportCols, true);
    $hasDisplaced = in_array('displaced_households', $reportCols, true);
    $hasIncidentLabel = in_array('incident_label', $reportCols, true);
    $hasReportType = in_array('report_type', $reportCols, true);

    $joinUsers = 'LEFT JOIN users u ON u.id = r.' . $reporterFk;
    $joinStatus = $hasStatusId ? 'LEFT JOIN report_statuses rs ON rs.id = r.status_id' : '';

    $orgIdExpr = 'NULL';
    if ($hasReportOrgId) {
        $orgIdExpr = 'r.organization_id';
    } elseif ($hasUserOrgId) {
        $orgIdExpr = 'u.organization_id';
    }

    $joinOrganizations = '';
    if ($orgIdExpr !== 'NULL' && $orgCols !== [] && in_array('id', $orgCols, true)) {
        $joinOrganizations = 'LEFT JOIN organizations o ON o.id = ' . $orgIdExpr;
    }

    if ($joinOrganizations !== '' && in_array('name', $orgCols, true)) {
        $orgNameExpr = 'COALESCE(NULLIF(TRIM(o.name), ""), NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation inconnue")';
    } else {
        $orgNameExpr = 'COALESCE(NULLIF(TRIM(u.organization_name), ""), u.full_name, "Organisation inconnue")';
    }

    $statusExpr = $hasWorkflowStatus
        ? 'COALESCE(NULLIF(TRIM(r.workflow_status), ""), "Brouillon")'
        : ($hasStatusId
            ? 'COALESCE(NULLIF(TRIM(rs.label), ""), "Brouillon")'
            : '"Brouillon"');

    $statusNormExpr = 'LOWER(REPLACE(REPLACE(REPLACE(' . $statusExpr . ', "é", "e"), "è", "e"), "ê", "e"))';
    $urgencyExpr = $hasUrgency ? 'COALESCE(NULLIF(TRIM(r.urgency_level), ""), "Moyenne")' : '"Moyenne"';
    $urgencyNormExpr = 'LOWER(REPLACE(REPLACE(REPLACE(' . $urgencyExpr . ', "é", "e"), "è", "e"), "ê", "e"))';
    $territoryExpr = $hasTerritory ? 'COALESCE(NULLIF(TRIM(r.territory), ""), "Non précisé")' : '"Non précisé"';
    $incidentExpr = $hasIncidentLabel
        ? 'COALESCE(NULLIF(TRIM(r.incident_label), ""), "-")'
        : ($hasReportType ? 'COALESCE(NULLIF(TRIM(r.report_type), ""), "-")' : '"-"');

    return [
        'reporter_fk' => $reporterFk,
        'join_users' => $joinUsers,
        'join_status' => $joinStatus,
        'join_organizations' => $joinOrganizations,
        'org_id_expr' => $orgIdExpr,
        'org_name_expr' => $orgNameExpr,
        'status_expr' => $statusExpr,
        'status_norm_expr' => $statusNormExpr,
        'urgency_expr' => $urgencyExpr,
        'urgency_norm_expr' => $urgencyNormExpr,
        'territory_expr' => $territoryExpr,
        'incident_expr' => $incidentExpr,
        'has_territory' => $hasTerritory,
        'has_urgency' => $hasUrgency,
        'has_victims' => $hasVictims,
        'has_displaced' => $hasDisplaced,
        'has_report_type' => $hasReportType,
    ];
}

function advanced_stats_parse_filters(): array
{
    $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
    $dateTo = trim((string) ($_GET['date_to'] ?? ''));

    return [
        'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1 ? $dateFrom : null,
        'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1 ? $dateTo : null,
        'organization_id' => ctype_digit((string) ($_GET['organization_id'] ?? '')) ? (int) $_GET['organization_id'] : null,
        'territory' => trim((string) ($_GET['territory'] ?? '')),
        'severity' => advanced_stats_normalize_text((string) ($_GET['severity'] ?? '')),
    ];
}

function advanced_stats_build_where(array $context, array $filters, string $roleCode, int $userId, int $orgId): array
{
    $where = [];
    $params = [];

    $isLeadOrAdmin = in_array($roleCode, ['ADMIN', 'CLUSTER_LEADER', 'LEAD_GTMP', 'GTMP_LEAD', 'GTMP_COLEAD', 'CLUSTER_PROTECTION'], true);
    $isOrganisation = in_array($roleCode, ['ORGANISATION', 'ORGANIZATION'], true);

    if (!$isLeadOrAdmin) {
        if ($isOrganisation && $orgId > 0 && $context['org_id_expr'] !== 'NULL') {
            $where[] = $context['org_id_expr'] . ' = :session_org_id';
            $params['session_org_id'] = $orgId;
        } else {
            $where[] = 'r.' . $context['reporter_fk'] . ' = :session_user_id';
            $params['session_user_id'] = $userId;
        }
    } elseif (($filters['organization_id'] ?? null) !== null && $context['org_id_expr'] !== 'NULL') {
        $where[] = $context['org_id_expr'] . ' = :filter_org_id';
        $params['filter_org_id'] = (int) $filters['organization_id'];
    }

    if ($filters['date_from'] !== null) {
        $where[] = 'r.created_at >= :date_from';
        $params['date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if ($filters['date_to'] !== null) {
        $where[] = 'r.created_at <= :date_to';
        $params['date_to'] = $filters['date_to'] . ' 23:59:59';
    }

    if ($context['has_territory'] && $filters['territory'] !== '') {
        $where[] = 'LOWER(TRIM(r.territory)) = :territory';
        $params['territory'] = strtolower($filters['territory']);
    }

    if ($context['has_urgency'] && $filters['severity'] !== '') {
        $where[] = $context['urgency_norm_expr'] . ' LIKE :severity';
        $params['severity'] = '%' . $filters['severity'] . '%';
    }

    return [$where, $params];
}

function advanced_stats_where_sql(array $conditions): string
{
    if ($conditions === []) {
        return '';
    }
    return ' WHERE ' . implode(' AND ', $conditions);
}

function advanced_stats_fetch_payload(PDO $pdo, array $context, array $filters, string $roleCode, int $userId, int $orgId): array
{
    [$whereConditions, $params] = advanced_stats_build_where($context, $filters, $roleCode, $userId, $orgId);
    $whereSql = advanced_stats_where_sql($whereConditions);

    $fromSql = ' FROM reports r ' . $context['join_users'] . ' ' . $context['join_status'] . ' ' . $context['join_organizations'];

    $victimsExpr = $context['has_victims'] ? 'COALESCE(r.victims_count, 0)' : '0';
    $displacedExpr = $context['has_displaced'] ? 'COALESCE(r.displaced_households, 0)' : '0';

    $kpiStmt = $pdo->prepare('SELECT COUNT(*) AS total_incidents,
                                     SUM(' . $victimsExpr . ') AS total_victims,
                                     SUM(' . $displacedExpr . ') AS total_displaced
                              ' . $fromSql . $whereSql);
    $kpiStmt->execute($params);
    $kpiRow = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $mostAffectedTerritory = '-';
    if ($context['has_territory']) {
        $territoryStmt = $pdo->prepare('SELECT ' . $context['territory_expr'] . ' AS territory_name, COUNT(*) AS total
                                        ' . $fromSql . $whereSql . '
                                        GROUP BY territory_name
                                        ORDER BY total DESC
                                        LIMIT 1');
        $territoryStmt->execute($params);
        $territoryRow = $territoryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $mostAffectedTerritory = (string) ($territoryRow['territory_name'] ?? '-');
    }

    $useWeekly = false;
    if ($filters['date_from'] !== null && $filters['date_to'] !== null) {
        $days = (int) ((strtotime($filters['date_to']) - strtotime($filters['date_from'])) / 86400);
        $useWeekly = $days > 62;
    }

    if ($useWeekly) {
        $evolutionSql = 'SELECT DATE_FORMAT(r.created_at, "%x-W%v") AS bucket,
                                MIN(DATE(r.created_at)) AS sort_key,
                                COUNT(*) AS total
                         ' . $fromSql . $whereSql . '
                         GROUP BY bucket
                         ORDER BY sort_key ASC';
    } else {
        $evolutionSql = 'SELECT DATE(r.created_at) AS bucket,
                                DATE(r.created_at) AS sort_key,
                                COUNT(*) AS total
                         ' . $fromSql . $whereSql . '
                         GROUP BY bucket
                         ORDER BY sort_key ASC';
    }
    $evolutionStmt = $pdo->prepare($evolutionSql);
    $evolutionStmt->execute($params);
    $evolutionRows = $evolutionStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $evolutionLabels = [];
    $evolutionValues = [];
    foreach ($evolutionRows as $row) {
        $evolutionLabels[] = (string) ($row['bucket'] ?? '');
        $evolutionValues[] = (int) ($row['total'] ?? 0);
    }

    $severityBuckets = [
        'Critique' => 0,
        'Elevee' => 0,
        'Moyenne' => 0,
        'Faible' => 0,
    ];
    $severityStmt = $pdo->prepare('SELECT ' . $context['urgency_expr'] . ' AS urgency_level, COUNT(*) AS total
                                   ' . $fromSql . $whereSql . '
                                   GROUP BY urgency_level');
    $severityStmt->execute($params);
    $severityRows = $severityStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($severityRows as $row) {
        $raw = advanced_stats_normalize_text((string) ($row['urgency_level'] ?? 'moyenne'));
        $total = (int) ($row['total'] ?? 0);
        if (strpos($raw, 'crit') !== false) {
            $severityBuckets['Critique'] += $total;
        } elseif (strpos($raw, 'ele') !== false || strpos($raw, 'haut') !== false) {
            $severityBuckets['Elevee'] += $total;
        } elseif (strpos($raw, 'faib') !== false || strpos($raw, 'bas') !== false) {
            $severityBuckets['Faible'] += $total;
        } else {
            $severityBuckets['Moyenne'] += $total;
        }
    }

    $topOrgLabels = [];
    $topOrgValues = [];
    if ($context['org_name_expr'] !== '') {
        $topOrgStatuses = '"soumis","submitted","en revue","en revision","under_review","approuve","approuvé","approved","valide","validee","publie","published"';
        $extraWhere = $whereConditions;
        $extraWhere[] = $context['status_norm_expr'] . ' IN (' . $topOrgStatuses . ')';
        $topOrgSql = 'SELECT ' . $context['org_name_expr'] . ' AS organization_name,
                             COUNT(*) AS total
                      ' . $fromSql . advanced_stats_where_sql($extraWhere) . '
                      GROUP BY ' . $context['org_name_expr'] . '
                      ORDER BY total DESC
                      LIMIT 5';
        $topOrgStmt = $pdo->prepare($topOrgSql);
        $topOrgStmt->execute($params);
        foreach (($topOrgStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $topOrgLabels[] = (string) ($row['organization_name'] ?? 'Organisation');
            $topOrgValues[] = (int) ($row['total'] ?? 0);
        }
    }

    $impactLabels = [];
    $impactVictims = [];
    $impactDisplaced = [];
    if ($context['has_territory']) {
        $impactStmt = $pdo->prepare('SELECT ' . $context['territory_expr'] . ' AS territory_name,
                                            SUM(' . $victimsExpr . ') AS victims_total,
                                            SUM(' . $displacedExpr . ') AS displaced_total,
                                            COUNT(*) AS reports_total
                                     ' . $fromSql . $whereSql . '
                                     GROUP BY territory_name
                                     ORDER BY reports_total DESC
                                     LIMIT 10');
        $impactStmt->execute($params);
        foreach (($impactStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $impactLabels[] = (string) ($row['territory_name'] ?? 'Non précisé');
            $impactVictims[] = (int) ($row['victims_total'] ?? 0);
            $impactDisplaced[] = (int) ($row['displaced_total'] ?? 0);
        }
    }

    [$rbacConditions, $rbacParams] = advanced_stats_build_where(
        $context,
        ['date_from' => null, 'date_to' => null, 'organization_id' => null, 'territory' => '', 'severity' => ''],
        $roleCode,
        $userId,
        $orgId
    );

    $organizationOptions = [];
    if ($context['org_id_expr'] !== 'NULL') {
         $orgStmt = $pdo->prepare('SELECT ' . $context['org_id_expr'] . ' AS organization_id,
                              MAX(' . $context['org_name_expr'] . ') AS organization_name,
                                         COUNT(*) AS total
                                  ' . $fromSql . advanced_stats_where_sql($rbacConditions) . '
                          GROUP BY ' . $context['org_id_expr'] . '
                                  ORDER BY organization_name ASC');
        $orgStmt->execute($rbacParams);
        foreach (($orgStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $oid = (int) ($row['organization_id'] ?? 0);
            if ($oid <= 0) {
                continue;
            }
            $organizationOptions[] = [
                'id' => $oid,
                'name' => (string) ($row['organization_name'] ?? 'Organisation'),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }
    }

    $territoryOptions = [];
    if ($context['has_territory']) {
        $territoryStmt = $pdo->prepare('SELECT ' . $context['territory_expr'] . ' AS territory_name,
                                               COUNT(*) AS total
                                        ' . $fromSql . advanced_stats_where_sql($rbacConditions) . '
                                        GROUP BY territory_name
                                        ORDER BY territory_name ASC');
        $territoryStmt->execute($rbacParams);
        foreach (($territoryStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $name = trim((string) ($row['territory_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $territoryOptions[] = [
                'name' => $name,
                'total' => (int) ($row['total'] ?? 0),
            ];
        }
    }

    return [
        'meta' => [
            'role' => $roleCode,
            'bucket' => $useWeekly ? 'week' : 'day',
        ],
        'filters' => [
            'organizations' => $organizationOptions,
            'territories' => $territoryOptions,
            'applied' => [
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'organization_id' => $filters['organization_id'],
                'territory' => $filters['territory'],
                'severity' => $filters['severity'],
            ],
        ],
        'kpis' => [
            'total_incidents' => (int) ($kpiRow['total_incidents'] ?? 0),
            'total_victims' => (int) ($kpiRow['total_victims'] ?? 0),
            'total_displaced_households' => (int) ($kpiRow['total_displaced'] ?? 0),
            'most_affected_territory' => $mostAffectedTerritory,
        ],
        'charts' => [
            'evolution' => [
                'labels' => $evolutionLabels,
                'values' => $evolutionValues,
            ],
            'severity' => [
                'labels' => array_keys($severityBuckets),
                'values' => array_values($severityBuckets),
            ],
            'top_organizations' => [
                'labels' => $topOrgLabels,
                'values' => $topOrgValues,
            ],
            'territory_impact' => [
                'labels' => $impactLabels,
                'victims' => $impactVictims,
                'displaced_households' => $impactDisplaced,
            ],
        ],
    ];
}

function advanced_stats_fetch_raw_rows(PDO $pdo, array $context, array $filters, string $roleCode, int $userId, int $orgId): array
{
    [$whereConditions, $params] = advanced_stats_build_where($context, $filters, $roleCode, $userId, $orgId);
    $whereSql = advanced_stats_where_sql($whereConditions);

    $fromSql = ' FROM reports r ' . $context['join_users'] . ' ' . $context['join_status'] . ' ' . $context['join_organizations'];
    $victimsExpr = $context['has_victims'] ? 'COALESCE(r.victims_count, 0)' : '0';
    $displacedExpr = $context['has_displaced'] ? 'COALESCE(r.displaced_households, 0)' : '0';

    $sql = 'SELECT r.id,
                   r.created_at,
                   ' . $context['org_name_expr'] . ' AS organization_name,
                   ' . $context['territory_expr'] . ' AS territory,
                   ' . $context['incident_expr'] . ' AS incident_type,
                   ' . $context['urgency_expr'] . ' AS severity,
                   ' . $context['status_expr'] . ' AS workflow_status,
                   ' . $victimsExpr . ' AS victims_count,
                   ' . $displacedExpr . ' AS displaced_households
            ' . $fromSql . $whereSql . '
            ORDER BY r.created_at DESC
            LIMIT 10000';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
