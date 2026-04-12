# CLAUDE.md

Stack: Laravel 11, Blade, Docker, zepto/jQuery, MySQL

## Development

- Local URL: http://transposer.local
- Run commands inside the Docker container or via the Makefile.
- Use `make start-local` for development, `make test` for tests, `make bash` for shell.
- The app runs in Docker; DB host is `host.docker.internal`.

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

### Scripts & commands
- Legacy standalone PHP scripts should become Artisan commands with `app:kebab-case` naming.

### Config paths
- `config/nt.php` uses `__DIR__` which resolves to `config/`. Use `/../` for paths outside that directory.
