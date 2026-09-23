# Installer et tester GSB sur Debian

## Comprendre le montage
Votre navigateur Windows envoie les demandes HTTP à Apache dans Debian. Apache exécute PHP et CodeIgniter ; les modèles accèdent à MariaDB sur cette même VM. GitHub stocke le code, il n'exécute pas cette application. `php spark serve` sert au développement ; pour l'exigence LAMP, la démonstration doit passer par Apache.

Cette VM sert à la recette locale. La grille de travail fournie mentionne aussi le serveur LAMP H02 pour la mise en production : votre VM ne remplace pas automatiquement le serveur demandé par l'enseignant.

## 1 Vérifier Debian et le réseau
Dans le terminal Debian :
```bash
cat /etc/os-release
ip -br address
ip route
getent hosts deb.debian.org
```
Debian doit accéder à Internet pour APT et Composer. PHP doit être au moins en version 8.2.
Configuration fréquente : adaptateur 1 NAT pour Internet, adaptateur 2 réseau privé hôte pour joindre Debian depuis Windows. Autre possibilité : réseau en pont si le réseau de l'établissement l'autorise. En NAT seul, une redirection de port est généralement nécessaire.
Avec VirtualBox en NAT seul : rediriger TCP du port 8080 de l'hôte vers le port 8080 invité (adresse hôte 127.0.0.1) ; utiliser alors http://127.0.0.1:8080/ comme URL de base. Ne pas mélanger cette URL avec celle de l'interface hôte privée.
En réseau privé hôte, repérer l'IP correspondante de Debian, par exemple 192.168.56.101. Cet exemple n'est pas votre IP garantie.

## 2 Récupérer le projet
Cloner la branche fournie dans la réponse, ou transférer l'archive sur Debian puis la décompresser dans votre dossier personnel. Ne pas lancer Composer en root.
```bash
sudo apt update
sudo apt install git
# Utiliser la branche migration-codeigniter-lamp du dépôt
 git clone --branch migration-codeigniter-lamp https://github.com/Bouedec-simon-portfolio/GSB-Projet-1-2TSIO-BDC.git
cd GSB-Projet-1-2TSIO-BDC/GSB-CodeIgniter
```
Si sudo n'est pas installé ou votre compte n'y a pas accès, l'administrateur de la VM doit installer sudo et autoriser votre compte ; se reconnecter ensuite. Ne pas publier de jeton GitHub pour contourner un problème de clone privé.

Avant d’installer, depuis GSB-CodeIgniter :
```bash
bash deploy/preflight-debian.sh
```
Ce diagnostic ne modifie pas la VM et n’affiche pas les mots de passe. Il signale Debian, les interfaces réseau, la route IPv4, le DNS, sudo, une installation existante et le port 8080. Corriger les points signalés avant de continuer. L’absence de PHP à ce stade est normale : l’installateur le fournira. Une résolution DNS réussie ne suffit pas à prouver l’accès à APT ou Composer.

## 3 Installer LAMP et GSB
Remplacer l'IP par celle que Windows peut joindre :
```bash
bash deploy/install-debian.sh http://192.168.56.101:8080/
```
Le script demande le mot de passe sudo, installe les paquets, vérifie PHP, installe les dépendances Composer, exécute les tests métier, crée une base neuve gsb_ci et son utilisateur limité, copie l'application sous /var/www/gsb-ci et configure Apache sur 8080. Il préserve la base gsbV2 et le site Apache du port 80.
Il refuse une installation existante, une base ou un utilisateur gsb_ci existant et un port 8080 déjà occupé. Si une étape échoue, conserver le message et diagnostiquer la cause ; ne pas supprimer automatiquement une base pour relancer.
Le mot de passe SQL aléatoire est dans /var/www/gsb-ci/.env, lisible par root et www-data seulement. Ne jamais envoyer ce fichier dans GitHub ni dans une capture.

