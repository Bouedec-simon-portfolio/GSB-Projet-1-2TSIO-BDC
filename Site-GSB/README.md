# GSB — Gestion des frais

Application web réalisée dans le cadre du BTS SIO SLAM. Elle permet aux visiteurs médicaux de saisir leurs frais et de consulter leurs fiches. Un espace administrateur permet de consulter l'ensemble des fiches.

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
6. Créer le compte administrateur avec `php create-admin.php` depuis le dossier du projet.
7. Ouvrir `http://localhost/Site-GSB/` dans le navigateur.

Le fichier `inc/config.local.php` est ignoré par Git afin de ne pas publier les identifiants réels de la base. La configuration peut également être fournie avec les variables d'environnement `GSB_DB_HOST`, `GSB_DB_NAME`, `GSB_DB_USER`, `GSB_DB_PASS`, `GSB_ADMIN_IDS`, `GSB_ADMIN_CREATION_TOKEN` et `GSB_APP_ENV`.

## Comptes de démonstration

- Administrateur : `admin` / `admin1234`
Ce compte sert uniquement aux tests locaux. Il faut changer son mot de passe avant toute mise en ligne. Lors de la première connexion d'un ancien compte, son mot de passe en clair est remplacé par un hash sécurisé. La commande `php hash-passwords.php` permet de migrer immédiatement tous les comptes existants.

## Sécurité mise en place

- requêtes préparées PDO ;
- mots de passe gérés avec `password_hash()` et `password_verify()` ;
- renouvellement de l'identifiant de session après connexion ;
- cookies de session `HttpOnly` et `SameSite=Lax` ;
- contrôle des accès utilisateur et administrateur ;
- échappement HTML contre les attaques XSS ;
- validation des mois, dates, libellés et montants ;
- configuration locale exclue de Git.

## Organisation principale

- `inc/` : configuration, connexion PDO, authentification, fonctions métier et gabarits ;
- `GSB_V2.sql` : structure de la base et données de démonstration ;
- `saisie-frais.php` : saisie des frais ;
- `consultation-fiches.php` : consultation des fiches ;
- `tableau-bord.php` : tableau de bord après connexion ;
- `admin-fiches.php` : vue réservée à l'administrateur.

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
