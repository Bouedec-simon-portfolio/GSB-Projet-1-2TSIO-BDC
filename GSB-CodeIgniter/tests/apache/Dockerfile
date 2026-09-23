# Serveur de test jetable uniquement. Aucun accès à la VM de l'étudiant.
FROM debian:bookworm-slim
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    apache2 libapache2-mod-php php-cli php-mysql php-intl php-mbstring php-xml php-curl \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite && a2dissite 000-default \
    && printf 'Listen 8080\n' > /etc/apache2/ports.conf
COPY gsb-test.conf /etc/apache2/sites-available/gsb-test.conf
RUN a2ensite gsb-test
EXPOSE 8080
CMD ["bash", "-c", "mkdir -p /var/www/gsb-ci/writable/{cache,logs,session,uploads,debugbar} && chown -R www-data:www-data /var/www/gsb-ci/writable && apache2ctl -D FOREGROUND"]
