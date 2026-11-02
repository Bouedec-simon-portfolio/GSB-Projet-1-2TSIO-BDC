<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/template.php';

requireLogin();
$user = currentUser();
$visiteurId = $user['id'];

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_month'])) {
    $month = trim($_POST['new_month']);
    if (!csrfValide()) {
        $errors[] = 'Le formulaire a expiré, veuillez réessayer.';
    } elseif (!moisValide($month)) {
        $errors[] = 'Le mois doit être au format AAAAMM avec un mois de 01 à 12 (ex : 202602).';
    } elseif ($month > date('Ym')) {
        // Comparaison de chaînes possible car le format AAAAMM est toujours sur 6 chiffres.
        $errors[] = 'Vous ne pouvez pas créer une fiche pour un mois futur.';
    } elseif (createFicheIfNotExists($visiteurId, $month)) {
        $success = "La fiche du mois $month est prête. Les fiches précédentes en cours ont été clôturées.";
    } else {
        $errors[] = 'Impossible de créer la fiche. Veuillez réessayer plus tard.';
    }
}

$fiches = getFichesByVisiteur($visiteurId);

renderHeader('Tableau de bord - GSB', 'dashboard');
?>

<div style="display:flex; flex-wrap:wrap; gap:1.5rem;">
  <div style="flex:1 1 420px;">
    <h2>Créer une nouvelle fiche</h2>
    <p>Créez la fiche de frais du mois en cours. La saisie n'est possible que sur une fiche « en cours ».</p>
    <?php if (!empty($errors)): ?>
      <div class="message error">
        <ul style="margin:0; padding-left:1.2rem;">
          <?php foreach ($errors as $error): ?>
            <li><?php echo e($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="message success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <form method="post" novalidate style="max-width:380px;">
      <?php csrfField(); ?>
      <div class="form-group">
        <label for="new_month">Mois (AAAAMM)</label>
        <input id="new_month" name="new_month" type="text" pattern="[0-9]{6}" value="<?php echo date('Ym'); ?>" required />
        <small style="color:#666;">Ex: 202602 pour février 2026.</small>
      </div>
      <button type="submit" class="btn">Créer la fiche</button>
    </form>
  </div>

  <div style="flex:2 1 520px;">
    <h2>Vos fiches de frais</h2>
    <?php if (empty($fiches)): ?>
      <p>Aucune fiche de frais n'a encore été créée. Commencez par créer une fiche.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Mois</th>
            <th>État</th>
            <th>Nb justificatifs</th>
            <th>Montant validé</th>
            <th>Date dernière modif.</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fiches as $fiche): ?>
            <tr>
              <td><?php echo e($fiche['mois']); ?></td>
              <td><?php echo e($fiche['etatLibelle'] ?? $fiche['idEtat']); ?></td>
              <td><?php echo (int) $fiche['nbJustificatifs']; ?></td>
              <td><?php echo number_format((float) $fiche['montantValide'], 2, ',', ' '); ?> €</td>
              <td><?php echo e($fiche['dateModif']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php renderFooter();
