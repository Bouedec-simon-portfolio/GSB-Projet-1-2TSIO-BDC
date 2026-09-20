#!/usr/bin/env bash
# Diagnostic en lecture seule, avant installation. Ne lit aucun mot de passe.
set -u
GSB_ISSUES=0
check_command() {
  if command -v "$1" >/dev/null 2>&1; then
    printf 'OK : commande %s disponible\n' "$1"
  else
    printf 'À installer : %s\n' "$1"
    GSB_ISSUES=$((GSB_ISSUES + 1))
  fi
}
printf '=== Diagnostic GSB avant installation ===\n'
if [[ -r /etc/os-release ]]; then
  . /etc/os-release
  printf 'Système : %s\n' "${PRETTY_NAME:-inconnu}"
  if [[ ${ID:-} != debian ]]; then
    printf 'Attention : install-debian.sh exige Debian.\n'
    GSB_ISSUES=$((GSB_ISSUES + 1))
  fi
fi
if [[ $EUID -eq 0 ]]; then
  printf 'Utiliser un compte normal autorisé à utiliser sudo pour installer.\n'
  GSB_ISSUES=$((GSB_ISSUES + 1))
fi
check_command sudo
check_command ip
if command -v ip >/dev/null 2>&1; then
  printf '\nAdresses et routes :\n'
  ip -br address
  ip route
  if ! ip route show default | grep -q '^default'; then
    printf 'Aucune route IPv4 par défaut : vérifier le réseau de la VM.\n'
    GSB_ISSUES=$((GSB_ISSUES + 1))
  fi
fi
if command -v getent >/dev/null 2>&1 && getent hosts deb.debian.org >/dev/null; then
  printf '\nOK : résolution DNS de deb.debian.org\n'
else
  printf '\nÉchec DNS : vérifier le réseau et le DNS dans Debian.\n'
  GSB_ISSUES=$((GSB_ISSUES + 1))
fi
printf '\nPHP installé : '
if command -v php >/dev/null 2>&1; then php -r 'echo PHP_VERSION, PHP_EOL;'; else printf 'non (sera installé par le script)\n'; fi
for GSB_PATH in /var/www/gsb-ci /etc/apache2/sites-available/gsb-ci.conf; do
  if [[ -e "$GSB_PATH" ]]; then
    printf 'Installation existante détectée : %s. Ne pas relancer une installation neuve.\n' "$GSB_PATH"
    GSB_ISSUES=$((GSB_ISSUES + 1))
  fi
done
if command -v ss >/dev/null 2>&1 && ss -H -ltn '( sport = :8080 )' | grep -q .; then
  printf 'Le port 8080 est déjà utilisé.\n'
  GSB_ISSUES=$((GSB_ISSUES + 1))
fi
printf '\nPoints à résoudre : %s\n' "$GSB_ISSUES"
printf 'Ce diagnostic ne vérifie pas les comptes SQL ni le droit sudo ; le programme d’installation les vérifiera.\n'
printf 'La résolution DNS ne garantit pas le téléchargement des paquets : APT et Composer seront vérifiés pendant l’installation.\n'
[[ $GSB_ISSUES -eq 0 ]]
