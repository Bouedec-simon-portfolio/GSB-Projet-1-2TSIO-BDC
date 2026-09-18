<?php
namespace App\Commands;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\VisiteurModel;
class CreateUser extends BaseCommand
{
    protected $group = 'GSB';
    protected $name = 'gsb:user';
    protected $description = 'Créer un visiteur, sans mot de passe publié dans le dépôt.';
    public function run(array $params)
    {
        $id = CLI::prompt('ID (1 à 4 caractères, a00 pour admin)');
        $login = CLI::prompt('Identifiant (1 à 20 caractères)');
        $nom = CLI::prompt('Nom'); $prenom = CLI::prompt('Prénom');
        $password = CLI::prompt('Mot de passe (12 caractères minimum, saisie visible dans ce terminal)');
        if (!preg_match('/^[a-zA-Z0-9]{1,4}$/D',$id) || !preg_match('/^[a-zA-Z0-9._-]{1,20}$/D',$login) || mb_strlen($nom)<1 || mb_strlen($nom)>30 || mb_strlen($prenom)<1 || mb_strlen($prenom)>30 || strlen($password)<12) {
            CLI::error('Valeurs invalides. Aucun compte créé.'); return EXIT_ERROR;
        }
        $m = new VisiteurModel();
        if ($m->find($id) || $m->where('login',$login)->first()) { CLI::error('ID ou identifiant déjà utilisé.'); return EXIT_ERROR; }
        $m->insert(['id'=>$id,'login'=>$login,'nom'=>$nom,'prenom'=>$prenom,'mdp'=>password_hash($password,PASSWORD_DEFAULT)]);
        CLI::write('Compte créé.'); return EXIT_SUCCESS;
    }
}
