# Tornalyx — PHP plano sobre Apache.
# El front controller vive en SGDM/public/index.php y el routing depende
# del .htaccess (mod_rewrite + mod_headers).
FROM php:8.2-apache

# Extensiones PHP:
#   - pdo_mysql: conexión a la base de datos (config/database.php).
#   curl, openssl y json ya vienen compilados y habilitados en la imagen
#   oficial (los usan WhatsAppSender, el Mailer SMTP por TLS y las respuestas
#   JSON respectivamente), por eso no hace falta instalarlos.
RUN docker-php-ext-install pdo_mysql

# El .htaccess usa reescritura de URLs y cabeceras de seguridad.
RUN a2enmod rewrite headers

# Define un ServerName global para evitar el warning AH00558 al arrancar
# (Apache no puede determinar el FQDN dentro del contenedor).
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# php.ini de producción (oculta errores al cliente, etc.).
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Render inyecta $PORT en tiempo de ejecución; Apache debe escuchar ahí.
# El default 80 permite correr la imagen localmente sin variables extra.
ENV PORT=80

# El DocumentRoot apunta al front controller, no a la raíz del repo.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/SGDM/public

# VirtualHost y puerto parametrizados con las variables de entorno de arriba
# (Apache expande ${PORT} y ${APACHE_DOCUMENT_ROOT} desde el entorno del proceso).
COPY SGDM/docker/apache/ports.conf /etc/apache2/ports.conf
COPY SGDM/docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Código de la aplicación.
COPY . /var/www/html/

# storage/throttle debe ser escribible por Apache (control de fuerza bruta).
RUN mkdir -p /var/www/html/SGDM/storage/throttle \
    && chown -R www-data:www-data /var/www/html/SGDM/storage

EXPOSE 80
