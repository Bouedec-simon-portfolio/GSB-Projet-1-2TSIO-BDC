<?php
// Ancienne commande : réinitialisation locale de a00, sans secret par défaut.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
$argv = [__FILE__, 'reset', 'a00'];
require __DIR__ . '/account.php';