<?php
require __DIR__.'/../app/Libraries/FraisRules.php';
use App\Libraries\FraisRules as R;
$n=0;
function check(bool $ok,string $label): void { global $n; if (!$ok) throw new RuntimeException($label); $n++; }
function rejects(callable $f,string $label): void { try {$f();} catch (InvalidArgumentException $e) {check(true,$label);return;} check(false,$label); }
check(R::month('202609'),'mois réel'); check(!R::month('202613'),'mois 13 refusé');
check(R::editable('202609','CR','202609'),'mois ouvert');
check(!R::editable('202608','CR','202609'),'mois passé'); check(!R::editable('202610','CR','202609'),'mois futur');
foreach (['CL','VA','RB'] as $state) check(!R::editable('202609',$state,'202609'),'état fermé');
$month=date('Ym'); $valid=['date'=>date('Y-m-d'),'libelle'=>'Taxi','montant'=>'12,50'];
check(R::line($valid,$month)['montant']==='12.50','virgule acceptée');
foreach (['0','-1','1.001','1e3','1000000','NaN'] as $amount) rejects(fn()=>R::line(array_replace($valid,['montant'=>$amount]),$month),'montant refusé');
rejects(fn()=>R::line(array_replace($valid,['date'=>'2026-02-30']),'202602'),'date inexistante');
rejects(fn()=>R::line(array_replace($valid,['libelle'=>' ']),$month),'libellé vide');
rejects(fn()=>R::line(array_replace($valid,['montant'=>[]]),$month),'tableau hostile');
check(R::quantities(['KM'=>'12','REP'=>'0'],['KM','REP'])===['KM'=>12,'REP'=>0],'quantités');
rejects(fn()=>R::quantities(['KM'=>'1.5'],['KM']),'quantité décimale');
rejects(fn()=>R::quantities(['XYZ'=>'2'],['KM']),'forfait inconnu');
echo "$n tests métier réussis\n";
