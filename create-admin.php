<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';

if (PHP_SAPI !== 'cli') {
    if (ADMIN_CREATION_TOKEN === '') {
        http_response_code(403);
        echo 'Accès interdit : création d\'admin réservée en ligne de commande.';
        exit;
    }

    if (!isset($_GET['token']) || $_GET['token'] !== ADMIN_CREATION_TOKEN) {
        http_response_code(403);
        echo 'Accès refusé.';
        exit;
    }
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT 1 FROM Visiteur WHERE id = :id');
$stmt->execute(['id' => 'a00']);
$exists = $stmt->fetchColumn();

if ($exists) {
    $message = '✓ Admin existe déjà !';
    $success = true;
} else {
    $hash = password_hash('admin1234', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO Visiteur (id, nom, prenom, login, mdp, adresse, cp, ville, dateEmbauche)
         VALUES (:id, :nom, :prenom, :login, :mdp, :adresse, :cp, :ville, :dateEmbauche)'
    );
    $success = $stmt->execute([
        'id' => 'a00',
        'nom' => 'Admin',
        'prenom' => 'Système',
        'login' => 'admin',
        'mdp' => $hash,
        'adresse' => '123 rue Admin',
        'cp' => '75000',
        'ville' => 'Paris',
        'dateEmbauche' => date('Y-m-d'),
    ]);

    if ($success) {
        $message = '✓ Admin créé avec succès ! Login: admin, mot de passe: admin1234';
    } else {
        $message = '✗ Erreur lors de la création !';
    }
}

if (PHP_SAPI !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Créer Admin</title></head>
    <body>
    <h2>Création du compte admin</h2>
    <?php if ($success): ?>
        <p style="color:green;"><strong><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <?php else: ?>
        <p style="color:red;"><strong><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <?php endif; ?>
    <p><a href="connexion.php">Aller à la connexion</a></p>
    </body>
    </html>
    <?php
} else {
    echo $message . PHP_EOL;
}
