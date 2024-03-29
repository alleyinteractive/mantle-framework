# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v0.12.12 - 2024-01-08

### Added

- Adding support back for `alleyinteractive/wp-filter-side-effects` 1.0.

## v0.12.11 - 2023-12-18

### Fixed

- Allow Windows drive paths.

## v0.12.10 - 2023-11-27

### Changed

- Removed PHPUnit 10 support to prevent a breaking change. Moved to 1.x.

## v0.12.9 - 2023-11-21

### Changed

- Added PHPUnit 10 support.

## v0.12.8 - 2023-11-14

### Added

- Adding block assertions to strings.
- Allow partial matching of HTML content by xpath selectors.
- Add a shutdown handler to the installation script to prevent silent fatals.

### Fixed

- Ensure factories can be used with data providers.

## v0.12.7 - 2023-10-02

### Added

- Adding date query builder for posts.
- Adds a trait to easily silence remote requests during testing.

### Changed

- Improve the messaging of assertions when testing.

### Fixed

- Ensure that attribute and action methods are deduplicated in service providers.

## v0.12.6 - 2023-09-06

### Fixed

- Fix issue with custom post types/taxonomies and factories not resuming the
	correct post type/taxonomy after creation.

## v0.12.5 - 2023-09-01

### Fixed

- Improved the performance of the `with_image()` method on attachment factories.

## v0.12.4 - 2023-08-24

### Added

- Added `with_active_plugins()` method to the installation manager to set the active plugins after installation.
- Added the `install_plugin()` method to the installation manager to install a
  plugin from WordPress.org or a remote URL.

### Fixed

- Fixed an issue where the console kernel was not booting unless running `wp mantle` directly.

## v0.12.3 - 2023-08-21

### Added

- Add better support for a query modifier on a relationship.
- Add `whereRaw()` for querying against raw attributes in a SQL query.

### Fixed

- Fixed an issue when saving multiple models.

## v0.12.2 / v0.12.1

No changes, just a re-release to fix a bad tag.

## v0.12.0 - 2023-08-17

### Added

- Introduce a flexible Application Bootloader.
- Allow dynamic instance of a model to be created without defining the model class.
- Add facade docblocks and phpdoc block generation script.
- Stringable and updated Str class.
- Vendor Publishable Assets.
- Add first_or_new/first_or_create/update_or_create methods.
- New assertion helpers.
- Adding PHP 8.2 support.
- Allow JSON to be POST-ed to requests when testing.
- Adding chunk()/chunk_by_id()/each()/each_by_id() methods to the query builder, fixing order by aliases.
- Add dump/dumpSql/dd/ddSql to the query builder.
- Add testing for prefer-lowest.
- Add snapshot testing.

### Fixed

- Fix an error when typehinting and using request variables.
- Fix generator namespaces.
- Fix http-client content_type method by @nlemoine.
- Ensure that REST API headers are persisted when testing.

### Changed

- Remove Guzzle HTTP and move to WordPress Http Client.
- Refresh application routing and add tests.
- Improve the handling of various arguments to with_terms().
- Refactor Factories for simplicity and to combine with testing factories.
- Updating factory generators/stubs.
- Requiring `symfony/console`, upgrading to `psr/log` 3.0.
- Ensure that faker can always generate Gutenberg blocks.

## New Contributors
- @nlemoine made their first contribution in https://github.com/alleyinteractive/mantle-framework/pull/409

**Full Changelog**: https://github.com/alleyinteractive/mantle-framework/compare/v0.11.3...v0.12.0

## v0.11.3 - 2023-07-21

- Allow the default database configuration to be customized via environment
  variables: `WP_DB_NAME`, `WP_DB_USER`, `WP_DB_PASSWORD`, `WP_DB_HOST`.

## v0.11.2 - 2023-07-21

- Add back-support for WordPress 6.0 when testing.

## v0.11.1 - 2023-05-31

- Add support for using SQLite in tests (opt-in).

## v0.11.0 - 2023-05-30

### Added

- Add support for rsync-ing mu-plugins and adding object-cache.php during testing.

### Changes

