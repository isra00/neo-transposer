.PHONY: build
.DEFAULT_GOAL := build-dev

#Quedarían pendientes targets para composer, pero es un coñazo

# This should be run on post-commit, right? Otherwise serve would fail bc commit name has changed.
build-dev:
	#sh update_mmdb.sh
	docker build --target dev -t transposerlaravel:`git rev-parse --short HEAD`-dev .
	docker tag transposerlaravel:`git rev-parse --short HEAD`-dev transposerlaravel:latest-dev

build-prod:
	sh update_mmdb.sh
	docker build --target prod -t transposerlaravel:`git rev-parse --short HEAD`-prod .
	docker tag transposerlaravel:`git rev-parse --short HEAD`-prod transposerlaravel:prod

# NT_PROFILER debería ser 0 en start (para test)
start: OPTIONAL_VOLUME=
#--user es para que si en Docker se escriben archivos, no se escriban como root sino como el usuario actual. ¿O www-data?
start-local: OPTIONAL_VOLUME=-v ${CURDIR}:/var/www/html --user $(id -u):$(id -g)

# Esto pasaría a ser docker compose up excluyendo MySQL.
start start-local: stop
	docker tag transposerlaravel:`git rev-parse --short HEAD`-dev transposerlaravel:for-prod
	docker start transposerlaravel-dev || docker run --rm -dit -p 80:80 \
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
		--add-host=host.docker.internal:172.17.0.1 \
		--name transposerlaravel-dev \
		$(OPTIONAL_VOLUME) \
		transposerlaravel:latest-dev

start-db-for-test:
	@docker stop nt-mysql || true
	docker run --rm -dit -p 3306:3306 --name nt-mysql --platform linux/x86_64 -e MYSQL_ROOT_PASSWORD=${NT_DB_PASSWORD} mysql:8.3 --bind-address=0.0.0.0
	sleep 15
	@if [ -z "$NT_DB_USER" ] || [ -z "$NT_DB_PASSWORD" ] || [ -z "$NT_DB_DATABASE" ] || [ -z "$NT_DB_DATABASE_INTEGRATION" ]; then echo "Environment variables NT_DB_USER, NT_DB_PASSWORD, NT_DB_DATABASE and NT_DB_DATABASE_INTEGRATION must be set before calling this recipe" >&2; exit 1; fi
	docker exec    nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} -e "CREATE DATABASE ${NT_DB_DATABASE} COLLATE 'utf8_general_ci'"
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE} < create_tables.sql
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE} < song_data.sql
	docker exec    nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} -e "CREATE DATABASE ${NT_DB_DATABASE_INTEGRATION} COLLATE 'utf8_general_ci'"
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE_INTEGRATION} < create_tables.sql

start-db-local:
	@docker stop nt-mysql || true
	docker run --rm -dit -p 3306:3306 --name nt-mysql --platform linux/x86_64 -e MYSQL_ROOT_PASSWORD=root -v /var/www/vhosts/dev-env/mysql5:/var/lib/mysql mysql:8.3
	sleep 15

start-db-prod:
	@docker stop nt-mysql || true
	docker run --rm -dit -p 3306:3306 --name nt-mysql --platform linux/x86_64 -e MYSQL_ROOT_PASSWORD=root -v ./neo-transposer.sql:/docker-entrypoint-initdb.d/neo-transposer.sql mysql:8.3
	docker run --rm -dit -p 3306:3306 --name nt-mysql --platform linux/x86_64 -e MYSQL_ROOT_PASSWORD=${NT_DB_PASSWORD} mysql:8.3 --bind-address=0.0.0.0
	sleep 10

#No need to delete it after stopping since it's run with --rm
stop:
	@docker stop transposerlaravel-dev || true

stop-all: stop
	@docker stop nt-mysql || true

test:
	docker exec -t transposerlaravel-dev vendor/bin/codecept run unit --coverage-html --coverage-xml
	@sed -i "s@\/var\/www\/html@\/\/wsl$\/Ubuntu\/var\/www\/vhosts\/transposer.local@g" tests/_output/coverage.xml || true
	docker exec -t transposerlaravel-dev php tests/testAllTranspositions.php

test-acceptance:
	docker start selenium-chrome || docker run -d --name selenium-chrome --add-host=host.docker.internal:172.17.0.1 -p 4444:4444 -p 7900:7900 --shm-size=2g selenium/standalone-chrome
	sleep 5
	docker exec -t transposerlaravel-dev php /var/www/html/vendor/bin/codecept run acceptance

get-test-outputs:
	 docker cp transposerlaravel-dev:/var/www/html/tests/_output .

clean:
	rm -r cache/twig/*
	rm -r cache/profiler/*

composer:
	@echo "To run composer, type docker exec -it transposerlaravel-dev composer.phar [command]"

bash:
	docker exec -it transposerlaravel-dev bash
