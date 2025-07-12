# Utilise l’image officielle PHP 8.1 avec Apache
FROM php:8.1-apache

# Copie tout le code dans le répertoire web d’Apache
COPY . /var/www/html/

# Active l’extension cURL (nécessaire pour toTG4)
RUN docker-php-ext-install curl

# Expose le port par défaut
EXPOSE 10000

# Lancement d’Apache en avant-plan
CMD ["apache2-foreground"]
