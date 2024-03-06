<?php
/**
 * Controller_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Database\Model\Model;
use Mantle\Http\Controller;
use Mantle\Http\Request;
use Mantle\Support\Str;
use Nette\PhpGenerator\PhpFile;

/**
 * Controller Generator
 */
class Controller_Make_Command extends Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:controller';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a controller.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Http\Controller';

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = '{name : The name of the controller.}
		{--invokable                : Flag if the model should be invokable.}
		{--entity                   : Flag to generate an entity controller.}
		{--entity-model=            : Entity controller model.}';

	/**
	 * Build the generated file.
	 *
	 * @param string $name Class name to generate.
	 * @return string
	 */
	public function get_generated_class( string $name ): string {
		$class_name     = $this->get_class_name( $name );
		$namespace_name = Str::untrailing_slash( str_replace( '\\\\', '\\', $this->get_namespace( $name ) ) );

		$file = new PhpFile();

		$namespace = $file
			->addComment( "$class_name class file.\n\n@package $namespace_name" )
			->addNamespace( $namespace_name )
			->addUse( Controller::class );

		$class = $namespace
			->addClass( $class_name )
			->addComment( "$class_name class." )
			->setExtends( Controller::class );

		if ( $this->option( 'invokable' ) ) {
			$namespace->addUse( Request::class );
			$class
				->addMethod( '__invoke' )
				->addComment( 'Handle the incoming request.' )
				->addComment( '' )
				->addComment( '@param Request $request Request object.' )
				->setBody( '//' )
				->setVisibility( 'public' )
				->addParameter( 'request' )
				->setType( Request::class );
		} elseif ( $this->option( 'entity' ) ) {
			// Attempt to determine the model for the entity.
			$entity = $this->get_model_entity();
			$namespace->addUse( $entity );
			$param = strtolower( basename( str_replace( '\\', '/', $entity ) ) );

			$class
				->addMethod( 'show' )
				->addComment( 'Handle the single request.' )
				->addComment( '' )
				->addComment( "@param {$entity} \${$param} Model instance." )
				->setBody( '//' )
				->setVisibility( 'public' )
				->addParameter( $param )
				->setType( $entity );

			$class
				->addMethod( 'index' )
				->addComment( 'Handle the index request.' )
				->setBody( '//' )
				->setVisibility( 'public' );
		}

		return ( new Printer() )->printFile( $file );
	}

	/**
	 * Get the model to use for an entity controller.
	 *
	 * @return string
	 */
	protected function get_model_entity(): string {
		// Attempt to determine the model for the entity.
		$entity = $this->option( 'entity-model', $this->argument( 'name' ) );

		// Attempt to find the model if the one passed is not a model instance.
		if ( class_exists( $entity ) && is_subclass_of( $entity, Model::class ) ) {
			return $entity;
		}

		return 'App\Models\\' . str_replace( '_Controller', '', $entity );
	}

	/**
	 * Command synopsis.
	 *
	 * @param string $name Class name.
	 */
	public function complete_synopsis( string $name ): void {
		$this->log(
			PHP_EOL . sprintf(
				'Controller created [%s\%s]',
				$this->get_namespace( $name ),
				$this->get_class_name( $name )
			)
		);
	}
}
