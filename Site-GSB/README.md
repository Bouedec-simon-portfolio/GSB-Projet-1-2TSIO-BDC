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
6. Depuis `Site-GSB`, activer l'administrateur avec `php account.php reset a00`, puis un visiteur avec `php account.php reset a131`. Choisir deux nouveaux mots de passe privés.
7. Ouvrir `http://localhost/Site-GSB/` dans le navigateur.

Le fichier `inc/config.local.php` est ignoré par Git afin de ne pas publier les identifiants réels de la base. La configuration peut également être fournie avec les variables d'environnement `GSB_DB_HOST`, `GSB_DB_PORT`, `GSB_DB_NAME`, `GSB_DB_USER`, `GSB_DB_PASS`, `GSB_ADMIN_IDS` et `GSB_APP_ENV`. Le compte MySQL et son mot de passe sont à configurer localement : le SQL ne crée plus de comptes MySQL avec des secrets publics.

## Comptes conservés et préparation de la démonstration

Les 28 comptes du SQL d'origine sont créés, avec leurs ID, login, nom, prénom, adresse, code postal, ville et date d'embauche. Les anciens mots de passe sont supprimés. Chaque compte possède un hash PHP d'un secret aléatoire non conservé : il faut définir un nouveau mot de passe localement avant utilisation.

Dans le terminal de la VM, depuis `Site-GSB` :

```bash
php account.php reset a00
php account.php reset a131
```

La connexion utilise le **login**, et non l'ID : `a00` correspond au login d'origine `admin` ; `a131` correspond à `lvillachane`. Ce sont des identifiants du jeu d'origine, pas des secrets de démonstration. Choisir deux phrases de passe différentes, longues et privées (12 à 72 octets ; 72 maximum pour éviter la troncature bcrypt). La commande demande une confirmation, masque la saisie dans un terminal Debian et n'affiche jamais le secret. Sous Windows ou avec une entrée redirigée, un avertissement signale que la saisie n'est pas masquée.

Créer un compte supplémentaire : `php account.php create ID LOGIN NOM PRENOM` ; seuls ces renseignements sont passés en arguments, jamais le mot de passe. L'ID `a00` est administrateur par défaut. Aucun formulaire Web de gestion des comptes n'est ajouté. `php create-admin.php` reste un alias de réinitialisation de `a00`, sans mot de passe par défaut.

**Base déjà existante :** sauvegarder localement, puis lancer `php hash-passwords.php` avant de servir l'application. Cette commande remplace les secrets en clair par des hash de nouveaux secrets aléatoires non conservés ; elle préserve les hash déjà reconnus et les informations des visiteurs. Réinitialiser ensuite les comptes utilisés à l'examen, même s'ils possèdent déjà un hash : hacher un ancien mot de passe court ne le rend pas robuste. Ne pas réimporter `GSB_V2.sql` sur une base utilisée : son `DROP DATABASE` supprime les données.

Pour la version CodeIgniter déployée sur Debian, suivre [le compte rendu et les commandes VM](../GSB-CodeIgniter/docs/COMPTES.md).

## Sécurité mise en place

- requêtes préparées PDO ;
- mots de passe hachés avec `password_hash(PASSWORD_DEFAULT)`, connexion exclusivement avec `password_verify()` (aucun repli sur une comparaison en clair) ;
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

Les secrets d'examen restent dans un gestionnaire de mots de passe local, jamais dans le README, un argument de commande, une capture ou un export SQL publié. Les anciennes valeurs peuvent rester dans l'historique Git : elles sont considérées compromises et ne doivent jamais être réutilisées.
