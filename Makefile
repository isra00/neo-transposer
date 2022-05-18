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
	docker tag transposer:`git rev-parse --short HEAD`-prod transposer:for-prod

# NT_PROFILER debería ser 0 en start (para test)
start: OPTIONAL_VOLUME=
#--user es para que si en Docker se escriben archivos, no se escriban como root sino como el usuario actual. ¿O www-data?
start-local: OPTIONAL_VOLUME=-v ${CURDIR}:/var/www/html --user $(id -u):$(id -g)

# Esto pasaría a ser docker compose up excluyendo MySQL.
start start-local: stop
	docker tag transposer:`git rev-parse --short HEAD`-dev transposer:for-prod
	docker start transposer-dev || docker run --rm -dit -p 80:80 \
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
		--add-host=host.docker.internal:172.17.0.1 \
		--name transposer-dev \
		$(OPTIONAL_VOLUME) \
		transposer:latest-dev

start-db-for-test:
	@docker stop nt-mysql || true
	docker run --rm -dit -p 3306:3306 --name nt-mysql -e MYSQL_ROOT_PASSWORD=${NT_DB_PASSWORD} mysql:5.7-debian --bind-address=0.0.0.0
	sleep 15
	@if [ -z "$NT_DB_USER" ] || [ -z "$NT_DB_PASSWORD" ] || [ -z "$NT_DB_DATABASE" ] || [ -z "$NT_DB_DATABASE_INTEGRATION" ]; then echo "Environment variables NT_DB_USER, NT_DB_PASSWORD, NT_DB_DATABASE and NT_DB_DATABASE_INTEGRATION must be set before calling this recipe" >&2; exit 1; fi
	docker exec    nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} -e "CREATE DATABASE ${NT_DB_DATABASE} COLLATE 'utf8_general_ci'"
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE} < create_tables.sql
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE} < song_data.sql
	docker exec    nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} -e "CREATE DATABASE ${NT_DB_DATABASE_INTEGRATION} COLLATE 'utf8_general_ci'"
	docker exec -i nt-mysql mysql -u${NT_DB_USER} -p${NT_DB_PASSWORD} ${NT_DB_DATABASE_INTEGRATION} < create_tables.sql

start-db-local:
	@docker stop nt-mysql || true
	docker run --rm -dit -p 3306:3306 --name nt-mysql -e MYSQL_ROOT_PASSWORD=root -v /var/www/vhosts/dev-env/mysql5:/var/lib/mysql mysql:5.7-debian
	sleep 15

#No need to delete it after stopping since it's run with --rm
stop:
	@docker stop transposer-dev || true

stop-all: stop
	@docker stop nt-mysql || true

test:
	docker exec -t transposer-dev vendor/bin/codecept run unit --coverage-html
	docker exec -t transposer-dev php tests/testAllTranspositions.php

test-acceptance:
	docker start selenium-chrome || docker run -d --name selenium-chrome --add-host=host.docker.internal:172.17.0.1 -p 4444:4444 -p 7900:7900 --shm-size=2g selenium/standalone-chrome
	sleep 5
	docker exec -t transposer-dev php /var/www/html/vendor/bin/codecept run acceptance

get-test-outputs:
	 docker cp transposer-dev:/var/www/html/tests/_output .

clean:
	rm -r cache/twig/*
	rm -r cache/profiler/*