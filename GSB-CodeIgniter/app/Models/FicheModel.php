<?php
namespace App\Models;
use CodeIgniter\Model;
use App\Libraries\FraisRules;
use InvalidArgumentException;
use RuntimeException;
class FicheModel extends Model
{
    protected $table = 'FicheFrais';
    protected $returnType = 'array';
    public function liste(string $id): array
    {
        return $this->db->table('FicheFrais f')->select('f.*, e.libelle AS etatLibelle')
            ->join('Etat e', 'e.id = f.idEtat')->where('f.idVisiteur', $id)->orderBy('f.mois','DESC')->get()->getResultArray();
    }
    public function fiche(string $id, string $month): ?array
    {
        return $this->db->table('FicheFrais')->where(['idVisiteur'=>$id,'mois'=>$month])->get()->getRowArray();
    }
    public function createFor(string $id): void
    {
        $this->db->query('INSERT INTO FicheFrais (idVisiteur,mois,nbJustificatifs,montantValide,dateModif,idEtat) VALUES (?, ?, 0, 0, ?, ?) ON DUPLICATE KEY UPDATE mois = VALUES(mois)', [$id,date('Ym'),date('Y-m-d'),'CR']);
    }
    public function forfaits(string $id, string $month): array
    {
        return $this->db->query('SELECT ff.id, ff.libelle, ff.montant, COALESCE(l.quantite,0) AS quantite FROM FraisForfait ff LEFT JOIN LigneFraisForfait l ON l.idFraisForfait = ff.id AND l.idVisiteur = ? AND l.mois = ? ORDER BY ff.id', [$id,$month])->getResultArray();
    }
    public function lines(string $id, string $month): array
    {
        return $this->db->table('LigneFraisHorsForfait')->where(['idVisiteur'=>$id,'mois'=>$month])->orderBy('date','DESC')->get()->getResultArray();
    }
    /** Verrouiller la fiche évite une modification concurrente pendant le contrôle de son état. */
    public function change(string $id, string $month, callable $operation): void
    {
        $this->db->transBegin();
        try {
            $fiche = $this->db->query('SELECT * FROM FicheFrais WHERE idVisiteur = ? AND mois = ? FOR UPDATE', [$id,$month])->getRowArray();
            if (!$fiche || !FraisRules::editable($month, $fiche['idEtat'])) throw new InvalidArgumentException('Cette fiche est absente ou fermée à la saisie.');
            $operation($this->db);
            $this->db->table('FicheFrais')->where(['idVisiteur'=>$id,'mois'=>$month])->update(['dateModif'=>date('Y-m-d')]);
            if (!$this->db->transStatus()) throw new RuntimeException('Échec de la transaction.');
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback(); throw $e;
        }
    }
    public function saveForfaits(string $id, string $month, array $quantities): void
    {
        $this->change($id,$month,static function($db) use ($id,$month,$quantities) {
            foreach ($quantities as $code=>$quantity) {
                $db->query('INSERT INTO LigneFraisForfait (idVisiteur,mois,idFraisForfait,quantite) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantite = VALUES(quantite)', [$id,$month,$code,$quantity]);
            }
        });
    }
    public function addLine(string $id, string $month, array $line): void
    {
        $this->change($id,$month,static function($db) use ($id,$month,$line) {
            $db->table('LigneFraisHorsForfait')->insert($line + ['idVisiteur'=>$id,'mois'=>$month]);
        });
    }
    public function removeLine(string $id, string $month, int $line): void
    {
        $this->change($id,$month,static function($db) use ($id,$month,$line) {
            $db->table('LigneFraisHorsForfait')->where(['id'=>$line,'idVisiteur'=>$id,'mois'=>$month])->delete();
            if ($db->affectedRows() !== 1) throw new InvalidArgumentException('Ligne introuvable pour cette fiche.');
        });
    }
    public function allFiches(): array
    {
        return $this->db->table('FicheFrais f')->select('f.*,v.nom,v.prenom')->join('Visiteur v','v.id=f.idVisiteur')->orderBy('f.mois','DESC')->get()->getResultArray();
    }
}
