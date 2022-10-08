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
    mkdir /var/www/nt-sessions; \
    usermod -u 1000 www-data; \
    chown -R www-data:www-data /var/www; \
    a2enmod rewrite headers deflate expires

COPY infrastructure/apache.conf /etc/apache2/sites-enabled/000-default.conf

#Esto no es muy elegante, quizá mejor /var/www/neo-transposer. Habría que actualizarlo en el composer de prod
COPY --from=composer --chown=www-data ${WORKDIR} /var/www/html/

# ----------------------------------------------------------------------------------------------------------------------
FROM nt-common AS prod

#@todo PROD should have a different Composer run, without dev stuff
COPY infrastructure/php-prod.ini /usr/local/etc/php/conf.d/neo-transposer-prod.ini

#This reduces 1/2 of the app's folder size
RUN rm -rf /var/www/html/.git; \
    rm -rf /var/www/html/apps/NeoTransposerWeb/public/static/img/source

# Install the New Relic APM PHP agent & daemon. To enable and configure it, add a /usr/local/etc/php/conf.d/newrelic.ini
# https://docs.newrelic.com/docs/apm/agents/php-agent/advanced-installation/docker-other-container-environments-install-php-agent/#install-same-container
RUN \
  curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-10.0.0.312-linux.tar.gz | tar -C /tmp -zx && \
  export NR_INSTALL_USE_CP_NOT_LN=1 && \
  export NR_INSTALL_SILENT=1 && \
  /tmp/newrelic-php5-*/newrelic-install install && \
  rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*

# Install the New Relic APM PHP agent & daemon. To enable and configure it, add a /usr/local/etc/php/conf.d/newrelic.ini
# https://docs.newrelic.com/docs/apm/agents/php-agent/advanced-installation/docker-other-container-environments-install-php-agent/#install-same-container
RUN \
  curl -L https://download.newrelic.com/php_agent/archive/10.0.0.312/newrelic-php5-10.0.0.312-linux.tar.gz | tar -C /tmp -zx && \
  export NR_INSTALL_USE_CP_NOT_LN=1 && \
  export NR_INSTALL_SILENT=1 && \
  /tmp/newrelic-php5-*/newrelic-install install && \
  rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*

# ----------------------------------------------------------------------------------------------------------------------
FROM nt-common AS dev

COPY infrastructure/php-dev.ini /usr/local/etc/php/conf.d/neo-transposer-dev.ini

#For some reason, xdebug sneaks his way into the prod image!
#xdebug is not a core extension so it must be installed with PECL. 3.1 is the highest version supporting PHP 7.3
RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && curl -s https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer | php -- --quiet \
    && chmod +x composer.phar \
    && mv composer.phar /usr/bin \
    && pecl install xdebug-3.1.4 \
	&& docker-php-ext-enable xdebug \
    && apt-get -y update \
    && apt-get install -y libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl