# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Mantle is a Laravel-inspired framework for building large WordPress applications. It sits as a layer between WordPress core and custom code. Many components (Container, Collections, Support, Query Builder, Facades) are ports of Laravel concepts, adapted for WordPress and translated to **snake_case method names**.

This repo is the **read-only monorepo** for development. The individual `mantle-framework/*` packages are split out to standalone repos on release; you edit them here under `src/Mantle/`.

> A detailed companion guide lives at `.github/copilot-instructions.md` — read it for model/relationship/query-builder/testing API details. The notes below correct a few stale points in it (see "Stale doc corrections").

## Commands

```bash
composer test            # Full gate: lint (phpcs + phpstan + rector) then phpunit
composer phpunit         # PHPUnit only
composer phpunit:multisite   # Run suite under WP_MULTISITE=1
composer lint            # phpcs + phpstan + rector --dry-run (no changes)
composer lint:fix        # rector:fix + phpcbf (auto-fix)
composer phpcs           # Code style check
composer phpcbf          # Auto-fix code style
composer phpstan         # Static analysis (level 8, --memory-limit=3G)
composer rector          # Dry-run rector; rector:fix to apply
composer phpcs-modified   # phpcs only on git-modified files
composer monorepo-validate   # Validate per-package composer.json consistency
```

Run a single test:
```bash
vendor/bin/phpunit --filter test_method_name
vendor/bin/phpunit tests/Database/ModelTest.php
WP_MULTISITE=1 vendor/bin/phpunit --filter test_method_name   # under multisite
```

The first PHPUnit run downloads and installs a WordPress test environment automatically (driven by `tests/bootstrap.php` → `Mantle\Testing\manager()`). It rsyncs the plugin, installs companion plugins, and boots a full WP stack — no manual WP setup needed. Set `MANTLE_TESTING_DEBUG=true` (constant in bootstrap) to debug installation.

## Architecture

### Monorepo + split packages
- All source is under `src/Mantle/<Package>/` (PascalCase dirs), single PSR-4 map `Mantle\ → src/Mantle/`.
- Each package (`Database/`, `Http/`, `Support/`, `Testing/`, etc.) has its own `composer.json` declaring its dependencies. `Framework/` is the special umbrella package and is excluded from the split (`monorepo-builder.php`).
- The root `composer.json` `replace` block lists all `mantle-framework/*` packages. When adding/removing a package or changing cross-package deps, run `composer monorepo-validate`.
- Several packages ship a `files`-autoloaded `autoload.php` (helpers, function shims) — registered in the root `composer.json` `autoload.files`.

### Bootstrap & context detection
- `Framework/Bootloader.php` is the entry point. It detects execution context and loads the matching kernel: **HTTP Kernel** vs **Console Kernel** (`is_running_in_console()`, `is_running_in_console_isolation()` for tests).
- Sequence: `Bootloader::create() → register service providers → boot providers`.
- `Application/Application.php` is the service container subclass and app lifecycle owner.

### Service providers (central bootstrapping mechanism)
- `register()` (bind to container) runs for ALL providers first, then `boot()`, then `boot_provider()`.
- Registered in the consuming app's `config/app.php` under `providers[]`. Framework providers live in `Framework/Providers/`.

### Container & Facades
- The container is **Mantle's own** implementation (`Container/Container.php` implementing `Mantle\Contracts\Container`) — *not* Illuminate's, despite what the copilot doc says.
- Facades (`Facade/`) give static access to container bindings via `get_facade_accessor()`.

### Models & Query Builder (`Database/`)
- Models extend `Mantle\Database\Model\Post` / `Model\Term` etc., wrapping `WP_Post`/`WP_Term`/`WP_User`.
- Custom query builder wraps `WP_Query`/`WP_Term_Query` — there is no Eloquent. Chainable `where()`, `where_meta()`, `where_term()`, `with()` (eager loading), returns Collections.
- Post-to-post relationships are stored via an internal `mantle_relationship` taxonomy.

### Testing framework (`Testing/`)
This is a product in its own right (also shipped as `mantle-framework/testkit`). It provides the auto-installing WP environment, fluent HTTP testing (`$this->get(...)->assertOk()->assertSee(...)`), WordPress-aware assertions, factories (`static::factory()->post->create(...)`), HTTP mocking, and the `Refresh_Database` trait. Note PSR-4 CamelCase filenames here (`TestCase.php`, `FrameworkTestCase.php`).

## Conventions (enforced — match existing code)
- **`declare(strict_types=1);`** and full type coverage everywhere. Code must pass **PHPStan level 8**.
- **Method names are snake_case** (`has_many()`, `to_array()`) — this is the deliberate divergence from Laravel for WordPress familiarity.
- **Class names are PascalCase_With_Underscores** (`Has_One_Or_Many`, `Service_Provider`); variables/properties snake_case; constants SCREAMING_SNAKE_CASE.
- In PHPDoc use `@return static`, never `@return $this`. Use generics (`@template`) for collections and typed returns over magic methods.
- PHP `^8.3` minimum. CI matrix tests PHP 8.3/8.4/8.5 against multiple WordPress versions, single + multisite, and with VIP MU plugins.

## Stale doc corrections (`.github/copilot-instructions.md`)
That file predates the PSR-4 migration and some refactors. Where it conflicts with the repo, the repo wins:
- PSR-4 migration is **complete** for framework source — paths are `src/Mantle/Framework/Bootloader.php`, not `src/mantle/framework/class-bootloader.php`. The only `class-*.php` files left are app-scaffolding templates under `Framework/resources/application-structure/`.
- The container is Mantle's own, not Laravel's Illuminate Container.
- Minimum PHP is 8.3 (the doc says 8.2+).
