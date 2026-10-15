<?php
require_once __DIR__ . '/auth.php';

/**
 * Échappe une valeur pour affichage HTML.
 *
 * @param string $value
 * @return string
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Affiche l'en-tête HTML et le menu de navigation.
 *
 * @param string $title
 * @param string $activePage
 */
function renderHeader(string $title, string $activePage = ''): void
{
    $user = currentUser();
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo e($title); ?></title>
  <link rel="stylesheet" href="gsb.css" />
</head>
<body>
  <header class="app-header">
    <div class="container app-header-inner">
      <div class="app-brand">
        <img src="logo.svg" alt="GSB" width="48" height="48" />
        <div>
          <h1>GSB · Gestion des frais</h1>
          <p>Espace visiteurs médicaux</p>
        </div>
      </div>
      <?php if ($user): ?>
        <div class="user-menu">
          <div><strong><?php echo e($user['prenom'] . ' ' . $user['nom']); ?></strong></div>
          <a href="deconnexion.php">Déconnexion</a>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <nav class="container nav-main" aria-label="Navigation de l’espace visiteurs">
    <a href="tableau-bord.php" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>"<?php echo $activePage === 'dashboard' ? ' aria-current="page"' : ''; ?>>Tableau de bord</a>
    <a href="saisie-frais.php" class="<?php echo $activePage === 'saisie' ? 'active' : ''; ?>"<?php echo $activePage === 'saisie' ? ' aria-current="page"' : ''; ?>>Saisie de frais</a>
    <a href="consultation-fiches.php" class="<?php echo $activePage === 'consultation' ? 'active' : ''; ?>"<?php echo $activePage === 'consultation' ? ' aria-current="page"' : ''; ?>>Consultation</a>
    <?php if (isAdmin()): ?>
      <a href="admin-fiches.php" class="nav-admin <?php echo $activePage === 'admin' ? 'active' : ''; ?>"<?php echo $activePage === 'admin' ? ' aria-current="page"' : ''; ?>>Admin - Fiches</a>
    <?php endif; ?>
    <a href="connexion.php" class="<?php echo $activePage === 'login' ? 'active' : ''; ?>"<?php echo $activePage === 'login' ? ' aria-current="page"' : ''; ?>>Connexion</a>
  </nav>
  <main class="container app-main">
<?php }

/**
 * Affiche le pied de page et ferme le document HTML.
 */
function renderFooter(): void
{
    ?>
  </main>
  <footer>
    <div class="container footer-content">
      <div>
        <h4>Galaxy Swiss Bourdin</h4>
        <p>Siège : Philadelphie, États-Unis</p>
        <p>© <?php echo date('Y'); ?> GSB - Tous droits réservés</p>
      </div>
      <div>
        <h4>Liens utiles</h4>
        <ul>
          <li><a href="index.html">Accueil</a></li>
          <li><a href="mentions-legales.html">Mentions légales</a></li>
          <li><a href="contact.html">Contact</a></li>
        </ul>
      </div>
    </div>
  </footer>
</body>
</html>
<?php
}
