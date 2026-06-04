-- SyDRA - Requetes SQL de statistiques (Dashboard / Stats GTMP)

-- 1) Evolution mensuelle des rapports d'une organisation sur les 6 derniers mois.
-- Remplacer :user_id par l'ID de l'organisation connectee.
SELECT DATE_FORMAT(r.created_at, '%Y-%m') AS mois,
       r.report_type,
       COUNT(*) AS total
FROM reports r
WHERE r.user_id = :user_id
  AND r.created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
GROUP BY mois, r.report_type
ORDER BY mois ASC;

-- 2) Organisations rapportantes les plus actives.
SELECT COALESCE(NULLIF(TRIM(u.organization_name), ''), u.full_name, 'Organisation inconnue') AS organisation,
       COUNT(*) AS total_rapports
FROM reports r
LEFT JOIN users u ON u.id = r.user_id
GROUP BY organisation
ORDER BY total_rapports DESC
LIMIT 8;

-- 3) Evolution globale mensuelle (FLASH + NOTE) sur 6 mois.
SELECT DATE_FORMAT(r.created_at, '%Y-%m') AS mois,
       r.report_type,
       COUNT(*) AS total
FROM reports r
WHERE r.created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
GROUP BY mois, r.report_type
ORDER BY mois ASC;

-- 4) Repartition globale des alertes par niveau d'urgence.
SELECT r.urgency_level,
       COUNT(*) AS total
FROM reports r
GROUP BY r.urgency_level
ORDER BY total DESC;
