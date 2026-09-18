<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/template.php';

requireLogin();
requireAdmin();

$fiches = getAllFichesWithVisiteur();

renderHeader('Gestion des fiches - Admin - GSB', 'admin');
?>

<h2>Gestion des fiches de frais (Admin)</h2>
<p>Consultez les fiches de frais de tous les visiteurs.</p>

<?php if (empty($fiches)): ?>
  <div class="message">Aucune fiche de frais n'a été créée.</div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Visiteur</th>
          <th>Mois</th>
          <th>État</th>
          <th>Nb Justificatifs</th>
          <th>Montant validé</th>
          <th>Date dernière modif.</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($fiches as $fiche): ?>
          <tr>
            <td><?php echo e($fiche['prenom'] . ' ' . $fiche['nom']); ?></td>
            <td><?php echo e($fiche['mois']); ?></td>
            <td><?php echo e($fiche['etatLibelle'] ?? $fiche['idEtat']); ?></td>
            <td><?php echo (int) $fiche['nbJustificatifs']; ?></td>
            <td><?php echo number_format((float) $fiche['montantValide'], 2, ',', ' '); ?> €</td>
            <td><?php echo e($fiche['dateModif']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php renderFooter();
