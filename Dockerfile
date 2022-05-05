FROM composer:2.3.5 as composer

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


# ----------------------------------------------------------------------------------------------------------------------
FROM php:7.3-apache AS nt-common

ARG WORKDIR="/app"
EXPOSE 80

RUN apt update && apt install -y libzip-dev zlib1g-dev; \
    docker-php-ext-install mysqli pdo_mysql zip; \
    usermod -u 1000 www-data; \
    chown -R www-data:www-data /var/www/html; \
    a2enmod rewrite headers deflate expires

COPY ./build/apache.conf /etc/apache2/sites-enabled/000-default.conf

#Esto no es muy elegante, quizá mejor /var/www/neo-transposer. Habría que actualizarlo en el composer de prod
COPY --from=composer --chown=www-data ${WORKDIR} /var/www/html/


# ----------------------------------------------------------------------------------------------------------------------
FROM nt-common AS prod

#@todo PROD should have a different Composer run, without dev stuff
COPY ./build/php-prod.ini /usr/local/etc/php/conf.d/neo-transposer-prod.ini


# ----------------------------------------------------------------------------------------------------------------------
FROM nt-common AS dev

COPY ./build/php-dev.ini /usr/local/etc/php/conf.d/neo-transposer-dev.ini
#For some reason, xdebug sneaks his way into the prod image!
RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

#xdebug is not a core extension so it must be installed with PECL. 3.1 is the highest version supporting PHP 7.3
RUN pecl install xdebug-3.1.4 \
	&& docker-php-ext-enable xdebug