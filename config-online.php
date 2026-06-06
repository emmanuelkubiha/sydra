<?php

declare(strict_types=1);

/**
 * Point d'entree de configuration production.
 *
 * Usage:
 * - Option 1: dans index.php, remplacer require config/config.php par ce fichier
 * - Option 2: inclure ce fichier uniquement sur l'environnement en ligne
 *
 * Ce fichier delegue vers config/config-online.php pour centraliser la logique.
 */

return require __DIR__ . '/config/config-online.php';
