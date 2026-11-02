<?php
require_once __DIR__ . '/db.php';

/*
 * ============================================================
 *  Fonctions de contrôle des données saisies
 * ============================================================
 */

/**
 * Vérifie qu'un mois est au format AAAAMM avec un mois entre 01 et 12.
 * Exemple : "202611" est valide, "202613" ne l'est pas.
 *
 * @param string $mois
 * @return bool
 */
function moisValide(string $mois): bool
{
    return preg_match('/^[0-9]{4}(0[1-9]|1[0-2])$/', $mois) === 1;
}

/**
 * Vérifie qu'une date au format AAAA-MM-JJ existe vraiment
 * (par exemple le 31 février est refusé grâce à checkdate()).
 *
 * @param string $date
 * @return bool
 */
function dateValide(string $date): bool
{
    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date)) {
        return false;
    }
    [$annee, $mois, $jour] = explode('-', $date);
    return checkdate((int) $mois, (int) $jour, (int) $annee);
}

/**
 * Vérifie la règle de gestion GSB : un frais hors forfait doit dater
 * de moins d'un an et ne pas être dans le futur.
 *
 * @param string $date Date déjà validée par dateValide()
 * @return bool
 */
function dateDansLaDerniereAnnee(string $date): bool
{
    $aujourdHui = date('Y-m-d');
    $ilYaUnAn = date('Y-m-d', strtotime('-1 year'));
    // Les dates au format AAAA-MM-JJ peuvent se comparer comme des chaînes.
    return $date <= $aujourdHui && $date >= $ilYaUnAn;
}

/**
 * Une fiche n'est modifiable par le visiteur que si elle est
 * à l'état "CR" (fiche créée, saisie en cours).
 *
 * @param array|null $fiche
 * @return bool
 */
function ficheModifiable(?array $fiche): bool
{
    return $fiche !== null && $fiche['idEtat'] === 'CR';
}

/*
 * ============================================================
 *  Fiches de frais
 * ============================================================
 */

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
 * Récupère une fiche de frais (avec le libellé de son état).
 *
 * @param string $visiteurId
 * @param string $mois
 * @return array|null
 */
function getFiche(string $visiteurId, string $mois): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT f.*, e.libelle AS etatLibelle
         FROM FicheFrais f
         INNER JOIN Etat e ON f.idEtat = e.id
         WHERE f.idVisiteur = :id AND f.mois = :mois'
    );
    $stmt->execute(['id' => $visiteurId, 'mois' => $mois]);
    $fiche = $stmt->fetch();
    return $fiche ?: null;
}

/**
 * Crée une fiche de frais si elle n'existe pas.
 *
 * À la création :
 *  - les fiches précédentes encore "en cours" du visiteur sont clôturées
 *    (règle GSB : on ne saisit que sur le mois en cours) ;
 *  - une ligne à 0 est créée pour chaque frais forfaitisé (ETP, KM, NUI, REP).
 *
 * @param string $visiteurId
 * @param string $mois Format AAAAMM
 * @return bool Vrai si la fiche existe ou a été créée
 */
function createFicheIfNotExists(string $visiteurId, string $mois): bool
{
    if (getFiche($visiteurId, $mois) !== null) {
        return true;
    }

    $pdo = getPDO();

    // 1. Clôturer les anciennes fiches encore en cours.
    $stmt = $pdo->prepare(
        "UPDATE FicheFrais SET idEtat = 'CL', dateModif = :dateModif
         WHERE idVisiteur = :id AND mois < :mois AND idEtat = 'CR'"
    );
    $stmt->execute(['dateModif' => date('Y-m-d'), 'id' => $visiteurId, 'mois' => $mois]);

    // 2. Créer la nouvelle fiche.
    $stmt = $pdo->prepare(
        "INSERT INTO FicheFrais (idVisiteur, mois, nbJustificatifs, montantValide, dateModif, idEtat)
         VALUES (:idVisiteur, :mois, 0, 0, :dateModif, 'CR')"
    );
    $ok = $stmt->execute([
        'idVisiteur' => $visiteurId,
        'mois' => $mois,
        'dateModif' => date('Y-m-d'),
    ]);

    // 3. Créer les lignes de frais forfaitisés avec une quantité à 0.
    if ($ok) {
        $stmt = $pdo->prepare(
            'INSERT INTO LigneFraisForfait (idVisiteur, mois, idFraisForfait, quantite)
             VALUES (:idVisiteur, :mois, :idFrais, 0)'
        );
        foreach (getFraisForfait() as $frais) {
            $stmt->execute(['idVisiteur' => $visiteurId, 'mois' => $mois, 'idFrais' => $frais['id']]);
        }
    }

    return $ok;
}

