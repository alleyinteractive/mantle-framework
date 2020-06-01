<?php
/**
 * Docs_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use League\CommonMark\GithubFlavoredMarkdownConverter as MarkdownConverter;
use Mantle\Framework\Service_Provider;
use Mantle\Framework\Helpers;
use Mantle\Framework\Support\Collection;
use Mantle\Framework\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Docs Service Provider
 */
class Docs_Service_Provider extends Service_Provider {
	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	public const MENU_SLUG = 'mantle_docs';

	/**
	 * Capability needed to show the Mantle Documentation Pages
	 *
	 * @var string
	 */
	protected $menu_capability;

	/**
	 * Collection of files.
	 *
	 * @var Collection
	 */
	protected $files;

	/**
	 * Register the service provider.
	 */
	public function boot() {
		$this->set_files();
		if ( $this->should_register_menu() ) {

			\add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		}
	}

	/**
	 * Register admin menu.
	 */
	public function register_admin_menu() {
		\add_menu_page(
			__( 'Mantle Docs', 'mantle' ),
			__( 'Mantle Docs', 'mantle' ),
			$this->get_menu_capability(),
			static::MENU_SLUG,
			[ $this, 'render' ]
		);

		foreach ( $this->get_pages() as $page ) {
			$title = $page['title'] ?? 'Unknown';

			\add_submenu_page(
				static::MENU_SLUG,
				$title,
				$title,
				$this->get_menu_capability(),
				'mantle_docs_' . $page['name'],
				[ $this, 'render' ]
			);
		}
	}

	/**
	 * Determine if the docs menu should be registered.
	 *
	 * @return bool
	 */
	protected function should_register_menu(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		/**
		 * Determine if the Mantle Documentation pages should show.
		 *
		 * @param bool $should_show Flag if the documentation page should show.
		 */
		return \apply_filters( 'mantle_should_show_docs', Helpers\is_local_env() );
	}

	/**
	 * Get the menu capability needed.
	 *
	 * @return string
	 */
	protected function get_menu_capability(): string {
		/**
		 * Capability needed to show the Mantle Documentation Pages.
		 *
		 * @param string $capability Capability needed.
		 */
		return (string) \apply_filters( 'mantle_docs_capability', 'manage_options' );
	}

	/**
	 * Render the documentation page.
	 *
	 * @throws Provider_Exception Thrown on missing page.
	 */
	public function render() {
		$current_page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$current_file = $this->files
			->filter(
				function( $file ) use ( $current_page ) {
					if ( static::MENU_SLUG === $current_page ) {
						return 'README' === $file['name'];
					}

					return 'mantle_docs_' . $file['name'] === $current_page;
				}
			)
			->first();

		if ( empty( $current_file ) ) {
			throw new Provider_Exception( 'Unknown documentation page: ' . $current_page );
		}

		$converter = new MarkdownConverter();
		$content   = $converter->convertToHtml( $this->get_file_contents( $current_file['path'] ) );

		printf(
			'<div class="wrap">%s</div>',
			wp_kses_post( $content )
		);
	}

	/**
	 * Get available pages.
	 * Removes all internal markdown files and the main README.
	 *
	 * @return Collection
	 */
	public function get_pages(): Collection {
		return $this->files->filter(
			function ( array $file ) {
				return ! Str::starts_with( $file['name'], '_' ) && 'README' !== $file['name'];
			}
		);
	}

	/**
	 * Load the list of files.
	 *
	 * @todo Allow this to be abstracted a bit more and extended by other service providers.
	 */
	protected function set_files() {
		$this->files = new Collection();

		foreach ( Finder::create()->files()->name( '*.md' )->in( $this->get_docs_path() ) as $file ) {
			$this->files[] = [
				'name'  => pathinfo( $file->getFilename(), PATHINFO_FILENAME ),
				'path'  => $file->getRealPath(),
				'title' => $this->get_file_title( $file->getRealPath() ),
			];
		}

		return $this->files;
	}

	/**
	 * Get the file title.
	 *
	 * @param string $file_path File path.
	 * @return string|null
	 */
	protected function get_file_title( string $file_path ): ?string {
		$contents = $this->get_file_contents( $file_path );
		preg_match( '/^(.*)={5,}/s', $contents, $matches );
		if ( empty( $matches ) ) {
			return null;
		}

		return explode( PHP_EOL, $matches[0] )[0] ?? null;
	}

	/**
	 * Get the file contents from a documentation file.
	 *
	 * @param string $file_path Path to file.
	 * @return string
	 */
	protected function get_file_contents( string $file_path ): string {
		return file_get_contents( $file_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	}

	/**
	 * Get the path to the docs folder.
	 *
	 * @return string
	 */
	protected function get_docs_path(): string {
		return $this->app->get_base_path( 'docs' );
	}
}
