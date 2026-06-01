<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Report
{
    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $reference = 'SYDRA-' . date('YmdHis') . '-' . random_int(100, 999);

        $sql = 'INSERT INTO reports (
                    reference_code, organization_id, reporter_user_id, report_type, status_id,
                    incident_type_id, severity_id, urgency_id,
                    province, territory, health_zone, groupement, village, locality, place_search_text,
                    latitude, longitude, households_count, people_count,
                    vulnerable_children_count, vulnerable_elderly_count, vulnerable_women_count,
                    vulnerable_men_count, vulnerable_disability_count, vulnerable_other_count,
                    vulnerable_categories,
                    context_text, facts_text, analysis_text, impacts_text, needs_text, recommendations_text,
                    submitted_at
                ) VALUES (
                    :reference_code, :organization_id, :reporter_user_id, :report_type,
                    (SELECT id FROM report_statuses WHERE code = :status_code LIMIT 1),
                    :incident_type_id, :severity_id, :urgency_id,
                    :province, :territory, :health_zone, :groupement, :village, :locality, :place_search_text,
                    :latitude, :longitude, :households_count, :people_count,
                    :vulnerable_children_count, :vulnerable_elderly_count, :vulnerable_women_count,
                    :vulnerable_men_count, :vulnerable_disability_count, :vulnerable_other_count,
                    :vulnerable_categories,
                    :context_text, :facts_text, :analysis_text, :impacts_text, :needs_text, :recommendations_text,
                    :submitted_at
                )';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'reference_code' => $reference,
            'organization_id' => $data['organization_id'],
            'reporter_user_id' => $data['reporter_user_id'],
            'report_type' => $data['report_type'],
            'status_code' => $data['is_submit'] ? 'SUBMITTED' : 'DRAFT',
            'incident_type_id' => $data['incident_type_id'] ?: null,
            'severity_id' => $data['severity_id'] ?: null,
            'urgency_id' => $data['urgency_id'] ?: null,
            'province' => $data['province'] ?: null,
            'territory' => $data['territory'] ?: null,
            'health_zone' => $data['health_zone'] ?: null,
            'groupement' => $data['groupement'] ?: null,
            'village' => $data['village'] ?: null,
            'locality' => $data['locality'] ?: null,
            'place_search_text' => $data['place_search_text'] ?: null,
            'latitude' => $data['latitude'] ?: null,
            'longitude' => $data['longitude'] ?: null,
            'households_count' => $data['households_count'] ?: null,
            'people_count' => $data['people_count'] ?: null,
            'vulnerable_children_count' => $data['vulnerable_children_count'] ?: null,
            'vulnerable_elderly_count' => $data['vulnerable_elderly_count'] ?: null,
            'vulnerable_women_count' => $data['vulnerable_women_count'] ?: null,
            'vulnerable_men_count' => $data['vulnerable_men_count'] ?: null,
            'vulnerable_disability_count' => $data['vulnerable_disability_count'] ?: null,
            'vulnerable_other_count' => $data['vulnerable_other_count'] ?: null,
            'vulnerable_categories' => $data['vulnerable_categories'] ?: null,
            'context_text' => $data['context_text'] ?: null,
            'facts_text' => $data['facts_text'] ?: null,
            'analysis_text' => $data['analysis_text'] ?: null,
            'impacts_text' => $data['impacts_text'] ?: null,
            'needs_text' => $data['needs_text'] ?: null,
            'recommendations_text' => $data['recommendations_text'] ?: null,
            'submitted_at' => $data['is_submit'] ? date('Y-m-d H:i:s') : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function latest(int $limit = 20): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT r.id, r.reference_code, r.report_type, rs.label AS status_label,
                                      r.province, r.territory, r.locality, r.created_at,
                                      o.name AS organization_name
                               FROM reports r
                               INNER JOIN report_statuses rs ON rs.id = r.status_id
                               INNER JOIN organizations o ON o.id = r.organization_id
                               ORDER BY r.created_at DESC
                               LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function dashboardStats(): array
    {
        $pdo = Database::connection();
        $sql = 'SELECT
                    COUNT(*) AS total_reports,
                    SUM(CASE WHEN report_type = "FLASH" THEN 1 ELSE 0 END) AS total_flash,
                    SUM(CASE WHEN report_type = "NOTE" THEN 1 ELSE 0 END) AS total_note,
                    SUM(CASE WHEN rs.code = "SUBMITTED" THEN 1 ELSE 0 END) AS pending_review,
                    SUM(CASE WHEN rs.code IN ("VALIDATED", "PUBLISHED") THEN 1 ELSE 0 END) AS approved,
                    SUM(COALESCE(vulnerable_children_count, 0)) AS total_vulnerable_children,
                    SUM(COALESCE(vulnerable_elderly_count, 0)) AS total_vulnerable_elderly,
                    SUM(COALESCE(vulnerable_disability_count, 0)) AS total_vulnerable_disability
                FROM reports r
                INNER JOIN report_statuses rs ON rs.id = r.status_id';

        $row = $pdo->query($sql)->fetch();

        return $row ?: [
            'total_reports' => 0,
            'total_flash' => 0,
            'total_note' => 0,
            'pending_review' => 0,
            'approved' => 0,
            'total_vulnerable_children' => 0,
            'total_vulnerable_elderly' => 0,
            'total_vulnerable_disability' => 0,
        ];
    }

    public static function referenceData(string $table): array
    {
        $allowed = ['incident_types', 'severity_levels', 'urgencies'];
        if (!in_array($table, $allowed, true)) {
            return [];
        }

        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT id, code, label FROM ' . $table . ' ORDER BY label ASC');

        return $stmt->fetchAll();
    }
}
