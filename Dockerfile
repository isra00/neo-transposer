FROM composer:2.8.1 AS composer

ARG WORKDIR="/app"

WORKDIR ${WORKDIR}

COPY composer.json composer.lock ${WORKDIR}/
RUN composer install \
    --ignore-platform-reqs \
    --no-ansi \
    --no-interaction \
    --no-scripts

COPY . ${WORKDIR}
RUN composer dump-autoload --optimize --classmap-authoritative

# Same as above without dev packages, for the prod image
FROM composer:2.8.1 AS composer-prod

ARG WORKDIR="/app"

WORKDIR ${WORKDIR}

COPY composer.json composer.lock ${WORKDIR}/
RUN composer install \
    --no-dev \
    --ignore-platform-reqs \
    --no-ansi \
    --no-interaction \
    --no-scripts

COPY . ${WORKDIR}
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ----------------------------------------------------------------------------------------------------------------------
# Pinned to >= 8.3.36 for PHP2Shell (CVE-2026-109618, CVE-2026-109619)
FROM php:8.3.36-apache AS nt-common

EXPOSE 80
ENV TZ="Europe/Madrid"

RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev zlib1g-dev; \
    rm -rf /var/lib/apt/lists/*; \
    docker-php-ext-install mysqli pdo_mysql zip; \
    mkdir /var/www/nt-sessions; \
    usermod -u 1000 www-data; \
    chown -R www-data:www-data /var/www; \
    a2enmod rewrite headers deflate expires

COPY ./build/apache.conf /etc/apache2/sites-enabled/000-default.conf

# The app is copied last in each stage below, so a code change doesn't rebuild the
# New Relic / xdebug layers.

# ----------------------------------------------------------------------------------------------------------------------
FROM nt-common AS prod

COPY ./build/php-prod.ini /usr/local/etc/php/conf.d/neo-transposer-prod.ini

# Install the New Relic APM PHP agent & daemon. To enable and configure it, add a /usr/local/etc/php/conf.d/newrelic.ini
# https://docs.newrelic.com/docs/apm/agents/php-agent/advanced-installation/docker-other-container-environments-install-php-agent/#install-same-container
RUN \
  curl -L https://download.newrelic.com/php_agent/archive/10.19.0.9/newrelic-php5-10.19.0.9-linux.tar.gz | tar -C /tmp -zx && \
  export NR_INSTALL_USE_CP_NOT_LN=1 && \
  export NR_INSTALL_SILENT=1 && \
  /tmp/newrelic-php5-*/newrelic-install install && \
  rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*

ARG WORKDIR="/app"
COPY --from=composer-prod --chown=www-data ${WORKDIR} /var/www/html/

#This reduces 1/2 of the app's folder size
RUN rm -rf /var/www/html/.git; \
    rm -rf /var/www/html/web/static/img/source

# ----------------------------------------------------------------------------------------------------------------------
FROM nt-common AS dev

COPY ./build/php-dev.ini /usr/local/etc/php/conf.d/neo-transposer-dev.ini

#xdebug is not a core extension so it must be installed with PECL
RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && curl -fsSL https://getcomposer.org/download/2.8.1/composer.phar -o /usr/bin/composer.phar \
    && chmod +x /usr/bin/composer.phar \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

ARG WORKDIR="/app"
COPY --from=composer --chown=www-data ${WORKDIR} /var/www/html/
