<?= $this->extend('layout') ?><?= $this->section('content') ?>
<h1>Fiche <?= esc($fiche['mois']) ?></h1><p>État : <?= esc($fiche['idEtat']) ?> · Total déclaré : <strong><?= number_format($total,2,',',' ') ?> €</strong> · Montant validé : <?= number_format((float)$fiche['montantValide'],2,',',' ') ?> €</p>
<?php if (!$editable): ?><p class="notice">Consultation uniquement : la saisie est réservée au mois courant et aux fiches en cours (CR).</p><?php endif ?>
<section class="card"><h2>Frais forfaitaires</h2><form method="post" action="<?= site_url('fiches/'.$fiche['mois'].'/forfait') ?>"><?= csrf_field() ?>
<div class="table-wrap"><table><thead><tr><th>Forfait</th><th>Tarif</th><th>Quantité</th><th>Total</th></tr></thead><tbody>
<?php foreach ($forfaits as $f): ?><tr><td><label for="q-<?= esc($f['id'],'attr') ?>"><?= esc($f['libelle']) ?></label></td><td><?= number_format((float)$f['montant'],2,',',' ') ?> €</td><td><input id="q-<?= esc($f['id'],'attr') ?>" type="number" name="quantites[<?= esc($f['id'],'attr') ?>]" min="0" max="99999" step="1" value="<?= (int)$f['quantite'] ?>" required <?= $editable ? '' : 'disabled' ?>></td><td><?= number_format((float)$f['montant']*(int)$f['quantite'],2,',',' ') ?> €</td></tr><?php endforeach ?>
</tbody></table></div><?php if ($editable): ?><button>Enregistrer les quantités</button><?php endif ?></form></section>
<section class="card"><h2>Frais hors forfait</h2>
<?php if ($editable): ?><form method="post" action="<?= site_url('fiches/'.$fiche['mois'].'/hors-forfait') ?>" class="expense-form"><?= csrf_field() ?>
<label>Date<input type="date" name="date" required min="<?= esc(substr($fiche['mois'],0,4).'-'.substr($fiche['mois'],4,2).'-01','attr') ?>" max="<?= date('Y-m-d') ?>"></label>
<label>Libellé<input name="libelle" maxlength="100" required></label><label>Montant en euros<input name="montant" type="number" step="0.01" min="0.01" max="999999.99" required></label><button>Ajouter</button></form><?php endif ?>
<?php if (!$lines): ?><p>Aucun frais hors forfait.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Date</th><th>Libellé</th><th>Montant</th><th>Action</th></tr></thead><tbody>
<?php foreach ($lines as $l): ?><tr><td><?= esc($l['date']) ?></td><td><?= esc($l['libelle']) ?></td><td><?= number_format((float)$l['montant'],2,',',' ') ?> €</td><td><?php if ($editable): ?><form method="post" action="<?= site_url('fiches/'.$fiche['mois'].'/hors-forfait/'.$l['id'].'/supprimer') ?>"><?= csrf_field() ?><button class="secondary">Supprimer</button></form><?php endif ?></td></tr><?php endforeach ?>
</tbody></table></div><?php endif ?></section><?= $this->endSection() ?>
