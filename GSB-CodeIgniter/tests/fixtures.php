<?php
// Exclusivement pour le service SQL jetable du workflow, jamais pour gsb_ci.
$db = new mysqli('127.0.0.1','gsb_test','ephemeral-ci-only','gsb_test',3306);
$db->multi_query(file_get_contents(__DIR__.'/../database/schema.sql'));
do {if ($r=$db->store_result()) $r->free();} while ($db->more_results() && $db->next_result());
if ($db->errno) throw new RuntimeException($db->error);
$hash=password_hash('ephemeral-test-Password-123',PASSWORD_DEFAULT);
$stmt=$db->prepare('INSERT INTO Visiteur (id,nom,prenom,login,mdp) VALUES (?, ?, ?, ?, ?)');
foreach ([['v001','Test','Alice','alice'],['v002','Test','Bob','bob'],['a00','Test','Admin','admin']] as $u) {
 [$id,$nom,$prenom,$login]=$u; $stmt->bind_param('sssss',$id,$nom,$prenom,$login,$hash);$stmt->execute();
}
$db->query("INSERT INTO FicheFrais VALUES ('v001','200001',0,0,'2000-01-31','CL')");
