# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

### Changed

- Adjusted the format of pooled HTTP requests to use `Pooled_Pending_Request`
  class methods which support direct chaining of methods like `put()`, `post()`,
  etc.

## v1.8.6

### Fixed

- Fixed the order of attributes returned by
  `Mantle\Support\Reflector::get_attributes_for_class()` to maintain inheritance
  order (parent -> child).

## v1.8.5

### Added

- Added `Mantle\Support\Reflector::get_attributes_for_class()` method to
  retrieve attributes for a class (with parents), with support for filtering by
  attribute name and flags.
- Added `first_or_create()` method to all factories to allow for creating a model if it doesn't exist.

### Changed

- `Mantle\Testing\Concerns\Reads_Annotations::get_attributes_for_method()`
  method will now return attributes for the passed class as well as any parent
  classes. You can disable this by passing `false` for the `$inherit` parameter.

## v1.8.4

### Changed

- Changed the cache key and TTL parameter names in the View class to be more concise (`$key` and `$ttl`).
- Add `post_mime_type` to the attachment factory definition to ensure that
  attachments are created with a valid MIME type.

### Fixed

- Suppress errors during blog/site creation to prevent errors when creating temporary tables.

## v1.8.3

### Fixed

- Fix the parsing of `argv` when handling the `wp-cli` command.
- Adjusted the way that `nunomaduro/collision` is loaded in PHPUnit.

## v1.8.2

No changes, just a re-release to fix a bad tag.

## v1.8.1

No changes, just a re-release to fix a bad tag.

## v1.8.0

### Added

- Adds a `PreserveObjectCache` attribute to prevent the object cache from being
  cleared during unit test HTTP requests.
- Added `Database_Table_Model` class to allow for creating models that
  represent database tables without a post type or taxonomy.
- Added `assertDatabaseHas()` and `assertDatabaseDoesNotHave()` methods to the
  to allow for asserting that a database table has or does not have a specific
  row.
- Added `Mantle\Support\Uri` class and `Mantle\Support\Helpers\uri()` helper to
  create and manipulate URIs. Supports manipulating the current query or
  modifying an arbitrary URI.

### Changed

- Refactored the routing registrar class to be more flexible. Ensure that REST
  API routes are treated the same as web routes.
- Adjusted the template wrapper middleware to use block templates instead of
  `get_header()`/`get_footer()` calls.
- Added callback support to the `assertContent()` method to allow for
  more complex assertions on the content of a response.

### Fixed

- The param for `Post_Type_Arguments::template()` has been fixed to match the argument shape from core.

## v1.7.2

### Fixed

- Bumped `league/commonmark` to fix a XSS vulnerability.

## v1.7.1

### Fixed

- Ensure that an early redirect in `parse_query` prevents the template from being
  loaded.
- Fixed an issue with the `wp_scripts`/`wp_styles`.

## v1.7.0

### Added

- Added `Blade` facade with `Blade::render_string()` method to render a string
  using the Blade templating engine.
- Added `assertBlockExists()` and `assertBlockMissing()` to the response assertions.
- Added `$count` param to `assertPostExists()`.
- Added dynamic sentence length support to `paragraph()` and `paragraphs()` on
  the Faker block provider.
- Added `Environment` attribute to allow environment-specific code to run during tests.
- Added support for user agent setting in tests via the `#[UserAgent]` attribute.
- Added `slash()` helper to factories to automatically slash content before storing.
- Added `Mantle\Support\Registration\Post_Type_Arguments` and
  `Mantle\Support\Registration\Taxonomy_Arguments` classes to allow for
  registering post types/taxonomies with custom arguments fluently.
- Add wildcard event listener support to events.
- Add support for more log drivers and channels.
- Add `environment_mixed()` helper to return a `Mixed_Data` instance for an
  environment variable.

### Changed

- Various types added to the framework to support increasing PHPStan to level 6.
- Remove the `WP_ENVIRONMENT_TYPE` environmental variable on tear down.
- When using the `Hookable` trait, an incorrect usage notice will be fired (or a
  runtime exception if on a local environment) if a method attempts to be
  used as a hook callback that is not public.
