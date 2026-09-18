<?= $this->extend('layout') ?><?= $this->section('content') ?>
<h1>Consultation administrative</h1><p>Lecture des fiches de tous les visiteurs. La validation comptable n'est pas implémentée.</p>
<div class="table-wrap"><table><thead><tr><th>Visiteur</th><th>Mois</th><th>État</th><th>Montant validé</th></tr></thead><tbody>
<?php foreach ($fiches as $f): ?><tr><td><?= esc($f['prenom'].' '.$f['nom']) ?></td><td><?= esc($f['mois']) ?></td><td><?= esc($f['idEtat']) ?></td><td><?= number_format((float)$f['montantValide'],2,',',' ') ?> €</td></tr><?php endforeach ?>
</tbody></table></div><?= $this->endSection() ?>
