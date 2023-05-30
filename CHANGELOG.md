# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

- **Fix:** Set default to string incase of missing location.
- Adding assertions for element missing/existing.
- Middleware for testing factories.

## v0.7.0 - 2022-10-06

- Asset assertions and improvements.
- Support for mix pulling in dependencies.
- Cast the item to an array inside of only_children.
- Adding keywords to trigger --dev.
- Separate requires based on what they include.
- Compatibility layer for Refresh_Database and Installs_Wordpress.

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

- Prevent external requests during unit testing by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/293
- Adding macroable to responses by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/292
- Bump actions/cache from 3.0.4 to 3.0.5 by @dependabot in https://github.com/alleyinteractive/mantle-framework/pull/294
- Bumping asset manager by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/295
- Update testkit to include URL Generator by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/296
- Add request before/after callbacks by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/298
- Cleaning up the flag/argument, simplify to flag/argument/option by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/297
- Including mantle-framework/http-client with testing by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/299
- Fix Asset_Manager bug on asset() by @anubisthejackle in https://github.com/alleyinteractive/mantle-framework/pull/300
- Adding an Installation_Manager to facilitate installation by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/302
- Adding Conditionable Method Chaining by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/304
- Adding support for Mock_Http_Sequence inside an array by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/303
- Adding Concurrent Http Client Request Support by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/301
- Bumping composer autoloader to v0.6 by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/306

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
- Adding create_ordered_set helper by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/271
- Allow control over the temporary URL expiration by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/272
- Allow attributes to register hooks by @srtfisher in https://github.com/alleyinteractive/mantle-framework/pull/273
- Registering listeners with attributes in https://github.com/alleyinteractive/mantle-framework/pull/275

### Fixed

- Fix Faker deprecation warnings about accessing methods as parameters by @anubisthejackle in https://github.com/alleyinteractive/mantle-framework/pull/270
- Ensure WP_MULTISITE can be passed properly in https://github.com/alleyinteractive/mantle-framework/pull/274

## [0.1.0](https://github.com/alleyinteractive/mantle-framework/releases/tag/v0.1.0) - 2022-04-19

Initial release of the Mantle Framework 🎉
