# Mantle Framework AI Coding Guidelines

## Overview
Mantle is a Laravel-inspired framework for WordPress, providing a modern application architecture while maintaining WordPress ecosystem compatibility. It uses PHP 8.2+ with snake_case naming conventions throughout.

Many features are ported directly from Laravel (Collections, Support classes, Container, etc.) but are translated to snake_case naming and adapted as needed for WordPress. While the ports generally maintain feature parity with Laravel, they are not strictly 1-to-1 implementations—methods and behavior are modified when necessary for WordPress integration or framework conventions.

## Project Goals

### Strong IDE Support
- **Prefer strongly typed returns over magic**: Use explicit return types and typed properties
- **Avoid magic methods when possible**: Prefer concrete, type-hinted methods for better IDE autocomplete
- **Type everything**: Parameters, returns, properties should all have explicit types for optimal IDE support
- **Generic annotations**: Extensive use of PHPStan templates helps IDEs understand collection types

### Delightful Testing Experience for WordPress
- **Seamless WordPress environment**: Full WordPress stack available in tests without manual setup
- **Familiar testing patterns**: Fluent assertions and Laravel-style testing helpers
- **Fast and reliable**: Clean database state between tests with `Refresh_Database` trait
- **WordPress-specific assertions**: Test WordPress queries, templates, and core functionality with specialized assertions

### PSR-4 Migration
The testing package is transitioning to PSR-4 standards with CamelCase file and class names (e.g., `FrameworkTestCase.php` instead of `class-framework-test-case.php`). **Mantle 2.0 will complete this migration**, with the entire framework adopting PSR-4 file naming conventions while maintaining snake_case method names for WordPress compatibility.

## Core Architecture Patterns

### Service Provider Lifecycle
Service providers are the central place for all application bootstrapping:
- `register()`: Register bindings to the container (called first for ALL providers)
- `boot()`: Perform additional bootstrapping after ALL providers registered
- `boot_provider()`: Final lifecycle method, executed after `boot()`
- Register providers in `config/app.php` under `providers[]` array
- Use `app()->get_provider(Provider::class)` to access registered providers

### Bootloader & Context Detection
The bootloader (`src/mantle/framework/class-bootloader.php`) detects execution context:
- `is_running_in_console()`: CLI execution via WP-CLI or Symfony Console
- `is_running_in_console_isolation()`: Unit test execution
- Different kernels load based on context: HTTP Kernel vs Console Kernel
- Bootstrap sequence: `Bootloader::create() → register_providers() → boot_providers()`

### Facade Pattern
Facades provide static access to container-bound services:
- Extend `Mantle\Facade\Facade` base class
- Implement `get_facade_accessor()` returning the container binding name
- Example: `Route::get()` → resolves `'router'` from container → calls method on instance
- Facades registered in `config/app.php` under `aliases[]` array

### Dependency Injection & Container
- Service container is Laravel's Illuminate Container
- Use constructor injection or `app()->make()` for resolution
- Bind interfaces to implementations in service provider's `register()` method
- Use `$this->app->singleton()` for shared instances
- Access application instance via `$this->app` in tests and service providers

## Model & Database

### Model Conventions
- Models extend `Mantle\Database\Model\Post` or `Mantle\Database\Model\Term`
- Use `Core_Object`, `Model_Meta`, and `Updatable` contracts
- Static methods: `Post::create()`, `Post::find()`, `Post::where()` return Query Builder
- Instance methods use snake_case: `$post->save()`, `$post->delete()`, `$post->fresh()`
- Register custom post types/taxonomies with `Registrable` interface

### Relationships
- Available relationships: `has_one()`, `has_many()`, `belongs_to()`, `belongs_to_many()`
- Relationships use internal taxonomy (`mantle_relationship`) for post-to-post relations
- Query relationships: `Post::has('comments')`, `Post::doesnt_have('author')`
- Eager loading: `Post::with('comments')->get()`
- Access via property: `$post->comments` (lazy loads if not eager loaded)

### Query Builder
- WordPress query wrapper with chainable methods
- Methods: `where()`, `where_in()`, `order_by()`, `limit()`, `offset()`, `get()`, `first()`
- Supports meta queries: `where_meta('key', 'value')`, `where_meta_in('key', [...])`
- Tax queries: `where_term('category', 'slug')`, `where_term_in()`
- Returns Collection of models or null

### Factories
- Access via `static::factory()->post->create(['title' => 'Test'])`
- Fluent interface: `->count(10)->create()`, `->as_models()->create()`
- Available: `post`, `page`, `attachment`, `term`, `user`, `comment`, `blog`, `network`
- Models can define custom factories with `Has_Factory` trait

## Testing

### Test Base Classes
- `FrameworkTestCase`: Full WordPress environment with database
- `MockeryTestCase`: Lightweight tests without WordPress
- Use `Refresh_Database` trait to reset database between tests

### Test Patterns
```php
class MyTest extends FrameworkTestCase {
    protected function setUp(): void {
        parent::setUp(); // ALWAYS call parent first
    }

    public function test_example() {
        $post = static::factory()->post->create(['title' => 'Test']);
        $this->get("/post/{$post->ID}")
            ->assertOk()
            ->assertSee('Test');
    }
}
```

