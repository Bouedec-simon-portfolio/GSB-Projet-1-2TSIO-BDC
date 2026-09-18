<?php
namespace App\Controllers;
use App\Models\FicheModel;
use App\Libraries\FraisRules;
use CodeIgniter\Exceptions\PageNotFoundException;
class Frais extends BaseController
{
    protected $helpers = ['url','form'];
    private function uid(): string { return session('user')['id']; }
    public function index()
    {
        return view('fiches/index', ['fiches'=>(new FicheModel())->liste($this->uid())]);
    }
    public function create()
    {
        (new FicheModel())->createFor($this->uid());
        return redirect()->to(site_url('fiches/'.date('Ym')));
    }
    public function show(string $month)
    {
        $m = new FicheModel();
        if (!FraisRules::month($month) || !($fiche = $m->fiche($this->uid(),$month))) throw PageNotFoundException::forPageNotFound();
        $forfaits = $m->forfaits($this->uid(),$month); $lines = $m->lines($this->uid(),$month);
        $total = 0;
        foreach ($forfaits as $f) $total += (int)round((float)$f['montant']*100) * (int)$f['quantite'];
        foreach ($lines as $l) $total += (int)round((float)$l['montant']*100);
        return view('fiches/show', ['fiche'=>$fiche,'forfaits'=>$forfaits,'lines'=>$lines,'total'=>$total/100,'editable'=>FraisRules::editable($month,$fiche['idEtat'])]);
    }
    private function perform(string $month, callable $operation)
    {
        try {
            if (!FraisRules::month($month)) throw new \InvalidArgumentException('Mois invalide.');
            $operation(new FicheModel());
            return redirect()->to(site_url('fiches/'.$month))->with('success','Modification enregistrée.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->to(site_url('fiches/'.(FraisRules::month($month) ? $month : '')))->with('error',$e->getMessage());
        }
    }
    public function forfait(string $month)
    {
        return $this->perform($month, function($m) use ($month) {
            $ids = array_column($m->forfaits($this->uid(),$month),'id');
            $m->saveForfaits($this->uid(),$month,FraisRules::quantities($this->request->getPost('quantites'),$ids));
        });
    }
    public function add(string $month)
    {
        return $this->perform($month, function($m) use ($month) {
            $m->addLine($this->uid(),$month,FraisRules::line($this->request->getPost(),$month));
        });
    }
    public function delete(string $month, string $line)
    {
        return $this->perform($month, fn($m)=>$m->removeLine($this->uid(),$month,(int)$line));
    }
    public function admin()
    {
        $ids = array_map('trim',explode(',', (string)env('gsb.adminIds','a00')));
        if (!in_array($this->uid(),$ids,true)) return $this->response->setStatusCode(403)->setBody('Accès administrateur refusé.');
        return view('fiches/admin',['fiches'=>(new FicheModel())->allFiches()]);
    }
}
