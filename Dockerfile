FROM php:8.3-apache AS nt-common

ARG WORKDIR="/app"
EXPOSE 80
ENV TZ="Europe/Madrid"

RUN apt update && apt install -y libzip-dev zlib1g-dev; \
    docker-php-ext-install mysqli pdo_mysql zip; \
    mkdir /var/www/nt-sessions; \
    usermod -u 1000 www-data; \
    chown -R www-data:www-data /var/www; \
    a2enmod rewrite headers deflate expires

COPY ./build/apache.conf /etc/apache2/sites-enabled/000-default.conf

# ----------------------------------------------------------------------------------------------------------------------
FROM nt-common AS prod

#@todo PROD should have a different Composer run, without dev stuff
COPY ./build/php-prod.ini /usr/local/etc/php/conf.d/neo-transposer-prod.ini

#This reduces 1/2 of the app's folder size
RUN rm -rf /var/www/html/.git; \
    rm -rf /var/www/html/web/static/img/source

# Install the New Relic APM PHP agent & daemon. To enable and configure it, add a /usr/local/etc/php/conf.d/newrelic.ini
# https://docs.newrelic.com/docs/apm/agents/php-agent/advanced-installation/docker-other-container-environments-install-php-agent/#install-same-container
RUN \
  curl -L https://download.newrelic.com/php_agent/archive/10.19.0.9/newrelic-php5-10.19.0.9-linux.tar.gz | tar -C /tmp -zx && \
  export NR_INSTALL_USE_CP_NOT_LN=1 && \
  export NR_INSTALL_SILENT=1 && \
  /tmp/newrelic-php5-*/newrelic-install install && \
  rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*

# ----------------------------------------------------------------------------------------------------------------------
FROM nt-common AS dev

COPY ./build/php-dev.ini /usr/local/etc/php/conf.d/neo-transposer-dev.ini

#For some reason, xdebug sneaks his way into the prod image!
#xdebug is not a core extension so it must be installed with PECL. 3.1 is the highest version supporting PHP 7.3
#@todo Update XDebug version to latest; ensure it's not in prod image.
RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && curl -s https://getcomposer.org/download/2.8.1/composer.phar > composer.phar \
    && chmod +x composer.phar \
    && mv composer.phar /usr/bin \
    && pecl install xdebug \
	&& docker-php-ext-enable xdebug \
    && apt-get install -y libicu-dev unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl
