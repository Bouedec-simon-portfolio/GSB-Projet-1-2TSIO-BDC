#!/usr/bin/env bash
# Installation dédiée pour une VM pédagogique Debian, à lancer avec son utilisateur normal.
set -euo pipefail
umask 027
if [[ $EUID -eq 0 ]]; then echo 'Lancer avec votre utilisateur habituel, pas avec root.' >&2; exit 1; fi
GSB_SOURCE=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
GSB_TARGET=/var/www/gsb-ci
GSB_URL=${1:-}
if [[ ! "$GSB_URL" =~ ^http://[a-zA-Z0-9.-]+:8080/$ ]]; then
  echo 'Usage : bash deploy/install-debian.sh http://IP_DE_LA_VM:8080/' >&2; exit 1
fi
. /etc/os-release
if [[ "$ID" != debian ]]; then echo 'Ce script est prévu pour Debian.' >&2; exit 1; fi
sudo -v
if sudo test -e "$GSB_TARGET" || sudo test -e /etc/apache2/sites-available/gsb-ci.conf; then
  echo 'Installation déjà présente : arrêt sans écrasement. Consulter docs/INSTALLATION.md.' >&2; exit 1
fi
sudo apt-get update
sudo apt-get install -y apache2 mariadb-server php libapache2-mod-php php-cli php-mysql php-intl php-mbstring php-xml php-curl php-zip composer unzip rsync openssl
php -r 'exit(version_compare(PHP_VERSION,"8.2.0",">=") ? 0 : 1);' || { echo 'PHP 8.2 minimum requis.' >&2; exit 1; }
sudo systemctl enable --now mariadb apache2
if [[ $(sudo mariadb -Nse "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='gsb_ci'") != 0 ]] || [[ $(sudo mariadb -Nse "SELECT COUNT(*) FROM mysql.user WHERE User='gsb_ci'") != 0 ]]; then
 echo 'Base ou compte gsb_ci existant : arrêt pour préserver les données.' >&2; exit 1
fi
if sudo ss -ltn '( sport = :8080 )' | tail -n +2 | grep -q .; then
 echo 'Port 8080 déjà occupé : arrêt. Consulter la configuration avant de continuer.' >&2; exit 1
fi
cd "$GSB_SOURCE"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php tests/rules.php
sudo install -d -m 755 "$GSB_TARGET"
sudo rsync -a --exclude=.git --exclude=.env --exclude=tests --exclude=docs --exclude=deploy ./ "$GSB_TARGET/"
GSB_PASSWORD=$(openssl rand -hex 24)
sudo mariadb <<SQL
CREATE DATABASE gsb_ci CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'gsb_ci'@'localhost' IDENTIFIED BY '$GSB_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE ON gsb_ci.* TO 'gsb_ci'@'localhost';
SQL
sudo mariadb gsb_ci < database/schema.sql
# Ne pas afficher le mot de passe dans les journaux.
{
 printf "CI_ENVIRONMENT = production\napp.baseURL = '%s'\napp.indexPage = ''\napp.appTimezone = 'Europe/Paris'\n" "$GSB_URL"
 printf "database.default.hostname = localhost\ndatabase.default.database = gsb_ci\ndatabase.default.username = gsb_ci\ndatabase.default.password = '%s'\ndatabase.default.DBDriver = MySQLi\ngsb.adminIds = 'a00'\n" "$GSB_PASSWORD"
} | sudo tee "$GSB_TARGET/.env" >/dev/null
unset GSB_PASSWORD
sudo chown -R root:www-data "$GSB_TARGET"
sudo find "$GSB_TARGET" -type d -exec chmod 755 {} +
sudo find "$GSB_TARGET" -type f -exec chmod 644 {} +
sudo chmod 640 "$GSB_TARGET/.env"
sudo chown -R www-data:www-data "$GSB_TARGET/writable"
sudo find "$GSB_TARGET/writable" -type d -exec chmod 770 {} +
sudo find "$GSB_TARGET/writable" -type f -exec chmod 660 {} +
sudo tee /etc/apache2/sites-available/gsb-ci.conf >/dev/null <<'APACHE'
Listen 8080
<VirtualHost *:8080>
    ServerName gsb.local
    DocumentRoot /var/www/gsb-ci/public
    <Directory /var/www/gsb-ci/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/gsb-ci-error.log
    CustomLog ${APACHE_LOG_DIR}/gsb-ci-access.log combined
</VirtualHost>
APACHE
sudo a2enmod rewrite
sudo a2ensite gsb-ci
sudo apache2ctl configtest
sudo systemctl reload apache2
printf '\nInstallation terminée. Adresse : %s\n' "$GSB_URL"
printf 'Créer maintenant vos comptes :\ncd /var/www/gsb-ci\nsudo -u www-data php spark gsb:user\n'
