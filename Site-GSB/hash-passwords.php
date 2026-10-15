<?php
// Les anciens secrets courts/publics sont invalidés, pas simplement hachés.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/inc/db.php';
$pdo = getPDO();
$pdo->beginTransaction();
try {
    $updated = 0;
    foreach ($pdo->query('SELECT id, mdp FROM Visiteur')->fetchAll() as $user) {
        if (password_get_info($user['mdp'])['algoName'] !== 'unknown') continue;
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE Visiteur SET mdp = ? WHERE id = ?')->execute([$hash, $user['id']]);
        $updated++;
    }
    $pdo->commit();
    echo "$updated anciens secrets invalidés. Définir les nouveaux mots de passe avec php account.php reset ID.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Migration annulée. Vérifier localement la configuration de la base.\n");
    exit(1);
}