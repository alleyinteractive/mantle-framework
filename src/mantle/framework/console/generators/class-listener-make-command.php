<?php
/**
 * Listener_Make_Command class file
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Nette\PhpGenerator\PhpFile;

/**
 * Event Listener Generator Command
 */
class Listener_Make_Command extends Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:listener';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate an event listener.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Listeners';

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = '{name} {event}';

	/**
	 * Build the generated file.
	 *
	 * @param string $name Class name to generate.
	 * @return string
	 */
	public function get_generated_class( string $name ): string {
		$class_name             = $this->get_class_name( $name );
		$namespace_name         = untrailingslashit( str_replace( '\\\\', '\\', $this->get_namespace( $name ) ) );
		$event_class            = $this->argument( 'event', str_replace( '_Listener', '_Event', $class_name ) );
		$event_class_namespaced = $this->container->config->get( 'app.namespace', 'App' ) . '\Events\\' . $event_class;

		$file = new PhpFile();

		$namespace = $file
			->addComment( "$class_name class file.\n\n@package $namespace_name" )
			->addNamespace( $namespace_name )
			->addUse( $event_class_namespaced );

		$class = $namespace
			->addClass( $class_name )
			->addComment( "$class_name class." );

		$class
			->addMethod( '__construct' )
			->addComment( 'Create the event listener.' )
			->addComment( '' )
			->addComment( '@return void' )
			->setBody( '//' )
			->setVisibility( 'public' );

		$class
			->addMethod( 'handle' )
			->addComment( 'Handle the event.' )
			->addComment( '' )
			->addComment( sprintf( '@param %s $event', $event_class ) )
			->addComment( '@return void' )
			->setBody( '//' )
			->setVisibility( 'public' )
			->addParameter( 'event' )
			->setType( $event_class_namespaced );

		$class
			->addMethod( 'on_example_hook' )
			->addComment( 'Handle the WordPress hook: example_hook.' )
			->addComment( '' )
			->addComment( '@return void' )
			->setBody( '//' )
			->setVisibility( 'public' );

		return ( new Printer() )->printFile( $file );
	}
}
