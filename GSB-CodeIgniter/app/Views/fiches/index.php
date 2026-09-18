<?= $this->extend('layout') ?><?= $this->section('content') ?>
<h1>Mes fiches de frais</h1><p>Bonjour <?= esc(session('user')['prenom']) ?>. Retrouvez vos déclarations mensuelles.</p>
<form method="post" action="<?= site_url('fiches') ?>"><?= csrf_field() ?><button>Ouvrir ou créer la fiche du mois courant</button></form>
<?php if (!$fiches): ?><p>Aucune fiche pour le moment.</p><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Mois</th><th>État</th><th>Montant validé</th><th>Action</th></tr></thead><tbody>
<?php foreach ($fiches as $f): ?><tr><td><?= esc($f['mois']) ?></td><td><?= esc($f['etatLibelle']) ?></td><td><?= number_format((float)$f['montantValide'],2,',',' ') ?> €</td><td><a href="<?= site_url('fiches/'.$f['mois']) ?>">Consulter / saisir</a></td></tr><?php endforeach ?>
</tbody></table></div><?php endif ?><?= $this->endSection() ?>
