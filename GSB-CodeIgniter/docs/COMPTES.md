# Compte rendu — comptes et mots de passe

Vérification du 15 octobre 2026.

## Conservation des comptes

Les **28 comptes d'origine, dont l'administrateur a00 et 27 visiteurs**, sont conservés : ID, login, nom, prénom, adresse, code postal, ville et date d'embauche. Le SQL de Site-GSB contient maintenant des INSERT actifs. Dans CodeIgniter, `database/visiteurs.json` contient les mêmes informations sans champ mot de passe ; `gsb:accounts` les ajoute à la base configurée et est lancé par l'installateur Debian. Un deuxième import laisse les comptes et leurs hash inchangés. Un conflit d'ID/login annule l'import sans écrasement.

Les anciennes fiches de frais ne sont pas importées par cette commande. Les données des comptes déjà présents ne sont pas écrasées.

## Hachage et connexion

Les anciens secrets courts et publics sont abandonnés. Les hash initiaux sont générés avec `password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)` ; les secrets aléatoires ne sont ni affichés ni conservés. Aucun de ces secrets ne sert de mot de passe d'examen. Les comptes sont inutilisables tant qu'un nouveau mot de passe n'est pas défini localement.

Les commandes de création et réinitialisation enregistrent uniquement le résultat de `password_hash($password, PASSWORD_DEFAULT)` dans `mdp VARCHAR(255)`. PHP génère le sel. En PHP 8.2, PASSWORD_DEFAULT utilise bcrypt. Le mot de passe saisi doit faire 12 à 72 octets et être confirmé ; choisir une longue phrase privée et différente pour chaque compte. Le hachage protège le stockage, pas la qualité d'un ancien secret court.

Les deux versions vérifient exclusivement avec `password_verify()`. Le repli en clair de Site-GSB a été supprimé. Les sessions ne contiennent pas le champ `mdp`. Les vues administrateur n'affichent ni mot de passe ni hash ; `debug-users.php` sélectionne uniquement les renseignements d'identité.

Pour une base existante, `gsb:accounts` remplace tout secret en clair par un hash de secret aléatoire inconnu. Pour Site-GSB, `php hash-passwords.php` réalise cette invalidation sans supprimer les identités. Les hash reconnus sont préservés : réinitialiser explicitement **tous les comptes réellement utilisés à l'examen**, même s'ils avaient déjà un hash.

## Commandes sur la VM CodeIgniter

Après déploiement du nouveau code, sans réimporter le schéma :

```bash
cd /var/www/gsb-ci
sudo -u www-data php spark gsb:accounts
sudo -u www-data php spark gsb:password
```

Saisir le login d'origine `admin` (ID `a00`), puis un nouveau secret privé et sa confirmation. Relancer `gsb:password` pour un visiteur, par exemple le login d'origine `lvillachane` (ID `a131`). Les ID/login du jeu d'origine sont publics et conservés ; les secrets choisis pour la démonstration restent privés. L'administration repose sur l'ID `a00`, configuré dans `gsb.adminIds`.

Pour créer un compte supplémentaire :

```bash
sudo -u www-data php spark gsb:user
```

Pour Site-GSB, depuis son dossier : `php account.php reset a00`, `php account.php reset a131`, ou `php account.php create ID LOGIN NOM PRENOM`. La réinitialisation ne modifie ni les informations du visiteur ni ses fiches.

La saisie est masquée sur un terminal Debian interactif, avec restauration de l'écho après la saisie. Sous Windows ou avec une entrée redirigée, un avertissement indique que le masquage n'est pas disponible. Ne pas mettre de secret en argument, dans GitHub, dans les captures ou dans les journaux. Conserver les secrets d'examen dans un gestionnaire de mots de passe local. `.env`, `inc/config.local.php` et les fichiers `*.local.sql/json/txt` sont ignorés par Git. Les exports contenant des hash restent privés.

Les anciennes valeurs peuvent subsister dans l'historique Git ; les supprimer du code courant ne purge pas l'historique. Elles sont compromises et ne sont plus à utiliser. Aucun nouveau secret de démonstration n'a été publié.

## Tests réellement réalisés

Le 15 octobre 2026, **300 contrôles réussis** avec PHP 8.2.29 et MySQL 8.4.7 sous Windows, dans deux bases jetables sur un serveur isolé lié à 127.0.0.1:13316. Les parcours HTTP ont utilisé le serveur de développement PHP, avec de nouveaux secrets de test aléatoires transmis par entrée standard, jamais affichés ni conservés dans le dépôt.

| Vérification | Résultat observé |
|---|---|
| Import des 28 comptes dans CodeIgniter, comparaison de tous les champs d'identité | Réussi |
| Second import, conservation des hash | Réussi |
| Import SQL Site-GSB avec 28 comptes | Réussi |
| Création et réinitialisation locales dans les deux versions | Réussi |
| Second changement : ancien secret refusé, nouveau accepté | Réussi dans les deux versions |
| Refus d'un mot de passe court et d'une confirmation différente (CodeIgniter) | Réussi |
| Refus d'un mot de passe court (Site-GSB) | Réussi |
| Connexion du visiteur d'origine a131 après réinitialisation | Réussi dans les deux versions |
| Connexion administrateur a00 après réinitialisation | Réussi dans les deux versions |
| Visiteur refusé dans l'administration, administrateur autorisé | Réussi dans les deux versions |
| Mauvais mot de passe CodeIgniter | Refusé |
| Mot de passe stocké en clair : connexion refusée, puis invalidation | Réussi dans les deux versions |
| Aucun secret dans la sortie de réinitialisation ou la page administrateur | Réussi |
| Commandes Site-GSB demandées par HTTP | Réponse 403 |
| Tests métier existants | 21 tests réussis |

Le script reproductible est `tests/accounts.php`. Il exige `GSB_ACCOUNTS_TEST_PORT`, un MySQL **isolé de test** avec root sans mot de passe, et des ports HTTP libres 18181/18182 ; il crée et supprime uniquement ses bases aux noms aléatoires. Ne pas le lancer contre un serveur de travail. Il nécessite les extensions mysqli, PDO MySQL, curl, intl et mbstring ainsi que les dépendances Composer.

**Restent à réaliser sur la VM Debian :** saisie masquée réelle, connexions via Apache depuis Windows, redémarrage et conservation des comptes. Définir les deux nouveaux secrets sur cette VM puis réaliser T02 et T15 de RECETTE.md ; noter date et résultats sans capturer les secrets. Les tests Windows ne constituent pas une preuve de démonstration sur la VM.