- `Interacts_With_Data::bool()` was changed to use `wp_validate_boolean()` to
  determine the boolean value of a value.
- Models' `new_factory()` method return type has changed to
  `Mantle\Database\Factory\Factory|string|null` with the ability to return a
  class string of a factory to use.
- The Console Kernel will attempt to load the commands within `app/console`
  directory and from `routes/console.php` if it exists without needing to define
  a `commands()` method in your application's console kernel.
- Change the priority of the `terminate` callback for the application's kernel
  to `PHP_INT_MAX` to prevent interfering with Query Monitor which outputs data
  at `shutdown` priority 9.

### Fixed

- Ensure that multiple HTTP request calls properly load the
  header/footer/sidebar template. Previously only the first request would load
  the header/footer/sidebar templates and the response returned was only the
  template content.
- Changed the `$_SERVER` variables set for HTTP headers when testing to set it
  as the string value rather than an array of strings. Previously a `X-Example`
  header with a value of `test` would become `$_SERVER['HTTP_X_EXAMPLE'] = ['test']` rather
  than `$_SERVER['HTTP_X_EXAMPLE'] = 'test'`.
- `wp_redirect` calls during `parse_query` can now be properly tested.

## v1.6.0

### Added

- Added a `list()`, `ordered_list()`, `reusable()`, and `button()` methods to the
  block factory to generate the corresponding blocks.
