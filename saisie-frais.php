<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/template.php';

requireLogin();
$user = currentUser();
$visiteurId = $user['id'];

$errors = [];
$success = '';

// ------------------------------------------------------------
// 1. Quel mois est sélectionné ?
//    - en POST : le mois vient du formulaire envoyé ;
//    - en GET  : le mois vient de la liste déroulante (?mois=...).
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moisSelectionne = trim($_POST['mois'] ?? '');
} else {
    $moisSelectionne = trim($_GET['mois'] ?? '');
}

if ($moisSelectionne !== '' && !moisValide($moisSelectionne)) {
    $errors[] = 'Mois invalide.';
    $moisSelectionne = '';
}

$fiches = getFichesByVisiteur($visiteurId);

// Si aucun mois n'est sélectionné, on prend la fiche la plus récente.
if ($moisSelectionne === '' && !empty($fiches)) {
    $moisSelectionne = $fiches[0]['mois'];
}

// On relit la fiche en base : c'est son ÉTAT qui décide si on peut la modifier,
// jamais une valeur envoyée par le navigateur.
$fiche = $moisSelectionne !== '' ? getFiche($visiteurId, $moisSelectionne) : null;
$modifiable = ficheModifiable($fiche);

// ------------------------------------------------------------
// 2. Traitement des formulaires
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $action = $_POST['action'] ?? '';

    if (!csrfValide()) {
        $errors[] = 'Le formulaire a expiré, veuillez réessayer.';
    } elseif ($fiche === null) {
        $errors[] = 'Cette fiche n\'existe pas.';
    } elseif (!$modifiable) {
        $errors[] = 'Cette fiche n\'est plus modifiable (état : ' . $fiche['etatLibelle'] . ').';
    } elseif ($action === 'maj_forfait') {
        // --- Mise à jour des frais forfaitisés ---
        $quantitesRecues = $_POST['quantite'] ?? [];
        $quantites = [];

        foreach (getFraisForfait() as $frais) {
            $valeur = trim((string) ($quantitesRecues[$frais['id']] ?? '0'));
            // ctype_digit() accepte seulement des chiffres : pas de négatif ni de virgule.
            if ($valeur === '' || !ctype_digit($valeur) || (int) $valeur > 10000) {
                $errors[] = 'La quantité pour « ' . $frais['libelle'] . ' » doit être un entier entre 0 et 10000.';
            } else {
                $quantites[$frais['id']] = (int) $valeur;
            }
        }

        if (empty($errors)) {
            majFraisForfait($visiteurId, $moisSelectionne, $quantites);
            $success = 'Les frais forfaitisés ont été enregistrés.';
        }
    } elseif ($action === 'add_line') {
        // --- Ajout d'un frais hors forfait ---
        $date = trim($_POST['date'] ?? '');
        $libelle = trim($_POST['libelle'] ?? '');
        $montant = str_replace(',', '.', trim($_POST['montant'] ?? ''));

        if ($date === '') {
            $errors[] = 'La date est requise.';
        } elseif (!dateValide($date)) {
            $errors[] = 'La date est invalide.';
        } elseif (!dateDansLaDerniereAnnee($date)) {
            $errors[] = 'La date doit dater de moins d\'un an et ne pas être dans le futur.';
        }

        if ($libelle === '') {
            $errors[] = 'Le libellé est requis.';
        } elseif (mb_strlen($libelle) > 80) {
            // 80 et non 100 : on garde de la place pour le préfixe "REFUSÉ : ".
            $errors[] = 'Le libellé ne doit pas dépasser 80 caractères.';
        }

        if ($montant === '') {
            $errors[] = 'Le montant est requis.';
        } elseif (!is_numeric($montant) || (float) $montant <= 0 || (float) $montant > 99999) {
            $errors[] = 'Le montant doit être un nombre entre 0,01 et 99 999 €.';
        }

        if (empty($errors)) {
            if (addLigneHorsForfait($visiteurId, $moisSelectionne, $libelle, $date, round((float) $montant, 2))) {
                $success = 'La ligne de frais a été ajoutée.';
            } else {
                $errors[] = 'Impossible d\'ajouter la ligne de frais. Veuillez réessayer.';
            }
        }
    } elseif ($action === 'delete_line') {
        // --- Suppression d'un frais hors forfait ---
        $ligneId = (int) ($_POST['ligne_id'] ?? 0);
        if ($ligneId <= 0) {
            $errors[] = 'Identifiant de ligne invalide.';
        } elseif (deleteLigneHorsForfait($ligneId, $visiteurId, $moisSelectionne)) {
            $success = 'La ligne de frais a été supprimée.';
        } else {
            $errors[] = 'Impossible de supprimer cette ligne.';
        }
    } else {
        $errors[] = 'Action inconnue.';
    }
}

// ------------------------------------------------------------
// 3. Données à afficher
// ------------------------------------------------------------
$lignesForfait = [];
$lignes = [];
if ($fiche !== null) {
    $lignesForfait = getLignesFraisForfait($visiteurId, $moisSelectionne);
    $lignes = getLignesHorsForfait($visiteurId, $moisSelectionne);
}

