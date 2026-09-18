#!/usr/bin/env bash
set -u
printf '\nVersion Debian\n'; cat /etc/os-release
printf '\nAdresses réseau\n'; ip -br address; ip route
printf '\nPHP\n'; php -v; php -m
printf '\nServices\n'; systemctl is-active apache2 mariadb
printf '\nPorts HTTP\n'; ss -ltn | grep -E ':80 |:8080 '
printf '\nHTTP local\n'; curl -I --max-time 5 http://127.0.0.1:8080/connexion
