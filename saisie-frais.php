<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/template.php';

requireLogin();
$user = currentUser();
$visiteurId = $user['id'];

$errors = [];
$success = '';

$moisSelectionne = trim($_GET['mois'] ?? '');
if ($moisSelectionne !== '' && !preg_match('/^[0-9]{6}$/', $moisSelectionne)) {
    $errors[] = 'Mois invalide.';
    $moisSelectionne = '';
}

$fiches = getFichesByVisiteur($visiteurId);

// Si aucun mois n'est sélectionné, prendre le plus récent.
if (!$moisSelectionne && !empty($fiches)) {
    $moisSelectionne = $fiches[0]['mois'];
}

// Interdire la saisie pour les mois déjà terminés.
$moisActuel = date('Ym');
$moisTermine = false;
if ($moisSelectionne !== '' && preg_match('/^[0-9]{6}$/', $moisSelectionne)) {
    $moisTermine = ((int) $moisSelectionne < (int) $moisActuel);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mois = trim($_POST['mois'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $mois)) {
        $errors[] = 'Le mois sélectionné est invalide.';
    } else {
        $moisSelectionne = $mois;
    }

    if (empty($errors)) {
        if ($action === 'add_line') {
            if ($moisTermine) {
                $errors[] = 'Vous ne pouvez pas ajouter de ligne sur un mois déjà terminé.';
            }

            $date = trim($_POST['date'] ?? '');
            $libelle = trim($_POST['libelle'] ?? '');
            $montant = trim($_POST['montant'] ?? '');

            if ($date === '') {
                $errors[] = 'La date est requise.';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $errors[] = 'Le format de la date doit être AAAA-MM-JJ.';
            }

            if ($libelle === '') {
                $errors[] = 'Le libellé est requis.';
            }

            if ($montant === '') {
                $errors[] = 'Le montant est requis.';
            } elseif (!is_numeric($montant) || (float) $montant <= 0) {
                $errors[] = 'Le montant doit être un nombre supérieur à 0.';
            }

            if (empty($errors)) {
                createFicheIfNotExists($visiteurId, $moisSelectionne);
                if (addLigneHorsForfait($visiteurId, $moisSelectionne, $libelle, $date, (float) $montant)) {
                    $success = 'La ligne de frais a été ajoutée.';
                } else {
                    $errors[] = 'Impossible d’ajouter la ligne de frais. Veuillez réessayer.';
                }
            }
        } elseif ($action === 'delete_line') {
            $ligneId = (int) ($_POST['ligne_id'] ?? 0);
            if ($ligneId <= 0) {
                $errors[] = 'Identifiant de ligne invalide.';
            } else {
                if (deleteLigneHorsForfait($ligneId, $visiteurId, $moisSelectionne)) {
                    $success = 'La ligne de frais a été supprimée.';
                } else {
                    $errors[] = 'Impossible de supprimer la ligne de frais.';
                }
            }
        }
    }
}

$lignes = [];
if ($moisSelectionne !== '') {
    $lignes = getLignesHorsForfait($visiteurId, $moisSelectionne);
}

renderHeader('Saisie de frais - GSB', 'saisie');
?>

<h2>Saisie de frais</h2>
<p>Ajoutez vos frais hors forfait pour un mois donné.</p>

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

<form method="get" style="max-width:380px; margin-bottom:1.5rem;">
  <div class="form-group">
    <label for="mois">Choisir une fiche (mois)</label>
    <select id="mois" name="mois" onchange="this.form.submit()" required>
      <?php foreach ($fiches as $fiche): ?>
        <option value="<?php echo e($fiche['mois']); ?>" <?php echo $fiche['mois'] === $moisSelectionne ? 'selected' : ''; ?>>
          <?php echo e($fiche['mois']); ?> - <?php echo e($fiche['etatLibelle'] ?? $fiche['idEtat']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<?php if ($moisSelectionne === '' && empty($fiches)): ?>
  <p>Aucune fiche trouvée. Créez d'abord une fiche sur le <a href="tableau-bord.php">tableau de bord</a>.</p>
<?php else: ?>
  <h3>Fiche de <?php echo e($moisSelectionne); ?></h3>

  <?php if ($moisTermine): ?>
    <div class="message warning">Vous ne pouvez plus ajouter de lignes sur un mois déjà terminé (<?php echo e($moisSelectionne); ?>).</div>
  <?php else: ?>
    <form method="post" style="max-width:600px;">
      <input type="hidden" name="action" value="add_line" />
      <input type="hidden" name="mois" value="<?php echo e($moisSelectionne); ?>" />

      <div class="form-group">
        <label for="date">Date</label>
        <input id="date" type="date" name="date" required />
      </div>
    <div class="form-group">
      <label for="libelle">Libellé</label>
      <input id="libelle" type="text" name="libelle" required maxlength="100" />
    </div>
    <div class="form-group">
      <label for="montant">Montant (€)</label>
      <input id="montant" type="number" name="montant" step="0.01" min="0.01" required />
    </div>
    <button type="submit" class="btn">Ajouter la ligne</button>
  </form>
  <?php endif; ?>

  <h3 style="margin-top:2rem;">Lignes de frais</h3>
  <?php if (empty($lignes)): ?>
    <p>Aucune ligne de frais pour ce mois.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Libellé</th>
          <th>Montant</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($lignes as $ligne): ?>
        <tr>
          <td><?php echo e($ligne['date']); ?></td>
          <td><?php echo e($ligne['libelle']); ?></td>
          <td><?php echo number_format((float) $ligne['montant'], 2, ',', ' '); ?> €</td>
          <td>
            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette ligne ?');">
              <input type="hidden" name="action" value="delete_line" />
              <input type="hidden" name="mois" value="<?php echo e($moisSelectionne); ?>" />
              <input type="hidden" name="ligne_id" value="<?php echo (int) $ligne['id']; ?>" />
              <button type="submit" class="btn secondary">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php endif; ?>

<?php renderFooter();
