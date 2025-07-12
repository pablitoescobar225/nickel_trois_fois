FROM php:8.1-apache

# 1) Met à jour la liste, installe libcurl-dev et pkg-config
RUN apt-get update && \
    apt-get install -y libcurl4-openssl-dev pkg-config && \
    rm -rf /var/lib/apt/lists/*

# 2) Compile et active l'extension cURL
RUN docker-php-ext-install curl

# 3) Active mod_rewrite si besoin
RUN a2enmod rewrite

# 4) Copie votre code
COPY . /var/www/html/

# 5) Expose le port Apache
EXPOSE 80

# 6) Lancement d’Apache
CMD ["apache2-foreground"]
