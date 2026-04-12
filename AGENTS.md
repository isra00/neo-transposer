-Stack: Laravel 11, Blade, Docker, zepto/jQuery, MySQL
-Run commands, tests, etc. using the Makefile or getting inspired by its definitions. 
-Run commands inside the docker container.
-The local url is http://transposer.local. Use curl or chrome to access it for testing/validation.
-Use blade for templates.
-Follow Laravel best practices for controllers, models, and views.
-This project is the result of migrating the legacy project in Silex to Laravel 11.

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
