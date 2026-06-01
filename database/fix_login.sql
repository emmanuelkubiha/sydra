USE sydra;

UPDATE utilisateurs
SET mot_de_passe = '$2y$10$L/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi'
WHERE email IN (
	'reporter@sydra.local',
	'admin@sydra.local',
	'lead.gtmp@sydra.local',
	'colead.gtmp@sydra.local',
	'cluster@sydra.local'
);

-- Compatibilite si l'ancien schema est encore en place
UPDATE users
SET password_hash = '$2y$10$L/APfRt7.hJsd2cdTyYqsuYS3aFdmd96voLq1zRnVM3KbyvsyyiRi'
WHERE email IN (
	'reporter@sydra.local',
	'admin@sydra.local',
	'lead.gtmp@sydra.local',
	'colead.gtmp@sydra.local',
	'cluster@sydra.local'
);
