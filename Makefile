.DEFAULT_GOAL := build-dev

pre-build:
	sh update_mmdb.sh

build-dev: pre-build
	docker build --target dev -t transposer:`git rev-parse --short HEAD`-dev .

build-prod: pre-build
	docker build --target prod -t transposer:`git rev-parse --short HEAD`-prod .
	docker tag transposer:`git rev-parse --short HEAD`-dev transposer:for-prod

#No need to delete it after stopping since it's run with --rm
destroy-server:
	@docker stop transposer-dev || true

serve: destroy-server
	docker run --rm -dit -p 80:80 \
		-e NT_DB_HOST \
		-e NT_DB_USER \
		-e NT_DB_PASSWORD \
		-e NT_DB_DATABASE \
		-e NT_DB_TEST_DATABASE \
		-e NT_RECAPTCHA_SECRET \
		-e NT_ADMIN_USERNAME \
		-e NT_ADMIN_PASSWORD \
		-e NT_ANALYTICS_ID \
		-e NT_DEBUG \
		--add-host=host.docker.internal:172.17.0.1 --name transposer-dev \
		transposer:`git rev-parse --short HEAD`-dev

serve-local: destroy-server
	docker run --rm -dit -p 80:80 \
		-e NT_DB_HOST \
		-e NT_DB_USER \
		-e NT_DB_PASSWORD \
		-e NT_DB_DATABASE \
		-e NT_DB_TEST_DATABASE \
		-e NT_RECAPTCHA_SECRET \
		-e NT_ADMIN_USERNAME \
		-e NT_ADMIN_PASSWORD \
		-e NT_ANALYTICS_ID \
		-e NT_DEBUG \
		--add-host=host.docker.internal:172.17.0.1 -v ${CURDIR}:/var/www/html --name transposer-dev \
		transposer:`git rev-parse --short HEAD`-dev

#Rename to serve-mysql
run-test-db:
	@docker stop test-mysql || true
	docker run --rm -dit -p 3306:3306 --name test-mysql -e MYSQL_ROOT_PASSWORD=root mysql:5.7-debian --bind-address=0.0.0.0
	sleep 15
	docker exec    test-mysql mysql -uroot -proot -e 'CREATE DATABASE nt_only_songs COLLATE utf8_general_ci'
	docker exec -i test-mysql mysql -uroot -proot nt_only_songs < create_tables.sql
	docker exec -i test-mysql mysql -uroot -proot nt_only_songs < song_data.sql
	docker exec    test-mysql mysql -uroot -proot -e 'CREATE DATABASE nt_empty_tables COLLATE utf8_general_ci'
	docker exec -i test-mysql mysql -uroot -proot nt_empty_tables < create_tables.sql

test:
	docker exec -t transposer-dev vendor/bin/codecept run unit --coverage-html
	docker exec -t transposer-dev php tests/testAllTranspositions.php

test-acceptance:
	docker start selenium-chrome || docker run -d --name selenium-chrome --add-host=host.docker.internal:172.17.0.1 -p 4444:4444 -p 7900:7900 --shm-size=2g selenium/standalone-chrome
	sleep 5
	docker exec -t transposer-dev php /var/www/html/vendor/bin/codecept run acceptance

build-and-test: build-dev serve test test-acceptance
