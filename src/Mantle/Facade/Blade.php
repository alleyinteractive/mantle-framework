<?php
/**
 * Cache Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Mantle\View\Engines\Blade_Engine;

/**
 * Blade Facade
 *
 * @method static string getPath()
 * @method static void setPath(string $path)
 * @method static string compileString(string $value)
 * @method static string stripParentheses(string $expression)
 * @method static void extend(callable $compiler)
 * @method static array getExtensions()
 * @method static void if(string $name, callable $callback)
 * @method static bool check(string $name, mixed ...$parameters)
 * @method static array getClassComponentAliases()
 * @method static void anonymousComponentPath(string $path, string|null $prefix = null)
 * @method static void anonymousComponentNamespace(string $directory, string|null $prefix = null)
 * @method static array getAnonymousComponentPaths()
 * @method static array getAnonymousComponentNamespaces()
 * @method static array getClassComponentNamespaces()
 * @method static void aliasComponent(string $path, string|null $alias = null)
 * @method static void include(string $path, string|null $alias = null)
 * @method static void aliasInclude(string $path, string|null $alias = null)
 * @method static void bindDirective(string $name, callable $handler)
 * @method static void directive(string $name, callable $handler, bool $bind = false)
 * @method static array getCustomDirectives()
 * @method static \Illuminate\View\Compilers\BladeCompiler prepareStringsForCompilationUsing(callable $callback)
 * @method static void precompiler(callable $precompiler)
 * @method static void setEchoFormat(string $format)
 * @method static void withDoubleEncoding()
 * @method static void withoutDoubleEncoding()
 * @method static void withoutComponentTags()
 * @method static string getCompiledPath(string $path)
 * @method static bool isExpired(string $path)
 * @method static string newComponentHash(string $component)
 * @method static string compileClassComponentOpening(string $component, string $alias, string $data, string $hash)
 * @method static string compileEndComponentClass()
 * @method static mixed sanitizeComponentAttribute(mixed $value)
 * @method static string compileEndOnce()
 * @method static void stringable(string|callable $class, callable|null $handler = null)
 * @method static string compileEchos(string $value)
 * @method static string applyEchoHandler(string $value)
 *
 * @see \Illuminate\View\Compilers\BladeCompiler
 */
class Blade extends Facade {
	/**
	 * Facade Accessor
	 */
	protected static function get_facade_accessor(): string {
		return 'blade.compiler';
	}

	/**
	 * Alias for compileString().
	 *
	 * @param string $value The string to compile.
	 */
	public static function compile_string( string $value ): string {
		return static::compileString( $value );
	}

	/**
	 * Render a Blade template dynamically from a string.
	 *
	 * @throws \RuntimeException Thrown when application instance is not set.
	 *
	 * @param string               $template The uncompiled Blade template.
	 * @param array<string, mixed> $data The data to pass to the view.
	 */
	public static function render_string( string $template, array $data = [] ): string {
		if ( ! isset( static::$app ) ) {
			throw new \RuntimeException( 'Application instance is not set on Facade.' );
		}

		$resolver = static::$app['view.engine.resolver'];

		assert( $resolver instanceof \Mantle\View\Engines\Engine_Resolver );

		$blade_engine = $resolver->resolve( 'blade' );

		assert( $blade_engine instanceof Blade_Engine );

		return $blade_engine->render_string( $template, $data );
	}
}
