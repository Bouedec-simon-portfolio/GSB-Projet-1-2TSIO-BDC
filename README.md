# GSB — Gestion des frais

Application web réalisée dans le cadre du BTS SIO SLAM. Elle permet aux visiteurs médicaux de saisir leurs frais (forfaitisés et hors forfait) et de consulter leurs fiches. Un espace administrateur, qui joue le rôle du service comptable, permet de clôturer, valider et rembourser les fiches.

## Technologies

- PHP 8 et PDO
- MySQL ou MariaDB
- HTML, CSS et JavaScript
- WAMP sous Windows ou une pile LAMP

## Installation locale avec WAMP

1. Cloner le dépôt dans le dossier `www` de WAMP.
2. Démarrer Apache et MySQL.
3. Importer `GSB_V2.sql` dans phpMyAdmin.
4. Copier `inc/config.local.example.php` vers `inc/config.local.php`.
5. Adapter les paramètres MySQL dans `inc/config.local.php`.
6. Depuis le dossier du projet, hasher les mots de passe des visiteurs avec `php hash-passwords.php`, puis créer le compte administrateur avec `php create-admin.php`.
7. Ouvrir `http://localhost/Site-GSB/` dans le navigateur.

Le fichier `inc/config.local.php` est ignoré par Git afin de ne pas publier les identifiants réels de la base. La configuration peut également être fournie avec les variables d'environnement `GSB_DB_HOST`, `GSB_DB_NAME`, `GSB_DB_USER`, `GSB_DB_PASS`, `GSB_ADMIN_IDS`, `GSB_ADMIN_CREATION_TOKEN` et `GSB_APP_ENV`.

## Fonctionnement

Cycle de vie d'une fiche de frais :

| État | Signification | Qui agit |
|------|---------------|----------|
| `CR` | Fiche créée, saisie en cours | Le visiteur saisit ses frais |
| `CL` | Saisie clôturée | L'admin peut refuser des lignes hors forfait |
| `VA` | Validée et mise en paiement | Le montant validé est calculé |
| `RB` | Remboursée | Fiche terminée |

- Créer la fiche d'un nouveau mois clôture automatiquement les fiches précédentes encore en cours.
- Le visiteur ne peut modifier une fiche qu'à l'état `CR`.
- Un frais hors forfait doit dater de moins d'un an et ne pas être dans le futur.
- Une ligne refusée garde son montant mais son libellé commence par « REFUSÉ : » ; elle n'est pas comptée dans le montant validé.
- Montant validé = somme (quantité × tarif) des frais forfaitisés + frais hors forfait non refusés.

## Déploiement sur le serveur de dev (ISPConfig)

1. Dans ISPConfig (`Sites`), créer un utilisateur de base de données, puis une base de données liée à son site. Le nom de la base reçoit un préfixe (par exemple `c50_gsb`).
2. Dans `Sites > Sites web`, choisir une version de PHP 8.
3. Dans phpMyAdmin, sélectionner la base puis importer `GSB_V2_serveur.sql`. Ne pas importer `GSB_V2.sql`, qui crée la base et des utilisateurs MySQL : cela demande des droits que l'on n'a pas sur le serveur.
4. Déposer les fichiers dans `/web` par FTP, ainsi qu'un `inc/config.local.php` avec le nom de la base, l'utilisateur et le mot de passe créés à l'étape 1, et `'app_env' => 'production'`.
5. Créer le compte admin : en SSH avec `php create-admin.php`, ou bien en mettant un `admin_creation_token` dans `config.local.php`, en ouvrant `create-admin.php?token=...` puis en remettant le jeton à vide.

Les mots de passe des visiteurs de démo sont hashés automatiquement à leur première connexion (ou avec `php hash-passwords.php` en SSH).

## Comptes de démonstration

- Administrateur : `admin` / `admin1234` (créé avec `php create-admin.php`)
- Visiteurs : jeu d'essai GSB importé avec `GSB_V2.sql`, par exemple `lvillachane` / `jux7g` ou `dandre` / `oppg5`

Ces comptes servent uniquement aux tests locaux. Il faut changer le mot de passe admin avant toute mise en ligne. Lors de la première connexion d'un ancien compte, son mot de passe en clair est remplacé par un hash sécurisé. La commande `php hash-passwords.php` permet de migrer immédiatement tous les comptes existants.

## Sécurité mise en place

- requêtes préparées PDO ;
- mots de passe gérés avec `password_hash()` et `password_verify()` ;
- renouvellement de l'identifiant de session après connexion ;
- cookies de session `HttpOnly` et `SameSite=Lax` ;
- jeton CSRF sur tous les formulaires ;
- contrôle des accès utilisateur et administrateur ;
- contrôle de l'état de la fiche en base avant chaque modification ;
- échappement HTML contre les attaques XSS ;
- validation des mois (01 à 12, pas de mois futur), des dates (`checkdate()`, moins d'un an), des quantités, libellés et montants ;
- configuration locale exclue de Git.

## Organisation principale

- `inc/` : configuration, connexion PDO, authentification, fonctions métier et gabarits ;
- `GSB_V2.sql` : structure de la base et données de démonstration ;
- `saisie-frais.php` : saisie des frais forfaitisés et hors forfait ;
- `consultation-fiches.php` : consultation des fiches ;
- `tableau-bord.php` : tableau de bord après connexion ;
- `admin-fiches.php` : gestion des fiches par l'administrateur (clôture, refus, validation, remboursement) ;
- `hash-passwords.php` : hashage des mots de passe en clair (à lancer après l'import SQL).

## Utilisation avec GitHub

GitHub conserve le code et son historique. GitHub Pages ne peut pas exécuter PHP ni MySQL : l'application doit tourner sous WAMP/LAMP ou chez un hébergeur compatible PHP/MySQL.

Pour envoyer une modification :

```bash
git status
git add .
git commit -m "Description des modifications"
git push origin main
```

Ne publiez jamais `inc/config.local.php`, une base de données réelle ou des identifiants de production.
