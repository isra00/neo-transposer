.DEFAULT_GOAL := build-dev

build-dev:
	docker build --target dev -t transposer:`git rev-parse --short HEAD`-dev .

build-prod:
	docker build --target prod -t transposer:`git rev-parse --short HEAD`-prod .

#No need to delete it after stopping since it's run with --rm
destroy-server:
	@docker stop transposer-dev || true

serve: destroy-server
	docker run --rm -dit -p 80:80 -e ENV=DEV --env-file ./.env --name transposer-dev transposer:`git rev-parse --short HEAD`-dev

serve-local: destroy-server
	docker run --rm -dit -p 80:80 -e ENV=DEV --env-file ./.env -v ${CURDIR}:/var/www/html --name transposer-dev transposer:`git rev-parse --short HEAD`-dev

test:
	docker exec -it transposer-dev vendor/bin/codecept run unit --coverage-html
	docker exec -it transposer-dev php tests/testAllTranspositions.php

test-acceptance:
	docker start selenium-chrome || docker run -d --name selenium-chrome -p 4444:4444 -p 7900:7900 --shm-size=2g selenium/standalone-chrome && sleep 2
	docker exec -it transposer-dev php /var/www/html/vendor/bin/codecept run acceptance

build-and-test: build-dev serve test test-acceptance