<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/template.php';

requireLogin();
$user = currentUser();
$visiteurId = $user['id'];

$selectedMonth = trim($_GET['mois'] ?? '');
if ($selectedMonth !== '' && !preg_match('/^[0-9]{6}$/', $selectedMonth)) {
    $selectedMonth = '';
}

$fiches = getFichesByVisiteur($visiteurId);

renderHeader('Consultation des fiches - GSB', 'consultation');
?>

<h2>Consultation des fiches</h2>
<p>Vous pouvez consulter vos fiches de frais et les détails des lignes hors forfait.</p>

<?php if (empty($fiches)): ?>
  <div class="message">Aucune fiche n'est disponible. Créez d'abord une fiche dans le tableau de bord.</div>
<?php else: ?>
  <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
    <div style="flex:1 1 380px;">
      <h3>Mes fiches</h3>
      <table class="table">
        <thead>
          <tr>
            <th>Mois</th>
            <th>État</th>
            <th>Montant</th>
            <th>Détails</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fiches as $fiche): ?>
            <tr>
              <td><?php echo e($fiche['mois']); ?></td>
              <td><?php echo e($fiche['etatLibelle'] ?? $fiche['idEtat']); ?></td>
              <td><?php echo number_format((float) $fiche['montantValide'], 2, ',', ' '); ?> €</td>
              <td><a class="btn secondary" href="consultation-fiches.php?mois=<?php echo e($fiche['mois']); ?>">Voir</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($selectedMonth):
      $fiche = getFiche($visiteurId, $selectedMonth);
      $lignes = $fiche ? getLignesHorsForfait($visiteurId, $selectedMonth) : [];
    ?>
      <div style="flex:2 1 520px;">
        <h3>Détails de la fiche <?php echo e($selectedMonth); ?></h3>
        <?php if (!$fiche): ?>
          <div class="message error">La fiche sélectionnée n'existe pas.</div>
        <?php else: ?>
          <p><strong>État :</strong> <?php echo e($fiche['idEtat']); ?> | <strong>Montant validé :</strong> <?php echo number_format((float)$fiche['montantValide'], 2, ',', ' '); ?> €</p>
          <?php if (empty($lignes)): ?>
            <div class="message">Aucune ligne hors forfait n'a encore été saisie pour ce mois.</div>
          <?php else: ?>
            <table class="table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Libellé</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lignes as $ligne): ?>
                  <tr>
                    <td><?php echo e($ligne['date']); ?></td>
                    <td><?php echo e($ligne['libelle']); ?></td>
                    <td><?php echo number_format((float) $ligne['montant'], 2, ',', ' '); ?> €</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php renderFooter();