renderHeader('Saisie de frais - GSB', 'saisie');
?>

<h2>Saisie de frais</h2>
<p>Renseignez vos frais forfaitisés et vos frais hors forfait pour le mois choisi.</p>

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

<?php if (empty($fiches)): ?>
  <p>Aucune fiche trouvée. Créez d'abord une fiche sur le <a href="tableau-bord.php">tableau de bord</a>.</p>
<?php else: ?>

  <form method="get" style="max-width:380px; margin-bottom:1.5rem;">
    <div class="form-group">
      <label for="mois">Choisir une fiche (mois)</label>
      <select id="mois" name="mois" onchange="this.form.submit()">
        <?php foreach ($fiches as $f): ?>
          <option value="<?php echo e($f['mois']); ?>" <?php echo $f['mois'] === $moisSelectionne ? 'selected' : ''; ?>>
            <?php echo e($f['mois']); ?> - <?php echo e($f['etatLibelle']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <noscript><button type="submit" class="btn secondary">Afficher</button></noscript>
  </form>

  <?php if ($fiche === null): ?>
    <div class="message error">La fiche sélectionnée n'existe pas.</div>
  <?php else: ?>
    <h3>Fiche de <?php echo e($moisSelectionne); ?> — <?php echo e($fiche['etatLibelle']); ?></h3>

    <?php if (!$modifiable): ?>
      <div class="message warning">Cette fiche n'est plus modifiable : elle a été clôturée ou traitée par le service comptable.</div>
    <?php endif; ?>

    <!-- ===== Frais forfaitisés ===== -->
    <h3 style="margin-top:1.5rem;">Frais forfaitisés</h3>
    <form method="post" style="max-width:600px;">
      <?php csrfField(); ?>
      <input type="hidden" name="action" value="maj_forfait" />
      <input type="hidden" name="mois" value="<?php echo e($moisSelectionne); ?>" />
      <table class="table">
        <thead>
          <tr><th>Frais</th><th>Tarif</th><th>Quantité</th></tr>
        </thead>
        <tbody>
          <?php foreach ($lignesForfait as $ligne): ?>
            <tr>
              <td><label for="q_<?php echo e($ligne['id']); ?>"><?php echo e($ligne['libelle']); ?></label></td>
              <td><?php echo number_format((float) $ligne['montant'], 2, ',', ' '); ?> €</td>
              <td>
                <input id="q_<?php echo e($ligne['id']); ?>" type="number" min="0" max="10000" step="1"
                       name="quantite[<?php echo e($ligne['id']); ?>]"
                       value="<?php echo (int) $ligne['quantite']; ?>"
                       <?php echo $modifiable ? '' : 'disabled'; ?> />
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($modifiable): ?>
        <button type="submit" class="btn" style="margin-top:1rem;">Enregistrer les frais forfaitisés</button>
      <?php endif; ?>
    </form>

    <!-- ===== Frais hors forfait ===== -->
    <h3 style="margin-top:2rem;">Frais hors forfait</h3>
    <?php if ($modifiable): ?>
      <form method="post" style="max-width:600px;">
        <?php csrfField(); ?>
        <input type="hidden" name="action" value="add_line" />
        <input type="hidden" name="mois" value="<?php echo e($moisSelectionne); ?>" />

        <div class="form-group">
          <label for="date">Date</label>
          <input id="date" type="date" name="date" required
                 min="<?php echo date('Y-m-d', strtotime('-1 year')); ?>" max="<?php echo date('Y-m-d'); ?>" />
        </div>
        <div class="form-group">
          <label for="libelle">Libellé</label>
          <input id="libelle" type="text" name="libelle" required maxlength="80" />
        </div>
        <div class="form-group">
          <label for="montant">Montant (€)</label>
          <input id="montant" type="number" name="montant" step="0.01" min="0.01" max="99999" required />
        </div>
        <button type="submit" class="btn">Ajouter la ligne</button>
      </form>
    <?php endif; ?>

    <?php if (empty($lignes)): ?>
      <p>Aucune ligne hors forfait pour ce mois.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Libellé</th>
            <th>Montant</th>
            <?php if ($modifiable): ?><th>Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lignes as $ligne): ?>
          <tr class="<?php echo ligneRefusee($ligne) ? 'refusee' : ''; ?>">
            <td><?php echo e($ligne['date']); ?></td>
            <td><?php echo e($ligne['libelle']); ?></td>
            <td><?php echo number_format((float) $ligne['montant'], 2, ',', ' '); ?> €</td>
            <?php if ($modifiable): ?>
              <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette ligne ?');">
                  <?php csrfField(); ?>
                  <input type="hidden" name="action" value="delete_line" />
                  <input type="hidden" name="mois" value="<?php echo e($moisSelectionne); ?>" />
                  <input type="hidden" name="ligne_id" value="<?php echo (int) $ligne['id']; ?>" />
                  <button type="submit" class="btn secondary">Supprimer</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  <?php endif; ?>
<?php endif; ?>

<?php renderFooter();
