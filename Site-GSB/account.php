<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/password-console.php';

try {
    $action = $argv[1] ?? '';
    $id = $argv[2] ?? '';
    if (!in_array($action, ['reset', 'create'], true) || !preg_match('/^[a-zA-Z0-9]{1,4}$/D', $id)) {
        throw new RuntimeException('Usage : php account.php reset ID | php account.php create ID LOGIN NOM PRENOM');
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM Visiteur WHERE id = ?');
    $stmt->execute([$id]);
    $exists = (bool) $stmt->fetchColumn();
    if ($action === 'reset') {
        if (!$exists) throw new RuntimeException('Compte introuvable. Utiliser create pour un nouveau compte.');
        $hash = newLocalPasswordHash();
        $pdo->prepare('UPDATE Visiteur SET mdp = ? WHERE id = ?')->execute([$hash, $id]);
    } else {
        [$login, $nom, $prenom] = [$argv[3] ?? '', $argv[4] ?? '', $argv[5] ?? ''];
        if ($exists || !preg_match('/^[a-zA-Z0-9._-]{1,20}$/D', $login) || $nom === '' || $prenom === '' || mb_strlen($nom) > 30 || mb_strlen($prenom) > 30) {
            throw new RuntimeException('Compte existant ou informations invalides.');
        }
        $stmt = $pdo->prepare('SELECT id FROM Visiteur WHERE login = ?');
        $stmt->execute([$login]);
        if ($stmt->fetchColumn()) throw new RuntimeException('Identifiant déjà utilisé.');
        $hash = newLocalPasswordHash();
        $pdo->prepare('INSERT INTO Visiteur (id, login, nom, prenom, mdp) VALUES (?, ?, ?, ?, ?)')->execute([$id, $login, $nom, $prenom, $hash]);
    }
    echo "Compte enregistré. Aucun mot de passe affiché.\n";
} catch (Throwable $e) {
    // Ne jamais afficher une erreur SQL contenant une valeur sensible.
    fwrite(STDERR, ($e instanceof PDOException ? 'Erreur de base de données : vérifier la configuration et les doublons.' : $e->getMessage()) . PHP_EOL);
    exit(1);
}
