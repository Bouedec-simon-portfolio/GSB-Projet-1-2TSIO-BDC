<?php
namespace App\Libraries;
/** Saisie locale Debian, sans écho et sans mot de passe dans les arguments. */
function readLocalPassword(string $prompt): string
{
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('Commande locale uniquement.');
    }
    $hidden = PHP_OS_FAMILY !== 'Windows' && function_exists('shell_exec')
        && function_exists('stream_isatty') && stream_isatty(STDIN);
    $mode = $hidden ? trim((string) shell_exec('stty -g')) : '';
    if ($hidden && $mode === '') {
        throw new RuntimeException('Impossible de masquer la saisie.');
    }
    if (!$hidden) fwrite(STDERR, "Attention : saisie visible ou entrée redirigée ; ne pas enregistrer ce terminal.\n");
    fwrite(STDOUT, $prompt . ': ');
    try {
        if ($hidden) shell_exec('stty -echo');
        $line = fgets(STDIN);
        if ($line === false) throw new RuntimeException('Saisie interrompue.');
        return rtrim($line, "\r\n");
    } finally {
        if ($hidden) shell_exec('stty ' . escapeshellarg($mode));
        fwrite(STDOUT, PHP_EOL);
    }
}

function newLocalPasswordHash(): string
{
    $password = readLocalPassword('Nouveau mot de passe (12 à 72 octets, phrase longue recommandée)');
    $confirmation = readLocalPassword('Confirmation');
    if (strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0") || trim($password) === '' || $password !== $confirmation) {
        throw new RuntimeException('Longueur invalide ou confirmation différente. Aucun changement.');
    }
    return password_hash($password, PASSWORD_DEFAULT);
}
