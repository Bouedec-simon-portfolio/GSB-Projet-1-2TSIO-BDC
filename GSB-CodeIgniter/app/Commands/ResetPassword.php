<?php
namespace App\Commands;

use App\Models\VisiteurModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/** Commande locale uniquement : aucune route Web ne permet cette opération. */
class ResetPassword extends BaseCommand
{
    protected $group = 'GSB';
    protected $name = 'gsb:password';
    protected $description = 'Changer le mot de passe d’un compte existant depuis le serveur.';

    public function run(array $params)
    {
        $login = trim(CLI::prompt('Identifiant de connexion (pas l’ID)'));
        $model = new VisiteurModel();
        $user = $model->where('login', $login)->first();
        if (!$user) {
            CLI::error('Compte introuvable. Créez-le avec php spark gsb:user si nécessaire.');
            return EXIT_ERROR;
        }
        require_once APPPATH . 'Libraries/password-console.php';
        try { $hash = \App\Libraries\newLocalPasswordHash(); }
        catch (\RuntimeException $e) { CLI::error($e->getMessage()); return EXIT_ERROR; }
        // On ne change ni l’identifiant, ni les droits, ni les fiches de frais.
        if (!$model->update($user['id'], ['mdp' => $hash])) {
            CLI::error('Échec de l’enregistrement. Consultez les journaux du serveur.');
            return EXIT_ERROR;
        }
        CLI::write('Mot de passe modifié. Connectez-vous avec votre identifiant.');
        return EXIT_SUCCESS;
    }
}
