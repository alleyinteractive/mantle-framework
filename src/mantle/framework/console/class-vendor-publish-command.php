<?php
/**
 * Vendor_Publish_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Filesystem\Filesystem;
use Mantle\Support\Service_Provider;
use Symfony\Component\Finder\Finder;

use function Mantle\Support\Helpers\collect;

/**
 * Vendor Publish Command
 */
class Vendor_Publish_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'vendor:publish';

	/**
	 * Command description.
	 *
	 * @var string
	 */
	protected $description = 'Publish any publishable assets from vendor packages';

	/**
	 * Filesystem instance.
	 *
	 * @var Filesystem
	 */
	protected Filesystem $filesystem;

	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'vendor:publish
		{--force : Overwrite any existing files.}
		{--providers=* : One or many specific providers that have assets you want to publish.}
		{--tags=* : One or many tags that have assets you want to publish.}
		{--list : List all tags that can be published.}
		{--all : Publish assets for all tags.}';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		// Hide the command in isolation mode.
		if ( app()->is_running_in_console_isolation() ) {
			$this->setHidden( true );
		}
	}

	/**
	 * Publish any publishable assets from vendor packages.
	 *
	 * @param Filesystem $filesystem Filesystem instance.
	 */
	public function handle( Filesystem $filesystem ): int {
		$this->filesystem = $filesystem;

		if ( $this->option( 'list' ) ) {
			$this->list_publishable_assets();

			return Command::SUCCESS;
		}

		// Ensure that either providers/tags are specified or the --all flag is used.
		if (
			empty( $this->option( 'providers' ) )
			&& empty( $this->option( 'tags' ) )
			&& empty( $this->option( 'all' ) )
		) {
			$this->error( 'Please specify at least one provider or tag with --providers or --tags.' );

			return Command::FAILURE;
		}

		if ( $this->option( 'all' ) ) {
			$paths = Service_Provider::paths_to_publish();
		} else {
			$paths = Service_Provider::paths_to_publish(
				providers: $this->has_option( 'providers' )
					? collect( (array) $this->option( 'providers' ) )->map( 'trim' )->all()
					: null,
				tags: $this->has_option( 'tags' )
					? collect( (array) $this->option( 'tags' ) )->map( 'trim' )->all()
					: null,
			);
		}

		if ( empty( $paths ) ) {
			$this->error( 'There are no assets to publish.' );

			return Command::FAILURE;
		}

		foreach ( $paths as $from => $to ) {
			$this->publish_item( $from, $to );
		}

		return Command::SUCCESS;
	}

	/**
	 * Helper to list publishable assets.
	 */
	protected function list_publishable_assets(): void {
		$providers = collect( Service_Provider::publishable_providers() )->sort();
		$tags      = collect( Service_Provider::publishable_tags() )->sort();

		if ( $providers->is_empty() && $tags->is_empty() ) {
			$this->error( 'There are no assets to publish.' );

			return;
		}

		if ( ! $providers->is_empty() ) {
			$this->line( $this->colorize( 'Publishable service providers:', 'green' ) );

			foreach ( $providers as $provider ) {
				$this->line( " - {$provider}" );
			}

			$this->line( '' );
		}

		if ( ! $tags->is_empty() ) {
			$this->line( $this->colorize( 'Publishable tags:', 'green' ) );

			foreach ( $tags as $tag ) {
				$this->line( " - {$tag}" );
			}

			$this->line( '' );
		}
	}

	/**
	 * Publish the given item.
	 *
	 * @param string $from Path to publish from.
	 * @param string $to   Path to publish to.
	 */
	protected function publish_item( string $from, string $to ): void {
		if ( $this->filesystem->is_file( $from ) ) {
			$this->publish_file( $from, $to );
		} elseif ( $this->filesystem->is_directory( $from ) ) {
			$this->publish_directory( $from, $to );
		} else {
			$this->error( "Can't locate path: <{$from}>" );
		}
	}

	/**
	 * Publish the given file.
	 *
	 * @param string $from Path to publish from.
	 * @param string $to   Path to publish to.
	 */
	protected function publish_file( string $from, string $to ): void {
		if ( $this->filesystem->exists( $to ) && ! $this->option( 'force' ) ) {
			$this->error( "File already exists at <{$to}>. Use --force to overwrite." );

			return;
		}

		$this->filesystem->copy( $from, $to );

		$this->status( $from, $to, 'File' );
	}

	/**
	 * Publish the given directory.
	 *
	 * @param string $from Path to publish from.
	 * @param string $to   Path to publish to.
	 */
	protected function publish_directory( string $from, string $to ): void {
		if ( $this->filesystem->is_directory( $to ) && ! $this->option( 'force' ) ) {
			$this->error( "Directory already exists at <{$to}>. Use --force to overwrite." );

			return;
		}

		$this->filesystem->ensure_directory_exists( $to );

		$files = ( new Finder() )
			->ignoreDotFiles( true )
			->ignoreUnreadableDirs()
			->ignoreVCS( true )
			->depth( '< 10' )
			->in( $from );

		if ( ! $files->hasResults() ) {
			$this->error( "No files found at <{$from}>." );

			return;
		}

		foreach ( $files as $file ) {
			$relative_path = ltrim( substr( $file->getPathname(), strlen( $from ) ), '/' );
			$to_path       = $to . '/' . $relative_path;

			if ( $file->isDir() ) {
				$this->filesystem->ensure_directory_exists( $to_path );
			} else {
				$this->filesystem->copy( $file->getRealPath(), $to_path );
			}
		}

		$this->status( $from, $to, 'Directory' );
	}

	/**
	 * Write a status message to the console.
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  string $type
	 */
	protected function status( string $from, string $to, string $type ): void {
		$from = str_replace( base_path() . '/', '', realpath( $from ) );

		$to = str_replace( base_path() . '/', '', realpath( $to ) );

		$this->info(
			sprintf(
				'Copying %s [%s] to [%s]',
				$type,
				$from,
				$to,
			)
		);
	}
}
