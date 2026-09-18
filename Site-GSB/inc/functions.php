<?php
require_once __DIR__ . '/db.php';

/**
 * Récupère toutes les fiches de frais d'un visiteur.
 *
 * @param string $visiteurId
 * @return array
 */
function getFichesByVisiteur(string $visiteurId): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT f.mois, f.nbJustificatifs, f.montantValide, f.dateModif, f.idEtat, e.libelle AS etatLibelle
         FROM FicheFrais f
         INNER JOIN Etat e ON f.idEtat = e.id
         WHERE f.idVisiteur = :id
         ORDER BY f.mois DESC'
    );
    $stmt->execute(['id' => $visiteurId]);
    return $stmt->fetchAll();
}

/**
 * Récupère une fiche de frais.
 *
 * @param string $visiteurId
 * @param string $mois
 * @return array|null
 */
function getFiche(string $visiteurId, string $mois): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM FicheFrais WHERE idVisiteur = :id AND mois = :mois');
    $stmt->execute(['id' => $visiteurId, 'mois' => $mois]);
    $fiche = $stmt->fetch();
    return $fiche ?: null;
}

/**
 * Crée une fiche de frais si elle n'existe pas.
 * Retourne vrai si la fiche existe ou a été créée.
 *
 * @param string $visiteurId
 * @param string $mois Format YYYYMM
 * @return bool
 */
function createFicheIfNotExists(string $visiteurId, string $mois): bool
{
    if (getFiche($visiteurId, $mois) !== null) {
        return true;
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'INSERT INTO FicheFrais (idVisiteur, mois, nbJustificatifs, montantValide, dateModif, idEtat)
         VALUES (:idVisiteur, :mois, 0, 0, :dateModif, :idEtat)'
    );
    return $stmt->execute([
        'idVisiteur' => $visiteurId,
        'mois' => $mois,
        'dateModif' => date('Y-m-d'),
        'idEtat' => 'CR',
    ]);
}

/**
 * Récupère les lignes hors forfait pour une fiche de frais.
 *
 * @param string $visiteurId
 * @param string $mois
 * @return array
 */
function getLignesHorsForfait(string $visiteurId, string $mois): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT id, libelle, date, montant
         FROM LigneFraisHorsForfait
         WHERE idVisiteur = :id AND mois = :mois
         ORDER BY date DESC, id DESC'
    );
    $stmt->execute(['id' => $visiteurId, 'mois' => $mois]);
    return $stmt->fetchAll();
}

/**
 * Ajoute une ligne hors forfait.
 *
 * @param string $visiteurId
 * @param string $mois
 * @param string $libelle
 * @param string $date
 * @param float $montant
 * @return bool
 */
function addLigneHorsForfait(string $visiteurId, string $mois, string $libelle, string $date, float $montant): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'INSERT INTO LigneFraisHorsForfait (idVisiteur, mois, libelle, date, montant)
         VALUES (:idVisiteur, :mois, :libelle, :date, :montant)'
    );
    $success = $stmt->execute([
        'idVisiteur' => $visiteurId,
        'mois' => $mois,
        'libelle' => $libelle,
        'date' => $date,
        'montant' => $montant,
    ]);

    if ($success) {
        updateFicheTotals($visiteurId, $mois);
    }

    return $success;
}

/**
 * Supprime une ligne hors forfait.
 *
 * @param int $ligneId
 * @param string $visiteurId
 * @param string $mois
 * @return bool
 */
function deleteLigneHorsForfait(int $ligneId, string $visiteurId, string $mois): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'DELETE FROM LigneFraisHorsForfait WHERE id = :id AND idVisiteur = :visiteur AND mois = :mois'
    );
    $success = $stmt->execute(['id' => $ligneId, 'visiteur' => $visiteurId, 'mois' => $mois]);

    if ($success) {
        updateFicheTotals($visiteurId, $mois);
    }

    return $success;
}

/**
 * Met à jour le montant validé et le nombre de justificatifs d'une fiche.
 *
 * @param string $visiteurId
 * @param string $mois
 */
function updateFicheTotals(string $visiteurId, string $mois): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS nb, COALESCE(SUM(montant), 0) AS total
         FROM LigneFraisHorsForfait
         WHERE idVisiteur = :id AND mois = :mois'
    );
    $stmt->execute(['id' => $visiteurId, 'mois' => $mois]);
    $row = $stmt->fetch();

    if ($row) {
        $stmt2 = $pdo->prepare(
            'UPDATE FicheFrais
             SET nbJustificatifs = :nb, montantValide = :total, dateModif = :dateModif
             WHERE idVisiteur = :id AND mois = :mois'
        );
        $stmt2->execute([
            'nb' => $row['nb'],
            'total' => $row['total'],
            'dateModif' => date('Y-m-d'),
            'id' => $visiteurId,
            'mois' => $mois,
        ]);
    }
}

/**
 * Récupère toutes les fiches de frais avec les infos visiteur (pour les admins).
 *
 * @return array
 */
function getAllFichesWithVisiteur(): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT f.idVisiteur, v.nom, v.prenom, f.mois, f.nbJustificatifs, f.montantValide, f.dateModif, f.idEtat, e.libelle AS etatLibelle
         FROM FicheFrais f
         INNER JOIN Visiteur v ON f.idVisiteur = v.id
         INNER JOIN Etat e ON f.idEtat = e.id
         ORDER BY v.nom, v.prenom, f.mois DESC'
    );
    $stmt->execute();
    return $stmt->fetchAll();
}
