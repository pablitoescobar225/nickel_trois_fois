# Utilise une image de base officielle PHP avec Apache.
FROM php:8.1-apache

# Installer les extensions PHP nécessaires.
# docker-php-ext-install est la commande recommandée pour installer les extensions.
RUN docker-php-ext-install curl
# Si vous aviez d'autres extensions (par ex. pdo_mysql, gd), vous les ajouteriez ici:
# RUN docker-php-ext-install pdo_mysql gd

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier tous les fichiers de votre application
COPY . /var/www/html/

# S'assurer que les permissions sont correctes pour Apache
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Assurez-vous que le répertoire 'data' est accessible en écriture par l'utilisateur Apache (www-data)
RUN mkdir -p /var/www/html/data && chmod -R 777 /var/www/html/data

# Expose le port sur lequel Apache écoute.
EXPOSE 80