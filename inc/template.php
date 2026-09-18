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
  <style>
    html { height: 100%; }
    body { display: flex; flex-direction: column; min-height: 100vh; margin: 0; }
    main { flex: 1; }
    footer { margin-top: auto; }
    .nav-main { display:flex; gap:1rem; padding:1rem; background:#003366; color:#fff; }
    .nav-main a { color:#fff; text-decoration:none; padding:0.5rem 0.75rem; border-radius:4px; }
    .nav-main a.active, .nav-main a:hover { background:rgba(255,255,255,0.15); }
    .container { max-width:1100px; margin:0 auto; padding:1rem; }
    .message { padding:0.75rem 1rem; border-radius:6px; margin-bottom:1rem; }
    .message.error { background:#ffefef; border:1px solid #f1c0c0; color:#8a1f1f; }
    .message.success { background:#eef9ef; border:1px solid #b3d7b3; color:#1f5d1f; }
    .form-group { margin-bottom:1rem; }
    .form-group label { display:block; margin-bottom:0.25rem; font-weight:600; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.6rem; border:1px solid #ccc; border-radius:6px; }
    .btn { display:inline-flex; align-items:center; justify-content:center; padding:0.6rem 1rem; border:none; border-radius:6px; cursor:pointer; background:#ff7a00; color:#fff; font-weight:600; }
    .btn.secondary { background:#444; }
    .btn.danger { background:#c00; }
    .table { width:100%; border-collapse:collapse; margin-top:1rem; }
    .table th, .table td { border:1px solid rgba(0,0,0,0.1); padding:0.65rem; text-align:left; }
    .table th { background:rgba(0,0,0,0.05); }
  </style>
</head>
<body>
  <header style="background: linear-gradient(135deg, #003366 0%, #004d99 100%); padding:1rem; color:#fff;">
    <div class="container" style="display:flex; align-items:center; justify-content:space-between; gap:1.5rem;">
      <div style="display:flex; align-items:center; gap:1rem;">
        <!-- Logo temporaire - remplacer logo.svg ou logo.png par votre fichier -->
        <img src="logo.svg" alt="Logo GSB" style="height:50px; width:auto;" onerror="this.style.display='none'" />
        <div>
          <h1 style="margin:0; font-size:1.4rem;">GSB - Gestion des frais</h1>
          <div style="font-size:0.9rem; opacity:0.85;">Espace visiteurs médicaux</div>
        </div>
      </div>
      <?php if ($user): ?>
        <div style="text-align:right; font-size:0.9rem;">
          <div><strong><?php echo e($user['prenom'] . ' ' . $user['nom']); ?></strong></div>
          <div><a href="deconnexion.php" style="color:rgba(255,255,255,0.85); text-decoration:underline;">Déconnexion</a></div>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <nav class="nav-main">
    <a href="tableau-bord.php" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Tableau de bord</a>
    <a href="saisie-frais.php" class="<?php echo $activePage === 'saisie' ? 'active' : ''; ?>">Saisie de frais</a>
    <a href="consultation-fiches.php" class="<?php echo $activePage === 'consultation' ? 'active' : ''; ?>">Consultation</a>
    <?php if (isAdmin()): ?>
      <a href="admin-fiches.php" class="<?php echo $activePage === 'admin' ? 'active' : ''; ?>" style="background:#c00; margin-left:auto;">Admin - Fiches</a>
    <?php endif; ?>
    <a href="connexion.php" class="<?php echo $activePage === 'login' ? 'active' : ''; ?>">Connexion</a>
  </nav>
  <main class="container" style="flex: 1;">
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