/**
 * Change l'état d'une fiche (CR, CL, VA ou RB).
 *
 * @param string $visiteurId
 * @param string $mois
 * @param string $nouvelEtat
 * @return bool
 */
function changerEtatFiche(string $visiteurId, string $mois, string $nouvelEtat): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'UPDATE FicheFrais SET idEtat = :etat, dateModif = :dateModif
         WHERE idVisiteur = :id AND mois = :mois'
    );
    return $stmt->execute([
        'etat' => $nouvelEtat,
        'dateModif' => date('Y-m-d'),
        'id' => $visiteurId,
        'mois' => $mois,
    ]);
}

/**
 * Valide une fiche : le montant validé devient le total
 * (forfait + hors forfait non refusé) et l'état passe à "VA".
 *
 * @param string $visiteurId
 * @param string $mois
 * @return bool
 */
function validerFiche(string $visiteurId, string $mois): bool
{
    $total = getTotalFraisForfait($visiteurId, $mois) + getTotalHorsForfait($visiteurId, $mois);

    $pdo = getPDO();
    $stmt = $pdo->prepare(
        "UPDATE FicheFrais SET montantValide = :total, idEtat = 'VA', dateModif = :dateModif
         WHERE idVisiteur = :id AND mois = :mois"
    );
    return $stmt->execute([
        'total' => $total,
        'dateModif' => date('Y-m-d'),
        'id' => $visiteurId,
        'mois' => $mois,
    ]);
}

/**
 * Met à jour le nombre de justificatifs et la date de modification d'une fiche.
 * Le montant validé n'est PAS calculé ici : c'est le comptable qui le fixe
 * au moment de la validation (voir validerFiche()).
 *
 * @param string $visiteurId
 * @param string $mois
 */
function updateFicheTotals(string $visiteurId, string $mois): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM LigneFraisHorsForfait WHERE idVisiteur = :id AND mois = :mois'
    );
    $stmt->execute(['id' => $visiteurId, 'mois' => $mois]);
    $nb = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'UPDATE FicheFrais SET nbJustificatifs = :nb, dateModif = :dateModif
         WHERE idVisiteur = :id AND mois = :mois'
    );
    $stmt->execute([
        'nb' => $nb,
        'dateModif' => date('Y-m-d'),
        'id' => $visiteurId,
        'mois' => $mois,
    ]);
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

/**
 * Récupère le nom et le prénom d'un visiteur.
 *
 * @param string $visiteurId
 * @return array|null
 */
function getVisiteur(string $visiteurId): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, nom, prenom FROM Visiteur WHERE id = :id');
    $stmt->execute(['id' => $visiteurId]);
    $visiteur = $stmt->fetch();
    return $visiteur ?: null;
}

/*
 * ============================================================
 *  Frais forfaitisés (étape, kilomètres, nuitée, repas)
 * ============================================================
 */

/**
 * Liste des frais forfaitisés et de leur tarif.
 *
 * @return array
 */
function getFraisForfait(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, libelle, montant FROM FraisForfait ORDER BY id');
    return $stmt->fetchAll();
}

/**
 * Récupère les quantités de frais forfaitisés d'une fiche,
 * avec le libellé et le tarif de chaque frais.
 *
 * @param string $visiteurId
 * @param string $mois
 * @return array
 */
function getLignesFraisForfait(string $visiteurId, string $mois): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT f.id, f.libelle, f.montant, COALESCE(l.quantite, 0) AS quantite
         FROM FraisForfait f
         LEFT JOIN LigneFraisForfait l
           ON l.idFraisForfait = f.id AND l.idVisiteur = :id AND l.mois = :mois
         ORDER BY f.id'
    );
    $stmt->execute(['id' => $visiteurId, 'mois' => $mois]);
    return $stmt->fetchAll();
}

