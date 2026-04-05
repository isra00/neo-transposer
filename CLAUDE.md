# Project: Neo-Transposer

## Stack
- PHP (Silex framework)
- MySQL (check version in `docker-compose.yml`)
- Docker
- Twig templates
- Codeception (testing)

## Local development
- URL: http://transposer.local. Browse to this URL for verification after changes.
- Environment variables required: `NT_DB_HOST`, `NT_DB_USER`, `NT_DB_PASSWORD`, `NT_DB_DATABASE`, `NT_DB_DATABASE_INTEGRATION`, `NT_RECAPTCHA_SECRET`, `NT_ADMIN_USERNAME`, `NT_ADMIN_PASSWORD`

## Common commands
- `make build-dev` — Build dev Docker image (default target). Must run commit is done.
- `make start-local` — Run app with local volume mounts (for development)
- `make stop` — Stop the app container
- `make start-db-local` — Start local MySQL container
- `make test` — Run unit tests with coverage
- `make test-acceptance` — Run acceptance tests (starts Selenium)
- `make bash` — Shell into the running container
- `make clean` — Clear twig and profiler caches
- Composer: `docker exec -it transposer-dev composer.phar [command]`