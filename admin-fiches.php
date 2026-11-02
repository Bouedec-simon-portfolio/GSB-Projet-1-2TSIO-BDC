<?php
/*
 * Espace administrateur (rôle de comptable).
 *
 * Cycle de vie d'une fiche de frais :
 *   CR (saisie en cours) → CL (clôturée) → VA (validée, mise en paiement) → RB (remboursée)
 *
 * - Clôturer : le visiteur ne peut plus modifier la fiche.
 * - Refuser une ligne hors forfait : possible tant que la fiche est clôturée.
 * - Valider : le montant validé est calculé (forfait + hors forfait non refusé).
 * - Rembourser : dernière étape, après le paiement.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/template.php';

requireAdmin();

$errors = [];
$success = '';

// Fiche sélectionnée (en GET pour l'affichage, en POST pour les actions).
$source = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
$idVisiteur = trim($source['visiteur'] ?? '');
$mois = trim($source['mois'] ?? '');

$fiche = null;
if ($idVisiteur !== '' && moisValide($mois)) {
    $fiche = getFiche($idVisiteur, $mois);
}

// ------------------------------------------------------------
// Traitement des actions du comptable
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $etat = $fiche['idEtat'] ?? '';

    if (!csrfValide()) {
        $errors[] = 'Le formulaire a expiré, veuillez réessayer.';
    } elseif ($fiche === null) {
        $errors[] = 'Fiche introuvable.';
    } elseif ($action === 'cloturer' && $etat === 'CR') {
        changerEtatFiche($idVisiteur, $mois, 'CL');
        $success = 'La fiche a été clôturée.';
    } elseif ($action === 'refuser' && $etat === 'CL') {
        $ligneId = (int) ($_POST['ligne_id'] ?? 0);
        if (refuserLigneHorsForfait($ligneId, $idVisiteur, $mois)) {
            $success = 'La ligne a été refusée.';
        } else {
            $errors[] = 'Impossible de refuser cette ligne (déjà refusée ?).';
        }
    } elseif ($action === 'valider' && $etat === 'CL') {
        validerFiche($idVisiteur, $mois);
        $success = 'La fiche a été validée et mise en paiement.';
    } elseif ($action === 'rembourser' && $etat === 'VA') {
        changerEtatFiche($idVisiteur, $mois, 'RB');
        $success = 'La fiche est marquée comme remboursée.';
    } else {
        // L'action ne correspond pas à l'état actuel de la fiche.
        $errors[] = 'Cette action n\'est pas possible pour une fiche dans cet état.';
    }

    // On relit la fiche pour afficher son nouvel état.
    $fiche = getFiche($idVisiteur, $mois);
}

$fiches = getAllFichesWithVisiteur();

renderHeader('Gestion des fiches - Admin - GSB', 'admin');
?>

<h2>Gestion des fiches de frais (Admin)</h2>
<p>Consultez, clôturez, validez et remboursez les fiches de frais des visiteurs.</p>

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

<?php if ($fiche !== null):
  $visiteur = getVisiteur($idVisiteur);
  $lignesForfait = getLignesFraisForfait($idVisiteur, $mois);
  $lignes = getLignesHorsForfait($idVisiteur, $mois);
  $totalForfait = getTotalFraisForfait($idVisiteur, $mois);
  $totalHorsForfait = getTotalHorsForfait($idVisiteur, $mois);
?>
  <section style="border:1px solid #ddd; border-radius:8px; padding:1rem; margin-bottom:2rem;">
    <h3 style="margin-top:0;">
      Fiche de <?php echo e($visiteur['prenom'] . ' ' . $visiteur['nom']); ?> — <?php echo e($mois); ?>
    </h3>
    <p>
      <strong>État :</strong> <?php echo e($fiche['etatLibelle']); ?><br>
      <strong>Total forfait :</strong> <?php echo number_format($totalForfait, 2, ',', ' '); ?> € —
      <strong>Total hors forfait (hors refus) :</strong> <?php echo number_format($totalHorsForfait, 2, ',', ' '); ?> € —
      <strong>Total :</strong> <?php echo number_format($totalForfait + $totalHorsForfait, 2, ',', ' '); ?> €<br>
      <strong>Montant validé :</strong> <?php echo number_format((float) $fiche['montantValide'], 2, ',', ' '); ?> €
    </p>

    <h4>Frais forfaitisés</h4>
    <table class="table">
      <thead><tr><th>Frais</th><th>Quantité</th><th>Tarif</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($lignesForfait as $ligne): ?>
          <tr>
            <td><?php echo e($ligne['libelle']); ?></td>
            <td><?php echo (int) $ligne['quantite']; ?></td>
            <td><?php echo number_format((float) $ligne['montant'], 2, ',', ' '); ?> €</td>
            <td><?php echo number_format($ligne['quantite'] * $ligne['montant'], 2, ',', ' '); ?> €</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h4>Frais hors forfait</h4>
    <?php if (empty($lignes)): ?>
      <p>Aucune ligne hors forfait.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Date</th><th>Libellé</th><th>Montant</th>
            <?php if ($fiche['idEtat'] === 'CL'): ?><th>Action</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lignes as $ligne): ?>
            <tr class="<?php echo ligneRefusee($ligne) ? 'refusee' : ''; ?>">
              <td><?php echo e($ligne['date']); ?></td>
              <td><?php echo e($ligne['libelle']); ?></td>
              <td><?php echo number_format((float) $ligne['montant'], 2, ',', ' '); ?> €</td>
              <?php if ($fiche['idEtat'] === 'CL'): ?>
                <td>
                  <?php if (!ligneRefusee($ligne)): ?>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Refuser cette ligne ?');">
                      <?php csrfField(); ?>
                      <input type="hidden" name="action" value="refuser" />
                      <input type="hidden" name="visiteur" value="<?php echo e($idVisiteur); ?>" />
                      <input type="hidden" name="mois" value="<?php echo e($mois); ?>" />
                      <input type="hidden" name="ligne_id" value="<?php echo (int) $ligne['id']; ?>" />
                      <button type="submit" class="btn danger">Refuser</button>
                    </form>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <?php
    // Un seul bouton est proposé : celui de l'étape suivante.
    $prochaineAction = [
        'CR' => ['cloturer', 'Clôturer la fiche'],
        'CL' => ['valider', 'Valider et mettre en paiement'],
        'VA' => ['rembourser', 'Marquer comme remboursée'],
    ];
    ?>
    <?php if (isset($prochaineAction[$fiche['idEtat']])):
      [$action, $texte] = $prochaineAction[$fiche['idEtat']]; ?>
      <form method="post" style="margin-top:1rem;">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="<?php echo e($action); ?>" />
        <input type="hidden" name="visiteur" value="<?php echo e($idVisiteur); ?>" />
        <input type="hidden" name="mois" value="<?php echo e($mois); ?>" />
        <button type="submit" class="btn"><?php echo e($texte); ?></button>
      </form>
    <?php else: ?>
      <p><em>Cette fiche est terminée (remboursée).</em></p>
    <?php endif; ?>

    <p style="margin-top:1rem;"><a href="admin-fiches.php">← Retour à la liste</a></p>
  </section>
<?php elseif ($idVisiteur !== '' || $mois !== ''): ?>
  <div class="message error">La fiche demandée n'existe pas.</div>
<?php endif; ?>

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
          <th>Nb justificatifs</th>
          <th>Montant validé</th>
          <th>Date dernière modif.</th>
          <th>Détails</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($fiches as $f): ?>
          <tr>
            <td><?php echo e($f['prenom'] . ' ' . $f['nom']); ?></td>
            <td><?php echo e($f['mois']); ?></td>
            <td><?php echo e($f['etatLibelle']); ?></td>
            <td><?php echo (int) $f['nbJustificatifs']; ?></td>
            <td><?php echo number_format((float) $f['montantValide'], 2, ',', ' '); ?> €</td>
            <td><?php echo e($f['dateModif']); ?></td>
            <td>
              <a class="btn secondary"
                 href="admin-fiches.php?visiteur=<?php echo urlencode($f['idVisiteur']); ?>&amp;mois=<?php echo urlencode($f['mois']); ?>">Gérer</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php renderFooter();
