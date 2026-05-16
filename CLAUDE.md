# CLAUDE.md

Stack: Laravel 11, Blade, Docker, zepto.js, MySQL

## Development

- Local URL: http://transposer.local
- Production URL: https://neo-transposer.com
- Run commands inside the Docker container or via the Makefile.
- Use `make start-local` for development, `make test` for tests, `make bash` for shell.
- The app runs in Docker; DB host is `host.docker.internal`.
- When running locally, unit tests use the NT_DB_DATABASE_INTEGRATION schema seeded with test data. The test-all-transpositions (functional test) and acceptance (e2e) ones run against whatever database is running, to facilitate testing algorithm and song data changes done locally.
- In the CI environment, all tests run against the test database schema (see the GHA test.yml flow).
- Web server logs are redirected to the container's stderr and stdout.

## Testing

- We have unit tests (some of which actually make DB queries) in `tests/unit`, integration tests (Laravel feature tests) in `tests/integration`, and acceptance tests (e2e tests with Selenium) in `tests/acceptance`.
- They all run with Codeception. 
- Use `make test` to run unit and integration tests, `make test-acceptance` to run acceptance tests (which starts Selenium).

## Silex → Laravel migration

This project is being migrated from Silex to Laravel 11. Many controllers and views are still in legacy form under `src/NeoTransposer/`. When migrating, apply these patterns:

### Container & config
- `$app['neoconfig']['x']` → `config('nt.x')`
- `$app['db']` → `DB` facade or repository classes
- `$app['neouser']` → `session('user')`
- `$app['locale']` → `app()->getLocale()`
- `$app['root_dir']` → `base_path()`
- `NeoApp` class no longer exists. `src/NeoTransposer/services.php` is legacy and not loaded.
- `TransposedSong::fromDb()` already uses `app()` internally — no `$app` parameter.

### Controllers
- Legacy: `src/NeoTransposer/Controllers/` → New: `app/Http/Controllers/`
- `$app->render('x.twig', [...])` → `response()->view('x', [...])`
- `$app->redirect($app->path('route'))` → `redirect()->route('route', ['locale' => app()->getLocale()])`
- `$app->trans(...)` → `__(...)`
- Locale routes go in the `{locale}` prefix group with `SetLocaleFromUrl` middleware.
- `NeedsLoginMiddleware` protects authenticated routes.

### Views
- Twig → Blade views extending `_base`.
- `page_title` and `page_class` are standard view variables.

### Translations
- Twig used `%key%` placeholders; Laravel uses `:key`. Update JSON files in `lang/` when migrating.
- HTML entities (`&larr;`) go outside `@lang()`, not inside the translation key.
- Use `{!! __(...) !!}` when the translated string contains HTML (e.g. `<strong>`).
- Beware of quotation marks within translation strings. Make sure everything is escaped properly.

### Scripts & commands
- Legacy standalone PHP scripts should become Artisan commands with `app:kebab-case` naming.

### Config paths
- `config/nt.php` uses `__DIR__` which resolves to `config/`. Use `/../` for paths outside that directory.
