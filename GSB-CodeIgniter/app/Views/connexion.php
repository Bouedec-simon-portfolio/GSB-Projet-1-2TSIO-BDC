<?= $this->extend('layout') ?><?= $this->section('content') ?>
<h1>Connexion</h1><p>Accédez à vos fiches de frais.</p>
<form method="post" action="<?= site_url('connexion') ?>" class="card compact"><?= csrf_field() ?>
<label for="login">Identifiant</label><input id="login" name="login" required maxlength="20" autocomplete="username">
<label for="password">Mot de passe</label><input id="password" type="password" name="password" required autocomplete="current-password">
<button>Se connecter</button></form><?= $this->endSection() ?>
