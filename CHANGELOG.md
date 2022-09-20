# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

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