- Assorted fixes from/to support PHPStan (we're now at level 5 with plans to increase).

## v0.10.8 - 2023-04-14

### Fixes

- Remove any reference to `LazyCollection`.

### Added

- Add a `with_image()` helper to create an attachment with a real image.

### Fixes

- Remove duplicate `rsync_exclusions` keys in `Rsync_Installation`.
- Handle some edge cases when running wp-cli on VIP

## v0.10.7 - 2023-04-04

- Fixing issue with core test case shim.

## v0.10.6 - 2023-03-31

### Changed

- Set better defaults for registering meta.
- Improving rsyncing and phpunit path detection during unit tests.
- Introduce a WP_UnitTestCase class and a core shim to the testing framework.
- Improve reporting of stray HTTP requests during unit tests.
- Allow json to be dumped from the testing response.

### Fixed

- Make `wp_insert_post` return `WP_Error`.
- Fix an error with the trace not passing through.
- Fix `get_facade_accessor` signature.

## v0.10.5 - 2023-02-22

### Fixed

- Fix a fatal error when the build directory doesn't exist.

## v0.10.4 - 2023-02-22

### Changed

- Remove Laravel Mix support. Switch to asset loader that aligns with shared company configuration.

## v0.10.3 - 2023-02-17

### Fixed

- Ensure --url doesn't throw an error on bin/mantle

## v0.10.2 - 2023-02-15

- Fix issue with custom namespace in application.

## v0.10.1 - 2023-01-10

- Upgrading to `voku/portable-ascii` v2 to fix conflicted version with `illuminate/support`.

## v0.10.0 - 2023-01-06

- Improvements to database factories: adds `with_meta()` to all supported types, adds `with_posts()` to term factory.
- Upgrading to Symfony 6 and Illuminate/View 9
- Allow `Mantle\Testing\Mock_Http_Response` to be converted to `Mantle\Http_Client\Response`
- Support streamed HTTP responses in the client and fake.
- Add `maybe_rsync_content` during testing.
- Add `--delete` when rsyncing content during testing.
- Work to make Mantle a bit more isolated.

## v0.9.1 - 2022-11-22

- Fix for testing installation.

## v0.9.0 - 2022-11-16

- Allow more flexible control over incorrect usage and deprecations
- Add support for rsync-ing a codebase from within the testing suite
- Fixes for WordPress 6.1
- Adding support for testing commands
- Add support for an isolated console mode
- Use the existing WP_CORE_DIR if one exists
- Display trace for incorrect usage/deprecation notices
- Authentication assertions and tests

## v0.8.0 - 2022-10-25

- **Fix:*- Set default to string incase of missing location.
- Adding assertions for element missing/existing.
- Middleware for testing factories.

## v0.7.0 - 2022-10-06

- Asset assertions and improvements.
- Support for mix pulling in dependencies.
- Cast the item to an array inside of only_children.
- Adding keywords to trigger --dev.
- Separate requires based on what they include.
- Compatibility layer for Refresh_Database and Installs_WordPress.

## v0.6.1 - 2022-09-20

- Adding alleyinteractive/wp-filter-side-effects to mantle-framework/database

## v0.6.0 - 2022-09-16

- Ensure tests have a permalink structure by default.
- Adding only_children() method to collections.
- Update to `alleyinteractive/composer-wordpress-autoloader` v1.0.0.
- Overhaul queue system, add support for closures to be dispatched to the queue asynchronously.
- Remove Caper package in favor of https://github.com/alleyinteractive/wp-caper.

## v0.5.4 - 2022-08-04

- Fixing issue with testing library

## v0.5.2 - 2022-08-03

- Fixing issue with Http Client.

## v0.5.1 - 2022-08-01

- Fixing issue with testing installation callback.

## v0.5.0 - 2022-07-29

- Prevent external requests during unit testing in https://github.com/alleyinteractive/mantle-framework/pull/293
- Adding macroable to responses in https://github.com/alleyinteractive/mantle-framework/pull/292
- Bump actions/cache from 3.0.4 to 3.0.5 by @dependabot in https://github.com/alleyinteractive/mantle-framework/pull/294
- Bumping asset manager in https://github.com/alleyinteractive/mantle-framework/pull/295
- Update testkit to include URL Generator in https://github.com/alleyinteractive/mantle-framework/pull/296
- Add request before/after callbacks in https://github.com/alleyinteractive/mantle-framework/pull/298
- Cleaning up the flag/argument, simplify to flag/argument/option in https://github.com/alleyinteractive/mantle-framework/pull/297
- Including mantle-framework/http-client with testing in https://github.com/alleyinteractive/mantle-framework/pull/299
- Fix Asset_Manager bug on asset() by @anubisthejackle in https://github.com/alleyinteractive/mantle-framework/pull/300
- Adding an Installation_Manager to facilitate installation in https://github.com/alleyinteractive/mantle-framework/pull/302
- Adding Conditionable Method Chaining in https://github.com/alleyinteractive/mantle-framework/pull/304
- Adding support for Mock_Http_Sequence inside an array in https://github.com/alleyinteractive/mantle-framework/pull/303
- Adding Concurrent Http Client Request Support in https://github.com/alleyinteractive/mantle-framework/pull/301
- Bumping composer autoloader to v0.6 in https://github.com/alleyinteractive/mantle-framework/pull/306

## v0.4.0 - 2022-06-28

Fixing a miss-tagged version.

## 0.3.0 - 2022-06-27

### Added

- Allow testing framework factory to return models pull/276
- Define Post Model Terms Fluently
- Add is_json/headline to Str
- Adding a With_Faker trait
- Adding Assert JSON Structure

### Fixed

- Make all headers lowercase for easier comparison when testing

## 0.2.0 - 2022-05-25

### Added

- Create new Testkit cases: Integration and Unit Test by @anubisthejackle in https://github.com/alleyinteractive/mantle-framework/pull/269
- Adding create_ordered_set helper in https://github.com/alleyinteractive/mantle-framework/pull/271
- Allow control over the temporary URL expiration in https://github.com/alleyinteractive/mantle-framework/pull/272
- Allow attributes to register hooks in https://github.com/alleyinteractive/mantle-framework/pull/273
- Registering listeners with attributes in https://github.com/alleyinteractive/mantle-framework/pull/275

### Fixed

- Fix Faker deprecation warnings about accessing methods as parameters by @anubisthejackle in https://github.com/alleyinteractive/mantle-framework/pull/270
- Ensure WP_MULTISITE can be passed properly in https://github.com/alleyinteractive/mantle-framework/pull/274

## [0.1.0](https://github.com/alleyinteractive/mantle-framework/releases/tag/v0.1.0) - 2022-04-19

Initial release of the Mantle Framework 🎉
