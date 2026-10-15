<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/** Import local des identités, jamais des anciens mots de passe. */
class ImportVisitors extends BaseCommand
{
    protected $group = 'GSB';
    protected $name = 'gsb:accounts';
    protected $description = 'Conserver les 28 comptes d’origine et invalider les secrets en clair.';

    public function run(array $params)
    {
        $db = db_connect();
        $db->transBegin();
        try {
            $rows = json_decode(file_get_contents(ROOTPATH . 'database/visiteurs.json'), true, 512, JSON_THROW_ON_ERROR);
            $created = 0;
            foreach ($rows as $row) {
                $existing = $db->table('Visiteur')->where('id', $row['id'])->get()->getRowArray();
                if ($existing) {
                    // Ne pas écraser une identité ou un login déjà utilisé différemment.
                    if ($existing['login'] !== $row['login']) throw new \RuntimeException('Conflit de compte.');
                    continue;
                }
                $row['mdp'] = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                if (!$db->table('Visiteur')->insert($row)) throw new \RuntimeException('Import impossible.');
                $created++;
            }
            $invalidated = 0;
            foreach ($db->table('Visiteur')->select('id, mdp')->get()->getResultArray() as $user) {
                if (password_get_info($user['mdp'])['algoName'] !== 'unknown') continue;
                $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                if (!$db->table('Visiteur')->where('id', $user['id'])->update(['mdp' => $hash])) throw new \RuntimeException('Migration impossible.');
                $invalidated++;
            }
            if (!$db->transStatus()) throw new \RuntimeException('Transaction impossible.');
            $db->transCommit();
            CLI::write("$created comptes ajoutés ; $invalidated secrets en clair invalidés. Identités existantes préservées.");
            CLI::write('Activer a00 et un visiteur avec php spark gsb:password (saisir le login, pas l’ID).');
            return EXIT_SUCCESS;
        } catch (\Throwable $e) {
            $db->transRollback();
            CLI::error('Import annulé. Vérifier la base, les ID et les doublons de login. Aucun secret affiché.');
            return EXIT_ERROR;
        }
    }
}
