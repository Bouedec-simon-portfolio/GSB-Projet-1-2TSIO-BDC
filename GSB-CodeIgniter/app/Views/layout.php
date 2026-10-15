<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>GSB · Gestion des frais</title>
  <link rel="stylesheet" href="<?= base_url('assets/gsb.css') ?>">
</head>
<body>
  <header class="app-header">
    <div class="app-header-inner">
      <div class="app-brand">
        <img src="<?= base_url('assets/logo.svg') ?>" alt="GSB" width="48" height="48">
        <div><strong>GSB · Gestion des frais</strong><small>Espace visiteurs médicaux</small></div>
      </div>
      <?php if (session('user')): ?>
        <form method="post" action="<?= site_url('deconnexion') ?>"><?= csrf_field() ?><button class="secondary">Déconnexion</button></form>
      <?php endif ?>
    </div>
  </header>
  <?php if (session('user')): ?>
    <nav class="app-nav" aria-label="Navigation de l’espace visiteurs">
      <?php if (!empty(session('user')['admin'])): ?>
        <a href="<?= site_url('administration') ?>" aria-current="page">Administration</a>
      <?php else: ?>
        <a href="<?= site_url('fiches') ?>" aria-current="page">Mes fiches</a>
      <?php endif ?>
    </nav>
  <?php endif ?>
  <main>
    <?php if (session()->getFlashdata('error')): ?><p class="error" role="alert"><?= esc(session()->getFlashdata('error')) ?></p><?php endif ?>
    <?php if (session()->getFlashdata('success')): ?><p class="success" role="status"><?= esc(session()->getFlashdata('success')) ?></p><?php endif ?>
    <?= $this->renderSection('content') ?>
  </main>
  <footer><div class="footer-inner">Galaxy Swiss Bourdin · Projet pédagogique BTS SIO</div></footer>
</body>
</html>
