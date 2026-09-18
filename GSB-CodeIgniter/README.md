# GSB sous CodeIgniter 4

Application pédagogique de gestion des frais, issue du projet GSB de Simon Bouëdec.
Version préparée pour Debian LAMP. Lire **docs/INSTALLATION.md** en premier.

## Contenu
- Connexion avec mots de passe hachés, sessions, limitation des tentatives et déconnexion POST.
- Fiches mensuelles personnelles et consultation des mois passés.
- Saisie des quantités forfaitaires, ajout et suppression des frais hors forfait.
- Contrôle du mois courant et de l'état CR côté serveur, transactions en base.
- Consultation administrative en lecture seule (ID a00 par défaut).
- Protection CSRF, échappement HTML et requêtes paramétrées.

Le total déclaré est calculé séparément du montant validé par le comptable.
La validation comptable, le dépôt de justificatifs et l'import automatique des anciennes données ne font pas partie de cette version.
Les anciennes pages vitrines restent dans Site-GSB ; elles ne sont pas exposées par cette application de frais.

## Prérequis
PHP >= 8.2, extensions intl, mbstring et mysqli, Composer, MariaDB/MySQL, Apache avec rewrite.
Squelette officiel appstarter 4.7.4 ; framework fixé à 4.7.4 dans composer.json.
Le framework et ses dépendances sont téléchargés par Composer lors de l'installation.
Il n'y a pas de vendor ni de composer.lock fourni tant que Composer n'a pas été exécuté.
Après la première installation réussie, conserver composer.lock dans Git pour figer aussi les dépendances transitives.

## Vérification
`php tests/rules.php` vérifie les règles métier.
`python3 tests/http_smoke.py` est un test HTTP d'intégration pour une base de test dédiée (voir le fichier et le workflow).
Consulter docs/RECETTE.md pour les scénarios et le statut réel des vérifications.

## Source et suivi
Le dossier Site-GSB original est conservé. Le nouveau code se trouve dans GSB-CodeIgniter.
Le dossier d'examen est une préparation à compléter après les tests dans la VM : captures d'écran, numéro de groupe, contributions réelles, résultats et dates.
