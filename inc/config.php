<?php

/**
 * Configuration générale de l'application.
 *
 * Les valeurs propres à chaque poste sont placées dans config.local.php,
 * qui est ignoré par Git. Les variables d'environnement sont prioritaires.
 */
$localConfigFile = __DIR__ . '/config.local.php';
$localConfig = file_exists($localConfigFile) ? require $localConfigFile : [];

if (!is_array($localConfig)) {
    throw new RuntimeException('inc/config.local.php doit retourner un tableau.');
}

$envOrLocal = static function (string $envName, string $localKey, $default) use ($localConfig) {
    $environmentValue = getenv($envName);

    if ($environmentValue !== false && $environmentValue !== '') {
        return $environmentValue;
    }

    return $localConfig[$localKey] ?? $default;
};

define('DB_HOST', (string) $envOrLocal('GSB_DB_HOST', 'db_host', '127.0.0.1'));
define('DB_NAME', (string) $envOrLocal('GSB_DB_NAME', 'db_name', 'gsbV2'));
define('DB_USER', (string) $envOrLocal('GSB_DB_USER', 'db_user', 'slam'));
define('DB_PASS', (string) $envOrLocal('GSB_DB_PASS', 'db_pass', 'password'));
define('DB_CHARSET', 'utf8mb4');

define('SESSION_NAME', 'gsb_session');

$adminIds = $envOrLocal('GSB_ADMIN_IDS', 'admin_ids', ['a00']);
if (is_string($adminIds)) {
    $adminIds = array_values(array_filter(array_map('trim', explode(',', $adminIds))));
}
define('ADMIN_IDS', is_array($adminIds) ? $adminIds : ['a00']);

define(
    'ADMIN_CREATION_TOKEN',
    (string) $envOrLocal('GSB_ADMIN_CREATION_TOKEN', 'admin_creation_token', '')
);

$appEnvironment = (string) $envOrLocal('GSB_APP_ENV', 'app_env', 'development');
define('DISPLAY_ERRORS', $appEnvironment !== 'production');

ini_set('display_errors', DISPLAY_ERRORS ? '1' : '0');
ini_set('display_startup_errors', DISPLAY_ERRORS ? '1' : '0');
error_reporting(E_ALL);
