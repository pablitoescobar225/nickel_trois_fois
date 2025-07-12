# 1. Image de base PHP 8.1 avec Apache
FROM php:8.1-apache

# 2. Installer les dépendances pour cURL et nettoyer le cache APT
RUN apt-get update && \
    apt-get install -y libcurl4-openssl-dev pkg-config && \
    rm -rf /var/lib/apt/lists/*

# 3. Compiler et activer l'extension cURL
RUN docker-php-ext-install curl

# 4. Activer mod_rewrite
RUN a2enmod rewrite

# 5. Configurer l'affichage et le log des erreurs PHP directement via une conf interne
RUN { \
    echo "display_errors=On"; \
    echo "display_startup_errors=On"; \
    echo "error_reporting=E_ALL"; \
    echo "log_errors=On"; \
    echo "error_log=/dev/stderr"; \
} > /usr/local/etc/php/conf.d/docker-php-debug.ini

# 6. Copier tout le code de votre projet dans le répertoire web
COPY . /var/www/html/

# 7. Régler les permissions pour Apache
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# 8. Exposer le port 80 (Render le détecte automatiquement)
EXPOSE 80

# 9. Commande de démarrage d'Apache en avant-plan
CMD ["apache2-foreground"]
