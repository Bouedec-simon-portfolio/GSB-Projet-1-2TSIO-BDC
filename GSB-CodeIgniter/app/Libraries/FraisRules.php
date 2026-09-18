<?php
namespace App\Libraries;
use InvalidArgumentException;
/** Règles métier indépendantes des formulaires et de la base. */
class FraisRules
{
    public static function month(string $month): bool
    {
        return preg_match('/^20[0-9]{2}(0[1-9]|1[0-2])$/D', $month) === 1;
    }
    public static function editable(string $month, string $state, ?string $today = null): bool
    {
        return self::month($month) && $month === ($today ?? date('Ym')) && $state === 'CR';
    }
    public static function line(array $data, string $month): array
    {
        $date = $data['date'] ?? ''; $label = $data['libelle'] ?? ''; $amount = $data['montant'] ?? '';
        if (!is_string($date) || !is_string($label) || !is_string($amount)) throw new InvalidArgumentException('Saisie invalide.');
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date || $d->format('Ym') !== $month || $date > date('Y-m-d')) {
            throw new InvalidArgumentException('La date doit être réelle, dans le mois de la fiche et non future.');
        }
        $label = trim($label);
        if ($label === '' || mb_strlen($label) > 100) throw new InvalidArgumentException('Le libellé doit contenir de 1 à 100 caractères.');
        $amount = str_replace(',', '.', trim($amount));
        if (!preg_match('/^[0-9]{1,6}(\.[0-9]{1,2})?$/D', $amount) || (float)$amount <= 0) {
            throw new InvalidArgumentException('Montant attendu : de 0,01 à 999 999,99 €, avec deux décimales au maximum.');
        }
        return ['date'=>$date,'libelle'=>$label,'montant'=>$amount];
    }
    public static function quantities($data, array $ids): array
    {
        if (!is_array($data) || count($data) !== count($ids) || array_diff(array_keys($data), $ids)) {
            throw new InvalidArgumentException('Liste des forfaits invalide.');
        }
        foreach ($ids as $id) {
            if (!isset($data[$id]) || !is_string($data[$id]) || !preg_match('/^[0-9]{1,5}$/D', $data[$id])) {
                throw new InvalidArgumentException('Les quantités doivent être des entiers entre 0 et 99 999.');
            }
        }
        return array_map('intval', $data);
    }
}
