.PHONY: build
.DEFAULT_GOAL := build-dev

#Quedarían pendientes targets para composer, pero es un coñazo

# This should be run on post-commit, right? Otherwise serve would fail bc commit name has changed.
build-dev:
	sh update_mmdb.sh
	docker build --target dev -t transposer:`git rev-parse --short HEAD`-dev .
	docker tag transposer:`git rev-parse --short HEAD`-dev transposer:latest-dev

build-prod:
	sh update_mmdb.sh
	docker build --target prod -t transposer:`git rev-parse --short HEAD`-prod .
	docker tag transposer:`git rev-parse --short HEAD`-prod transposer:prod

# NT_PROFILER debería ser 0 en start (para test)
start: OPTIONAL_VOLUME=
#--user es para que si en Docker se escriben archivos, no se escriban como root sino como el usuario actual. ¿O www-data?
start-local: OPTIONAL_VOLUME=-v ${CURDIR}:/var/www/html --user $(id -u):$(id -g)

# Esto pasaría a ser docker compose up excluyendo MySQL.
start start-local: stop
	docker tag transposer:`git rev-parse --short HEAD`-dev transposer:for-prod
	docker start transposer-dev || docker run --rm -dit -p 80:80 \
		-e APP_KEY \
		-e APP_ENV \
		-e NT_DB_HOST \
		-e NT_DB_USER \
		-e NT_DB_PASSWORD \
		-e NT_DB_DATABASE \
		-e NT_DB_DATABASE_INTEGRATION \
		-e NT_RECAPTCHA_SECRET \
		-e NT_ADMIN_USERNAME \
		-e NT_ADMIN_PASSWORD \
		-e NT_ANALYTICS_ID \
		-e NT_DEBUG \
		-e NT_PROFILER \
		-e NT_TRUSTED_PROXIES \
		-e SENTRY_LARAVEL_DSN \
		--add-host=host.docker.internal:172.17.0.1 \
		--name transposer-dev \
		$(OPTIONAL_VOLUME) \
		transposer:latest-dev

start-db-local:
	@docker stop nt-mysql || true
	docker run --rm -dit -p 3306:3306 --name nt-mysql --platform linux/x86_64 -e MYSQL_ROOT_PASSWORD=${NT_DB_PASSWORD} -v nt-mysql:/var/lib/mysql mysql:8.3
	@echo "Waiting for MySQL to be ready..."
	@for i in $$(seq 1 30); do docker exec nt-mysql mysql -uroot -p${NT_DB_PASSWORD} -e "SELECT 1" >/dev/null 2>&1 && break; sleep 2; done

import-prod-db:
	docker exec nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} -e "SET GLOBAL log_bin_trust_function_creators = 1; CREATE DATABASE IF NOT EXISTS ${NT_DB_DATABASE} COLLATE 'utf8_general_ci'"
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE} < ntprod.sql

start-db-for-test:
	@docker stop nt-mysql || true
	docker run --rm -dit -p 3306:3306 --name nt-mysql --platform linux/x86_64 -e MYSQL_ROOT_PASSWORD=${NT_DB_PASSWORD} mysql:8.3
	@echo "Waiting for MySQL to be ready..."
	@for i in $$(seq 1 30); do docker exec nt-mysql mysql -uroot -p${NT_DB_PASSWORD} -e "SELECT 1" >/dev/null 2>&1 && break; sleep 2; done
	@if [ -z "$NT_DB_USER" ] || [ -z "$NT_DB_PASSWORD" ] || [ -z "$NT_DB_DATABASE" ] || [ -z "$NT_DB_DATABASE_INTEGRATION" ]; then echo "Environment variables NT_DB_USER, NT_DB_PASSWORD, NT_DB_DATABASE and NT_DB_DATABASE_INTEGRATION must be set before calling this recipe" >&2; exit 1; fi
	docker exec    nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} -e "CREATE DATABASE ${NT_DB_DATABASE} COLLATE 'utf8_general_ci'"
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE} < create_tables.sql
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE} < song_data.sql
	docker exec    nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} -e "CREATE DATABASE ${NT_DB_DATABASE_INTEGRATION} COLLATE 'utf8_general_ci'"
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE_INTEGRATION} < create_tables.sql

#No need to delete it after stopping since it's run with --rm
stop:
	@docker stop transposer-dev || true

stop-all: stop
	@docker stop nt-mysql || true

test:
	@docker exec transposer-dev bash -c "mv /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini.disabled /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini 2>/dev/null; true"
	@docker exec nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} -e "CREATE DATABASE IF NOT EXISTS ${NT_DB_DATABASE_INTEGRATION} COLLATE 'utf8_general_ci'"
	docker exec -t transposer-dev vendor/bin/codecept run --skip acceptance --coverage-html --coverage-xml
	@sed "s@\/var\/www\/html@\/\/wsl$$\/Ubuntu\/var\/www\/vhosts\/transposer.local@g" tests/_output/coverage.xml > tests/_output/coverage.xml.tmp && mv tests/_output/coverage.xml.tmp tests/_output/coverage.xml || true
	docker exec -t transposer-dev php artisan app:test-all-transpositions
	docker exec -t transposer-dev php artisan app:test-song-urls

test-acceptance:
	docker start selenium-chrome || docker run -d --name selenium-chrome --platform linux/amd64 --add-host=host.docker.internal:172.17.0.1 -p 4444:4444 -p 7900:7900 --shm-size=2g selenium/standalone-chrome
	sleep 5
	docker exec -t transposer-dev php /var/www/html/vendor/bin/codecept run acceptance

get-test-outputs:
	 docker cp transposer-dev:/var/www/html/tests/_output .

clean:
	rm -r cache/twig/*
	rm -r cache/profiler/*

xdebug-off:
	docker exec transposer-dev bash -c "mv /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini.disabled 2>/dev/null; apache2ctl graceful"
	@echo "Xdebug disabled."

xdebug-on:
	docker exec transposer-dev bash -c "mv /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini.disabled /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini 2>/dev/null; apache2ctl graceful"
	@echo "Xdebug enabled."

composer:
	@echo "To run composer, type docker exec -it transposer-dev composer.phar [command]"

bash:
	docker exec -it transposer-dev bash
