# CLAUDE.md

Stack: Laravel 11, Blade, Docker, zepto.js, MySQL

## Development

- Local URL: http://transposer.local
- Production URL: https://neo-transposer.com
- The app runs in Docker; DB host is `host.docker.internal`.
- Run commands inside the Docker container or via the Makefile.
- Use `make start-local` + `make start-db-local` for development, `make test` for tests, `make bash` for shell.
- Web server logs are redirected to the container's stderr and stdout.
- Commit directly to master branch.
- Apply usual Laravel conventions.
- Favor cohesion over decoupling. Code simplicity and maintainability are paramount.
- Declare things in the narrowest scope that fits: a value used by only one method is a local variable, not a class constant or property. Promote it only when a second method needs it.
- Be straight to the point in your answers when interacting with the agent user.
- Consider the impact on current user sessions in production when making changes.

## Code style

- Laravel Pint is the source of truth; the ruleset is `pint.json` (`laravel` preset with a
  few PSR-12-compliant overrides). Don't hand-format against it.
- `make lint` checks, `make lint-fix` fixes, `make check` adds static analysis.
- PHPStan + Larastan at level 5: `make analyse`. The codebase is clean at this level, so
  there is no baseline; fix new errors rather than reintroducing one.
- `make install-hooks` once per clone: pre-commit style check, and `git blame` skips the
  reformat commit.

## Testing

- We have unit tests (some of which actually make DB queries) in `tests/unit`, integration tests (Laravel feature tests) in `tests/integration`, and acceptance tests (e2e tests with Selenium) in `tests/acceptance`.
- They all run with Codeception. 
- Use `make test` to run unit and integration tests, `make test-acceptance` to run acceptance tests (which starts Selenium).
- When running locally, unit tests use the NT_DB_DATABASE_INTEGRATION schema seeded with test data. The test-all-transpositions (functional test) and acceptance (e2e) ones run against whatever database is running, to facilitate testing algorithm and song data changes done locally.
- In the CI environment, all tests run against the test database schema (see the GHA test.yml flow).

## Architecture

- `app/` — Laravel layer: controllers, middleware, providers, `app:kebab-case` commands.
- `src/NeoTransposer/Domain/` — entities, value objects, services, chord printers, admin
  tasks. Framework-agnostic; autoloaded PSR-4 as `NeoTransposer\`.
- `src/NeoTransposer/Infrastructure/` — MySQL repository implementations of the Domain
  repository interfaces, plus GeoIP and login flow.
- Locale routes go in the `{locale}` prefix group with `SetLocaleFromUrl` middleware;
  `NeedsLoginMiddleware` protects authenticated routes.
- Views extend `_base`; `page_title` and `page_class` are standard view variables.

## Translations

- Placeholders use Laravel's `:key` syntax. Translation files are JSON in `lang/`.
- HTML entities (`&larr;`) go outside `@lang()`, not inside the translation key.
- Use `{!! __(...) !!}` when the translated string contains HTML (e.g. `<strong>`).
- Beware of quotation marks within translation strings. Make sure everything is escaped properly.

