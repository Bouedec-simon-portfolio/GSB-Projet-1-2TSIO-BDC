<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>GSB Gestion des frais</title><link rel="stylesheet" href="<?= base_url('assets/gsb.css') ?>"></head>
<body><header><img src="<?= base_url('assets/logo.svg') ?>" alt="GSB" width="70"><div><strong>GSB</strong><br>Gestion des frais</div>
<?php if (session('user')): ?><nav><?php if (!empty(session('user')['admin'])): ?><a href="<?= site_url('administration') ?>">Administration</a><?php else: ?><a href="<?= site_url('fiches') ?>">Mes fiches</a><?php endif ?></nav><form method="post" action="<?= site_url('deconnexion') ?>"><?= csrf_field() ?><button>Déconnexion</button></form><?php endif ?></header>
<main><?php if (session()->getFlashdata('error')): ?><p class="error" role="alert"><?= esc(session()->getFlashdata('error')) ?></p><?php endif ?>
<?php if (session()->getFlashdata('success')): ?><p class="success" role="status"><?= esc(session()->getFlashdata('success')) ?></p><?php endif ?>
<?= $this->renderSection('content') ?></main><footer>Galaxy Swiss Bourdin · Projet pédagogique BTS SIO</footer></body></html>