## 4 Créer les comptes
```bash
cd /var/www/gsb-ci
sudo -u www-data php spark gsb:user
```
Créer un premier visiteur avec un ID comme v001 et un identifiant simon. Choisir un mot de passe personnel d'au moins 12 caractères. La saisie est visible dans le terminal : ne pas filmer cette étape.
Relancer pour v002 afin de tester la séparation entre utilisateurs, puis pour a00 si vous voulez tester l'administration. Aucun mot de passe de démonstration public n'est imposé.

## 5 Ouvrir depuis Windows
Ouvrir l'URL choisie, puis se connecter. Dans PowerShell, si nécessaire :
```powershell
Test-NetConnection 192.168.56.101 -Port 8080
```
Si cela échoue mais que Debian ouvre le site localement, vérifier le mode réseau de la VM et les règles du pare-feu. Autoriser seulement le port utile depuis le réseau de test ; ne pas désactiver le pare-feu entier. Ne pas ouvrir MySQL/3306 vers Windows : PHP et MariaDB communiquent localement.

## 6 Vérifier le projet
Créer la fiche du mois, saisir 2 repas et 10 km, ajouter un frais hors forfait de 12,50 €, puis consulter. Avec les tarifs fournis, le total déclaré attendu est 68,70 €. Le montant validé reste 0 tant qu'aucun processus comptable ne le valide.
Exécuter ensuite les scénarios de RECETTE.md. Conserver les résultats observés et les captures datées.

## Diagnostic
```bash
bash deploy/check-debian.sh
sudo apache2ctl configtest
sudo tail -n 50 /var/log/apache2/gsb-ci-error.log
sudo ls /var/www/gsb-ci/writable/logs
```
Erreur 500 : consulter les logs et vérifier les extensions PHP et les droits de writable.
404 sur /fiches : vérifier rewrite, AllowOverride All et DocumentRoot vers public.
Erreur SQL : vérifier le nom de base, le compte local et .env sans en partager le mot de passe.
Redirection vers une mauvaise IP : corriger app.baseURL dans .env. Une IP obtenue par DHCP peut changer ; conserver une adresse stable dans le réseau privé hôte si possible.

## Mise à jour après modifications
Faire les modifications dans votre copie de travail, utiliser Git, exécuter les tests puis mettre à jour la copie déployée. Ne jamais écraser .env ou writable et ne pas réimporter schema.sql sur une base utilisée.
```bash
# Depuis GSB-CodeIgniter dans votre dossier personnel
composer install --no-dev --prefer-dist --optimize-autoloader
php tests/rules.php
sudo rsync -a --exclude=.git --exclude=.env --exclude=writable --exclude=tests --exclude=docs --exclude=deploy ./ /var/www/gsb-ci/
sudo chown -R root:www-data /var/www/gsb-ci/app /var/www/gsb-ci/public /var/www/gsb-ci/vendor
sudo systemctl reload apache2
```

## Anciennes données et serveur de l'établissement
La base fournie est neuve. Pour récupérer vos anciennes données, exporter d'abord gsbV2 et vérifier les doublons de login, les mots de passe hachés et les contraintes. Ne pas exécuter l'ancien script GSB_V2.sql : il commence par DROP DATABASE.
Pour H02, adapter avec l'enseignant le chemin, les droits, la base et l'URL : le script installe une VM dédiée, pas un serveur partagé d'établissement. L'application proposée en HTTP est destinée à une VM locale ; une exposition publique demande HTTPS et une configuration d'exploitation adaptée.

## Ce que vérifie l’intégration automatisée
Le workflow lance les parcours HTTP dans un conteneur Debian 12 avec Apache et PHP 8.2, relié à un service MariaDB de test. Il contrôle ensuite les lignes SQL enregistrées, la suppression et l’absence d’insertion invalide. Les fichiers privés doivent répondre 403 ou 404.
Ce test ne lance pas install-debian.sh et ne remplace pas la recette du réseau, des droits sudo, du redémarrage et des services dans votre VM. Consulter le résultat GitHub Actions du commit testé avant d’en annoncer la réussite.