/**
 * Enregistre les quantités de frais forfaitisés d'une fiche.
 * REPLACE INTO crée la ligne si elle n'existe pas, sinon la remplace.
 *
 * @param string $visiteurId
 * @param string $mois
 * @param array $quantites Tableau du type ['ETP' => 2, 'KM' => 150, ...]
 */
function majFraisForfait(string $visiteurId, string $mois, array $quantites): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'REPLACE INTO LigneFraisForfait (idVisiteur, mois, idFraisForfait, quantite)
         VALUES (:idVisiteur, :mois, :idFrais, :quantite)'
    );
    foreach ($quantites as $idFrais => $quantite) {
        $stmt->execute([
            'idVisiteur' => $visiteurId,
            'mois' => $mois,
            'idFrais' => $idFrais,
            'quantite' => $quantite,
        ]);
    }
    updateFicheTotals($visiteurId, $mois);
}

/**
 * Total des frais forfaitisés d'une fiche (quantité × tarif).
 *
 * @param string $visiteurId
 * @param string $mois
 * @return float
 */
function getTotalFraisForfait(string $visiteurId, string $mois): float
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(l.quantite * f.montant), 0)
         FROM LigneFraisForfait l
         INNER JOIN FraisForfait f ON l.idFraisForfait = f.id
         WHERE l.idVisiteur = :id AND l.mois = :mois'
    );
    $stmt->execute(['id' => $visiteurId, 'mois' => $mois]);
    return (float) $stmt->fetchColumn();
}

/*
 * ============================================================
 *  Frais hors forfait
 * ============================================================
 */

/** Préfixe ajouté au libellé d'une ligne refusée par le comptable. */
const PREFIXE_REFUS = 'REFUSÉ : ';

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
 * Indique si une ligne hors forfait a été refusée.
 *
 * @param array $ligne
 * @return bool
 */
function ligneRefusee(array $ligne): bool
{
    return str_starts_with($ligne['libelle'], PREFIXE_REFUS);
}

/**
 * Total des frais hors forfait d'une fiche, sans les lignes refusées.
 *
 * @param string $visiteurId
 * @param string $mois
 * @return float
 */
function getTotalHorsForfait(string $visiteurId, string $mois): float
{
    $total = 0.0;
    foreach (getLignesHorsForfait($visiteurId, $mois) as $ligne) {
        if (!ligneRefusee($ligne)) {
            $total += (float) $ligne['montant'];
        }
    }
    return $total;
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
 * La condition sur idVisiteur empêche de supprimer la ligne d'un autre visiteur.
 *
 * @param int $ligneId
 * @param string $visiteurId
 * @param string $mois
 * @return bool Vrai si une ligne a bien été supprimée
 */
function deleteLigneHorsForfait(int $ligneId, string $visiteurId, string $mois): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'DELETE FROM LigneFraisHorsForfait WHERE id = :id AND idVisiteur = :visiteur AND mois = :mois'
    );
    $stmt->execute(['id' => $ligneId, 'visiteur' => $visiteurId, 'mois' => $mois]);

    // rowCount() donne le nombre de lignes supprimées : 0 si l'id ne correspond à rien.
    if ($stmt->rowCount() === 0) {
        return false;
    }

    updateFicheTotals($visiteurId, $mois);
    return true;
}

/**
 * Refuse une ligne hors forfait (action du comptable) :
 * on ajoute "REFUSÉ : " au début du libellé, la ligne n'est plus comptée.
 * Le libellé est coupé à 100 caractères pour respecter la taille de la colonne.
 *
 * @param int $ligneId
 * @param string $visiteurId
 * @param string $mois
 * @return bool
 */
function refuserLigneHorsForfait(int $ligneId, string $visiteurId, string $mois): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        "UPDATE LigneFraisHorsForfait
         SET libelle = LEFT(CONCAT(:prefixe, libelle), 100)
         WHERE id = :id AND idVisiteur = :visiteur AND mois = :mois
           AND libelle NOT LIKE CONCAT(:prefixe2, '%')"
    );
    $stmt->execute([
        'prefixe' => PREFIXE_REFUS,
        'prefixe2' => PREFIXE_REFUS,
        'id' => $ligneId,
        'visiteur' => $visiteurId,
        'mois' => $mois,
    ]);
    return $stmt->rowCount() === 1;
}
