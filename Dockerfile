# Utilise une image de base officielle PHP avec Apache.
# php:8.1-apache est un bon choix car il est basé sur Debian et inclut Apache avec PHP-FPM déjà configuré.
FROM php:8.1-apache

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier tous les fichiers de votre application (du répertoire local où se trouve Dockerfile)
# vers le répertoire web par défaut d'Apache dans le conteneur.
# Le . ici fait référence au répertoire courant de votre dépôt sur le système de fichiers hôte
# /var/www/html est le DocumentRoot par défaut d'Apache dans cette image.
COPY . /var/www/html/

# Si vous avez des modules Apache spécifiques ou des configurations, vous les ajouteriez ici.
# Par exemple, pour activer mod_rewrite si vous utilisez des URL réécrites:
# RUN a2enmod rewrite

# S'assurer que les permissions sont correctes pour Apache
# www-data est l'utilisateur Apache par défaut dans cette image.
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Les fichiers de log seront probablement écrits dans le répertoire /data/
# Assurez-vous que le répertoire 'data' est accessible en écriture par l'utilisateur Apache (www-data)
# C'est crucial pour file_put_contents dans index.php.
# Le chemin /var/www/html/data est l'emplacement de votre dossier data dans le conteneur.
RUN chmod -R 777 /var/www/html/data || true # 777 est permissif, à ajuster en production
                                        # Le '|| true' permet de ne pas faire échouer le build
                                        # si le dossier 'data' n'existe pas encore.
                                        # Il est préférable de le créer si vous ne l'avez pas.
RUN mkdir -p /var/www/html/data && chmod -R 777 /var/www/html/data


# Expose le port sur lequel Apache écoute.
# Render le détectera automatiquement, mais c'est une bonne pratique de l'inclure.
EXPOSE 80

# La commande de démarrage est déjà gérée par l'image de base php:*-apache,
# elle démarre Apache et PHP-FPM. Vous n'avez pas besoin d'ajouter CMD ici.