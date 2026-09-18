<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/template.php';

requireLogin();
requireAdmin();

$pdo = getPDO();
$stmt = $pdo->query('SELECT id, login, nom, prenom FROM Visiteur ORDER BY id');
$users = $stmt->fetchAll();

renderHeader('Debug - Visiteurs', 'admin');
?>
<h2>Visiteurs en base de données :</h2>
<ul>
<?php foreach ($users as $user): ?>
  <li><strong><?php echo htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8'); ?></strong> (<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>) - <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom'], ENT_QUOTES, 'UTF-8'); ?></li>
<?php endforeach; ?>
</ul>
<p><a href="connexion.php">Retour à la connexion</a></p>
<?php renderFooter();
