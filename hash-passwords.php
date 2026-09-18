<?php
require_once __DIR__ . '/inc/db.php';

if (PHP_SAPI !== 'cli') {
    echo 'Ce script doit être exécuté en ligne de commande.' . PHP_EOL;
    exit(1);
}

$pdo = getPDO();
$stmt = $pdo->query('SELECT id, mdp FROM Visiteur');
$users = $stmt->fetchAll();

$updated = 0;
$skipped = 0;

foreach ($users as $user) {
    $info = password_get_info($user['mdp']);
    if ($info['algo'] !== 0) {
        $skipped++;
        continue;
    }

    $newHash = password_hash($user['mdp'], PASSWORD_DEFAULT);
    if ($newHash === false) {
        echo "Échec du hash pour l'utilisateur {$user['id']}" . PHP_EOL;
        continue;
    }

    $update = $pdo->prepare('UPDATE Visiteur SET mdp = :mdp WHERE id = :id');
    $update->execute(['mdp' => $newHash, 'id' => $user['id']]);
    $updated++;
}

echo sprintf("Total utilisateurs traités : %d\n", count($users));
echo sprintf("Mots de passe hashés : %d\n", $updated);
echo sprintf("Mots de passe déjà sécurisés : %d\n", $skipped);

echo 'Terminé.' . PHP_EOL;
