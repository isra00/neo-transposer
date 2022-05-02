FROM composer as composer

ARG WORKDIR="/app"

WORKDIR ${WORKDIR}

COPY composer.json composer.lock ${WORKDIR}/
RUN composer install  \
    --ignore-platform-reqs \
    --no-ansi \
#    --no-autoloader \
#    --no-dev \
    --no-interaction
#    --no-scripts

# Mola lo del multi-stage pero composer no puede comprobar si las extensiones php están presentes
# no-dev interesa??? no-scripts interesa??

COPY . ${WORKDIR}
RUN composer dump-autoload --optimize --classmap-authoritative

# @todo Descargar última versión de MMDB e ignorar/borrar la actual

FROM php:7.3-apache AS nt-common

ARG WORKDIR="/app"
EXPOSE 80

#Another way of installing extensions, recommended by <https://hub.docker.com/_/php>
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions mysqli pdo_mysql apcu zip; \
    usermod -u 1000 www-data; \
    a2enmod rewrite headers deflate expires

COPY ./build/apache.conf /etc/apache2/sites-enabled/000-default.conf

COPY --from=composer ${WORKDIR} /var/www/html/
RUN chown -R www-data:www-data /var/www/html

FROM nt-common AS prod

COPY ./build/php-prod.ini /usr/local/etc/php/conf.d/neo-transposer-prod.ini


FROM nt-common AS dev

COPY ./build/php-dev.ini /usr/local/etc/php/conf.d/neo-transposer-dev.ini
RUN install-php-extensions xdebug