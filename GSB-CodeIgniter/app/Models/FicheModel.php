<?php
namespace App\Models;
use CodeIgniter\Model;
use App\Libraries\FraisRules;
use InvalidArgumentException;
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
        $month = date('Ym');
        if ($this->fiche($id, $month)) return;

        $this->db->table('FicheFrais')->insert([
            'idVisiteur' => $id,
            'mois' => $month,
            'nbJustificatifs' => 0,
            'montantValide' => 0,
            'dateModif' => date('Y-m-d'),
            'idEtat' => 'CR',
        ]);
    }
    public function forfaits(string $id, string $month): array
    {
        return $this->db->query('SELECT ff.id, ff.libelle, ff.montant, COALESCE(l.quantite,0) AS quantite FROM FraisForfait ff LEFT JOIN LigneFraisForfait l ON l.idFraisForfait = ff.id AND l.idVisiteur = ? AND l.mois = ? ORDER BY ff.id', [$id,$month])->getResultArray();
    }
    public function lines(string $id, string $month): array
    {
        return $this->db->table('LigneFraisHorsForfait')->where(['idVisiteur'=>$id,'mois'=>$month])->orderBy('date','DESC')->get()->getResultArray();
    }
    private function checkEditable(string $id, string $month): void
    {
        $fiche = $this->fiche($id, $month);
        if (!$fiche || !FraisRules::editable($month, $fiche['idEtat'])) {
            throw new InvalidArgumentException('Cette fiche est absente ou fermée à la saisie.');
        }
    }
    private function touch(string $id, string $month): void
    {
        $this->db->table('FicheFrais')->where(['idVisiteur'=>$id,'mois'=>$month])->update(['dateModif'=>date('Y-m-d')]);
    }
    public function saveForfaits(string $id, string $month, array $quantities): void
    {
        $this->checkEditable($id, $month);
        foreach ($quantities as $code=>$quantity) {
            $this->db->query('INSERT INTO LigneFraisForfait (idVisiteur,mois,idFraisForfait,quantite) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantite = VALUES(quantite)', [$id,$month,$code,$quantity]);
        }
        $this->touch($id, $month);
    }
    public function addLine(string $id, string $month, array $line): void
    {
        $this->checkEditable($id, $month);
        $this->db->table('LigneFraisHorsForfait')->insert($line + ['idVisiteur'=>$id,'mois'=>$month]);
        $this->touch($id, $month);
    }
    public function removeLine(string $id, string $month, int $line): void
    {
        $this->checkEditable($id, $month);
        $this->db->table('LigneFraisHorsForfait')->where(['id'=>$line,'idVisiteur'=>$id,'mois'=>$month])->delete();
        if ($this->db->affectedRows() !== 1) throw new InvalidArgumentException('Ligne introuvable pour cette fiche.');
        $this->touch($id, $month);
    }
    public function allFiches(): array
    {
        return $this->db->table('FicheFrais f')->select('f.*,v.nom,v.prenom')->join('Visiteur v','v.id=f.idVisiteur')->orderBy('f.mois','DESC')->get()->getResultArray();
    }
}