- Add support for PHPUnit 12. Note: PHPUnit 12 drops all support for docblock
  annotations. To easily upgrade your project to use attributes, try
  [Rector](https://getrector.com/documentation).

### Changed

- When using the `Hookable` trait, any `action__{method}`/`on_{method}` will be
  ignored when the method uses an `Action`/`Filter` attribute. To allow for
  legacy behavior, the
  `Mantle\Support\Attributes\Hookable\Allow_Legacy_Duplicate_Registration`
  attribute can be used on the class to allow for duplicate registration of the
  methods.
- Additional typings added to internal framework class methods, parameters, and return types.

## v1.5.8

### Added

- Added an `HTML` class to help with HTML querying, assertions, and manipulation.
- Added `factory()` method to database seeder.

### Changed

- Renamed the base test cases for `mantle-framework/testing` and
  `mantle-framework/testkit` to `TestCase` (previously
  was `Test_Case`). A shim is provided for backward compatibility. The old
  class names are not yet deprecated.

## v1.5.7

### Changed

- Updated `block_factory()->block()` to require `null` to be passed as `$content`
  if no content is desired. This prevents the block factory from generating an
  empty block when `$content` is just an empty string.

## v1.5.6

### Added

- Added a `mixed()` helper function to return a `Interacts_With_Data` instance
  for a mixed value that isn't associated with meta or an option.

### Fixed

- Prevent the site's default category from being added to a post when using the
  `with_terms()` method on a post factory if a category is being set.
- Fixed an issue where the output would not pass back to the console when
  calling a command with `Console::call()`.

## v1.5.5 - 2025-03-12

No changes, just a re-release to fix a bad tag.

## v1.5.4 - 2025-03-12

### Added

- Added a unit testing factory for Byline Manager profiles and Co-Authors-Plus guest authors.
- Added a `Option` and `Object_Metadata` support classes as well as `option()`, `post_meta()`, `term_meta()`, `user_meta()`, and `comment_meta()` helpers to retrieve options and object metadata in a type-safe manner.
- Added a `Option` support class and `option()` helper to retrieve options in a type-safe manner.
- Added `fail()` method to commands to allow for a command to fail with a message.
- Added `with_debug()` and `with_multisite()` methods to the installation manager.

### Changed

- Passing a `\Stringable` as a model attribute will now be cast to a string before being set on the model.

### Fixed

- Fix the `Post::for()`/`Term::for()` methods to properly set the post
  type/taxonomy for the model when creating a new instance. Previously, the
  model would always be created with the default post type/taxonomy.
- Fixed command testing to proper handle failed commands.

## v1.5.3 - 2025-03-04

### Changed

- Change `str()` helper to only accept a string and always return a Stringable object.
- Allow multiple
  `Ignore_Deprecation`/`Ignore_Incorrect_Usage`/`Expected_Deprecation`/`Expected_Incorrect_Usage`
  attributes on a test method/class.

## v1.5.2 - 2025-02-06

### Fixed

- Fixed bad configuration file calling `env()` vs `environment()`.

## v1.5.1 - 2025-02-06

### Changed

- Dropped `nunomaduro/collision` from the main
  `alleyinteractive/mantle-framework` package in favor of making the package a
  dependency on `alleyinteractive/mantle`. It still remains a dependency of
  `mantle-framework/testkit`.

## v1.5.0 - 2025-02-06

### Changed

- Dropped `illuminate/view` from the `alleyinteractive/mantle-framework`
  package. There is a conflict between the dependent package
  `illuminate/support` and `spatie/once`. `alleyinteractive/mantle` will be
  updated to require `illuminate/view` directly.
- Upgraded `mantle-framework/testkit` to support `nunomaduro/collision` v8 (which requires PHP 8.2 and PHPUnit 10+).
- Drop support for `symfony/console` v6.2. All Symfony components are now at v7.0.

## v1.4.5 - 2025-02-03

### Fixed

- Fixed issue setting the domain during installation.

## v1.4.4 - 2025-02-03

### Fixed

- Ensure that when setting the home URL for testing with `Installation_Manager::with_url()` also sets the test domain.
- When setting a HTTPS home URL for testing, ensure that the site is installed using HTTPS.

## v1.4.3 - 2025-01-31

### Fixed

- Fixed issue when making HTTP requests in tests using partial URL paths.

## v1.4.2 - 2025-01-28

### Fixed

- Fix path to load the vip-config.php file during testing.

## v1.4.1 - 2025-01-21

### Changed

- Load `vip-config.php` during testing using the `WP_CONTENT_DIR` constant.
- Use the testing directory (`WP_TESTS_INSTALL_PATH`) for `ABSPATH` if not set.

## v1.4.0 - 2025-01-20

📢 Minimum PHP version is now 8.2. The framework supports 8.2 - 8.4.

### Added

- ✨ Experimental feature ✨: Use the home URL as the base URL for testing rather
  than `WP_TESTS_DOMAIN`. This can be enabled by calling the
  `with_experimental_testing_url_host()` method of the installation manager or
  by setting the `MANTLE_EXPERIMENTAL_TESTING_USE_HOME_URL_HOST` environment
  variable.

  Once enabled, the home URL will be used as the base URL for testing rather
  the hard-coded `WP_TESTS_DOMAIN`. It will also infer the HTTPS status from
  the home URL.
- Added `with_option()`/`with_home_url()`/`with_site_url()` methods to the installation manager.
- Add a `without_local_object_cache()` method to prevent the `object-cache.php` drop-in from being loaded locally.
- Added a better `dump()` method to the response object when testing HTTP
  requests that will dump the request/response to the console.

### Changed

- Removed support for PHP 8.1. The minimum PHP version is now 8.2.
- For projects that require PHPUnit 9, the `phpunit/phpunit` version is now set to `^9.6.22`.
- Upgraded to Symfony 7.0 packages.
- Disable `spatie/once`'s cache if found during unit testing.
- Ensure that the `QUERY_STRING` server variable is set when testing HTTP
  requests.

### Fixed

- Ensure that built-in taxonomies properly register their rewrite rules during testing.

## v1.3.3 - 2025-01-10

### Added

- Added `Mantle\Support\Helpers\capture` helper to capture output from a callback using output buffering.

### Changed

- Updated the `Mantle\Support\Helpers\defer` helper to be able to used outside
  of the Mantle Framework via the `shutdown` hook.

### Fixed

- Allow `Filter`/`Action` attributes to be used multiple times on the same method.

## v1.3.2 - 2024-12-17

- Allow stray requests to be ignored and pass through when stray requests are being prevented.

## v1.3.1 - 2024-12-13

### Fixed

- Fixed issue where output buffers were left open when an exception occurred during setup.

## v1.3.0 - 2024-12-02

### Added

- Define a `thumbnail` relationship on the post model that when defined will return an attachment model.
- Added count support to `assertSee()` for responses and `assertContains()` for HTML string assertions.
- Added `assertContains()` to response assertions.

### Changed

- Post factories that are passed a term slug to `with_terms()` will create the
  term if it doesn't exist by default. This can be disabled by calling the
  `create_terms()` method on the factory or replacing the with terms method call
  with `with_terms_only_existing()`.

### Fixed

- Attachment factories now properly use the `Attachment_Factory` class.
- Ensure that the `delete()` method of the HTTP Client doesn't set a body by default.
- Ensure that `with_terms()` can support an array of term slugs when passed with a
  taxonomy index.
- Ensure that framework configuration respects the application configuration.
- Ensure that collections can properly implode `Stringable` objects.

## v1.2.0 - 2024-09-23

### Added

- Add support for dispatching jobs to the queue after the current response has
  been sent.
- Allow the block factory to override text when generating blocks.
- Added new `defer()` helper.
- Added `Cache::flexible()` method to add SWR support to the cache.
- Added support for parallel unit testing with `brianium/paratest` (in beta).
- Added dynamic creation of post type/taxonomy factories.
- Added `Reset_Server` trait to reset the server between tests.
- Add `with_https()` to control if the request being tested is over HTTPS.
- Add `andReturnBoolean()` and `andReturn( fn ( $value ) => ... )` support to
  action/filter expectations
- Add cached HTTP response support using the `cache()` method.

### Changed

- **Breaking:** Http Client pools should now be built using `->method()` and `->url()` instead.
- Dropped support for Redis as a cache backend in favor of the default object
  cache drop-in.
- Allow returning falsey from `Collection::map_to_dictionary()`.

## v1.1.3 - 2024-08-14

### Added

- Added a `with_image()` helper to mocked HTTP responses.
- Added a `is_blob()` and `is_file()` helper to `Mantle\Http_Client\Response`.
- Added `with_real_thumbnail()` method to post factory for creating posts with
  real underlying thumbnail files.

### Changed

- Added support for faking specific HTTP requests by method.
- Added helper for fluently building HTTP sequence responses.

### Fixed

- Fixed an issue with taxonomy registration not returning an array.

## v1.1.2 - 2024-06-20

### Fixed

- Fixed issue with the `mantle-framework/testkit` package depending on classes
  that do not exist for that package (introduced in v1.1.0).

## v1.1.1 - 2024-06-20

### Added

- Add a `with_json()` helper to the HTTP client to send JSON data in a request.

### Changed

- Added types to the HTTP client methods.

### Fixed

- Fix the order of the `vip-config.php` loading that was added during 1.0.

## v1.1.0

### Added

- Added a `classname`/`the_classname` helper to generate complex class names.
- Added support for installing the Redis `object-cache.php` drop-in during
  testing with `with_object_cache()`.
- Added support for PHPUnit 11.

### Changed

- Overhauled the bootloader to be more flexible and allow for more
  customization. Supports passing configuration, custom kernels, exception
  handlers, etc. via the bootloader when configuring the application.
- Ensure that framework configuration is properly merged into application
  configuration when booting the application. This allows for slimmer
  configuration files in the application. Service providers will always
  load without needing to be declared in the application configuration.
- Load the `wp-content/vip-config/vip-config.php` file if it exists during
  testing to integrate better with VIP Go projects.

### Fixed

- Fixed issue with command jobs not working properly.
- Ensure that unit tests fail when a project's installation script fails.
- Fix anonymous queue jobs from WP-CLI failing to run.
- Fixed issue with HTTP Client not returning the proper headers.

## v1.0.7 - 2024-04-29

### Added

- Added a `block_factory()` helper to generate blocks in tests.

### Changed

- Changed `Hookable` to accept all arguments passed to the `add_action()` and
  `add_filter()` functions.

### Fixed

- Prevent sending mail during the install `wp_install()` call in unit tests by
  mocking the `$phpmailer` global earlier.
- Allow anonymous models to define events via `Model::created()` methods.
- Fixed the Collision printer with PHPUnit 10.

## v1.0.6 - 2024-04-19

### Fixed

- Properly disabling VIP's alloptions protections during unit testing
  (previously applied in v1.0.5).

## v1.0.5 - 2024-04-18

### Changed

- Disable VIP's `pre_wp_load_alloptions_protections` protection during unit testing.

## v1.0.4 - 2024-04-17

### Added

- Define the `WP_RUN_CORE_TESTS` constant during unit tests.

### Fixed

- Ensure that dumping the content dumps non-json content,

## v1.0.3 - 2024-04-15

### Fixed

- Proper unset server headers when testing.

## v1.0.2 - 2024-04-15

### Added

- Added `html_string()` helper to make assertions against a HTML string easier in testing.
- Added new assertion methods to test against elements.

### Fixed

- Fixed incorrect status code when testing.
- Properly tear down the `$wp_the_query` global.

## v1.0.1 - 2024-04-09

### Fixed

- Changed the timing of the `set_up` method being called in tests to be after
  the database transaction is started.
- Allow other use of the `pre_http_request` filter when preventing external
  requests during testing.
- Fixed an issue with the streamed HTTP response not being converted to a
  `WP_Error` when needed.

## v1.0.0 - 2024-04-04

### Added

- Added support for PHP 8.3.
- Add support for querying against against enum values in the database.
- PHPUnit 10 support added and `nunomaduro/collision` depend on to v6-7. See
  [PHPUnit 10 Migration](#phpunit-10-migration) for more information.
- Adds database-specific collections with storage of the `found_rows` value.
- Added testing against `wp_mail()` calls.
- Added assertions for elements by query selector (`assertElementExistsByQuerySelector()` and `assertElementMissingByQuerySelector()`).
- Added `Hookable` support trait.
- Added support for authentication via an attribute on a test case/method.
- Added new `map()` method to the query builder.

### Changed

- Database queries against models now return an instance of
  `Mantle\Database\Query\Collection` which includes the `found_rows` value.
- Overhauled queue performance and added admin interface.
- Tests that make requests using `$this->get()` and other HTTP methods will now
  use a fluent pending request class `Mantle\Testing\Pending_Testable_Request`
  to allow for more complex request building.
- Upgraded Symfony components to 6.2.
- Allow meta to be set as an array on a model.

### Removed

- Removed support for PHP 8.0. The minimum PHP version is now 8.1.

### PHPUnit 10 Migration

When upgrading to Mantle v1 projects will receive `phpunit/phpunit` v10 and
`nunomaduro/collision` v7. PHPUnit 10 requires PSR-4 file structure for tests
(`tests/Feature/MyExampleTest.php` vs `tests/feature/test-my-example.php`). If
you have tests written in the old style, you will need to migrate them to PSR-4.
If you wish to continue using PHPUnit 9, you will need to downgrade to PHPUnit
9/Collision 6. To do so, run the following command:

    composer require --dev phpunit/phpunit:^9 nunomaduro/collision:^6 -W

To upgrade an existing test suite to PHPUnit 10 and PSR-4 standards, consider
using a [helper tool](https://github.com/alleyinteractive/wp-to-psr-4/). You
will also need to adjust your `phpunit.xml` file:

```diff
<phpunit
  bootstrap="tests/bootstrap.php"
  backupGlobals="false"
  colors="true"
  convertErrorsToExceptions="true"
  convertNoticesToExceptions="true"
  convertWarningsToExceptions="true"
-  printerClass="NunoMaduro\Collision\Adapters\Phpunit\Printer"
>
  <testsuites>
    <testsuite name="general">
-      <directory prefix="test-" suffix=".php">tests</directory>
+      <directory suffix="Test.php">tests</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

If you plan on using PHPUnit 10 and previously declared the `phpunit/phpunit`
version in your `composer.json` file, now would be a good time to remove it and
allow Mantle to manage that.

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
