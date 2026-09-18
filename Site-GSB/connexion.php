<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/template.php';

if (isLoggedIn()) {
    header('Location: tableau-bord.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login === '') {
        $errors[] = 'Veuillez renseigner votre identifiant.';
    }
    if ($password === '') {
        $errors[] = 'Veuillez renseigner votre mot de passe.';
    }

    if (empty($errors)) {
        if (login($login, $password)) {
            header('Location: tableau-bord.php');
            exit;
        }
        $errors[] = 'Identifiant ou mot de passe incorrect.';
    }
}

renderHeader('Connexion - GSB', 'login');
?>

<h2>Connexion</h2>
<p>Connectez-vous avec vos identifiants pour accéder à votre espace de gestion des fiches de frais.</p>

<?php if (!empty($errors)): ?>
  <div class="message error">
    <ul style="margin:0; padding-left:1.2rem;">
      <?php foreach ($errors as $error): ?>
        <li><?php echo e($error); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" novalidate style="max-width:380px;">
  <div class="form-group">
    <label for="login">Identifiant</label>
    <input id="login" name="login" type="text" required value="<?php echo e($_POST['login'] ?? ''); ?>" />
  </div>
  <div class="form-group">
    <label for="password">Mot de passe</label>
    <input id="password" name="password" type="password" required />
  </div>
  <button type="submit" class="btn">Se connecter</button>
</form>

<?php renderFooter();