### Assertions
- HTTP: `assertOk()`, `assertNotFound()`, `assertRedirect()`, `assertSee()`
- WordPress: `assertQueriedObject()`, `assertQueriedObjectId()`, `assertQueryTrue()`
- Standard PHPUnit assertions available

## Naming Conventions

### Critical: snake_case vs camelCase
- **Use snake_case for ALL method names**: `get_post()`, `has_many()`, `to_array()`
- This differs from Laravel (camelCase) for WordPress ecosystem compatibility
- Class names: PascalCase with underscores: `Has_One_Or_Many`, `Service_Provider`
- Variables and properties: snake_case: `$foreign_key`, `$this->local_key`
- Constants: SCREAMING_SNAKE_CASE: `RELATION_TAXONOMY`

### Type Hints & Static Analysis
- **Maximize type coverage**: Use PHP type hints (union types, generics) wherever possible
- **PHPStan is heavily used**: All code should pass PHPStan level 8 analysis
- **Generic annotations**: Use PHPStan generics extensively in PHPDoc (`@template`, `@param`, `@return`)
- **NEVER use `$this` in PHPDoc**: Always use `static` for return type annotations when returning the same class instance
- Example: `@return static` NOT `@return $this`
- **Prefer typed parameters**: Use `int|float`, `string|null`, etc. over untyped parameters
- **Document templates**: Use `@template TKey of array-key`, `@template TValue` for generic collections
- This ensures proper type inference, IDE support, and catches errors during static analysis

## Configuration

### Config Files
- Located in `config/` directory
- Access via `config('app.key')` helper
- Environment-specific: `config('app.env')` returns `'development'`, `'production'`, etc.
- Config caching available for production

### Environment Detection
- Use `app()->is_production()`, `app()->is_local()`, `app()->is_running_in_console()`
- Set via `WP_ENVIRONMENT_TYPE` constant or `wp_get_environment_type()`

## Monorepo Structure

### Package Management
- 24+ split packages managed by `symplify/monorepo-builder`
- Main packages: framework, database, support, http, testing, facades
- Each package has own composer.json with dependencies
- Run `composer install` at root to link all packages

### Build & Quality Tools
- `composer test`: Run PHPUnit test suite
- `composer lint`: Check code style with PHP_CodeSniffer
- `composer phpcbf`: Auto-fix code style issues
- `composer phpstan`: Static analysis (level 8)
- `composer rector`: Automated refactoring

## HTTP & Routing

### Route Registration
- Register in service provider's `boot()` method
- `Route::get('/path', [Controller::class, 'method'])`
- Support for WordPress rewrites and custom endpoints
- Middleware: `Route::middleware([Auth::class])->group()`

### Controllers
- Extend `Mantle\Http\Controller`
- Method injection for dependencies: `public function show(Request $request, Post $post)`
- Return views, JSON, or Response objects

### Middleware
- Applied globally in HTTP Kernel or per-route
- Create by implementing `Middleware` interface with `handle()` method
- Common: `Substitute_Bindings`, `Wrap_Template`

## Collections & Helpers

### Collections
- Laravel Collection API with snake_case: `collect()->map()->filter()->values()`
- Implements `Enumerable` interface matching Laravel 12.x functionality
- Methods: `each()`, `map()`, `filter()`, `reduce()`, `pluck()`, `where()`, etc.
- Lazy collections available for large datasets

### Global Helpers
- `app()`: Get application instance or resolve from container
- `config()`: Get configuration value
- `collect()`: Create collection from array
- `view()`: Return view instance
- `request()`: Get current HTTP request
- `response()`: Create HTTP response

## Key Differences from Laravel

1. **WordPress Integration**: Models wrap WP_Post, WP_Term, WP_User objects
2. **snake_case**: All methods use snake_case, not camelCase
3. **Bootloader**: Context-aware initialization (HTTP/Console/WP-CLI)
4. **No Eloquent ORM**: Custom query builder wrapping WP_Query/WP_Term_Query
5. **WordPress Lifecycle**: Hooks into WordPress init, not standalone
6. **Relationship Storage**: Uses taxonomy terms for post-to-post relationships
7. **Strong Type Coverage**: Extensive use of union types, generics in PHPDoc, and PHP type hints for PHPStan analysis

## Performance Considerations

- Use query builder `get()` vs `all()` to limit results
- Eager load relationships to avoid N+1: `Post::with('author')->get()`
- Cache config in production with config caching
- Use `Refresh_Database` trait in tests to maintain clean state
- Avoid querying in loops; use collections and single queries

## Common Pitfalls

1. **Forgetting parent::setUp()**: Always call in test setUp() methods
2. **camelCase methods**: Use snake_case: `has_many()` not `hasMany()`
3. **Missing service provider registration**: Add to `config/app.php`
4. **Relationship taxonomy**: Ensure `mantle_relationship` taxonomy exists
5. **Type hints**: Use `Core_Object&Model_Meta&Updatable&Model` for model types
6. **Context detection**: Check bootloader context before initializing console-only features
