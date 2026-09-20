<?php
// Lecture seule, après http_smoke.py ; base jetable du workflow uniquement.
date_default_timezone_set('Europe/Paris');
$db = new mysqli('127.0.0.1', 'gsb_test', 'ephemeral-ci-only', 'gsb_test', 3306);
function value(mysqli $db, string $sql, array $params = []): mixed {
    $stmt = $db->prepare($sql);
    if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}
function expect(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$month = date('Ym');
expect((int)value($db, "SELECT COUNT(*) FROM FicheFrais WHERE idVisiteur='v001' AND mois=?", [$month]) === 1, 'Une seule fiche après deux demandes de création');
foreach (['ETP'=>0, 'KM'=>10, 'NUI'=>0, 'REP'=>2] as $code=>$quantity) {
    expect((int)value($db, "SELECT quantite FROM LigneFraisForfait WHERE idVisiteur='v001' AND mois=? AND idFraisForfait=?", [$month, $code]) === $quantity, 'Quantité enregistrée : '.$code);
}
expect((int)value($db, "SELECT COUNT(*) FROM LigneFraisForfait WHERE idVisiteur='v001' AND mois=?", [$month]) === 4, 'Quatre lignes forfaitaires, sans doublon');
expect((int)value($db, "SELECT COUNT(*) FROM LigneFraisHorsForfait WHERE libelle IN ('TEST TAXI','REFUSER','SANS TOKEN','FERMEE')") === 0, 'Taxi supprimé et lignes invalides absentes');
expect((int)value($db, "SELECT COUNT(*) FROM LigneFraisHorsForfait WHERE idVisiteur='v002'") === 0, 'Aucune ligne ajoutée pour Bob');
expect((int)value($db, "SELECT COUNT(*) FROM LigneFraisHorsForfait WHERE idVisiteur='v001' AND mois=? AND libelle=? AND montant=1", [$month, '<script>alert(1)</script>']) === 1, 'La chaîne HTML est stockée comme donnée et affichée échappée');
expect((float)value($db, "SELECT montantValide FROM FicheFrais WHERE idVisiteur='v001' AND mois=?", [$month]) === 0.0, 'La saisie ne valide pas le montant comptable');
expect(value($db, "SELECT idEtat FROM FicheFrais WHERE idVisiteur='v001' AND mois='200001'") === 'CL', 'La fiche ancienne reste clôturée');
echo "Contrôles SQL réussis : création, modification, suppression, droits et absence de lignes invalides.\n";
