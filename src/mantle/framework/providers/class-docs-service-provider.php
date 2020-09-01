<?php
/**
 * Docs_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
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
			[ $this, 'render' ],
			'dashicons-cloud'
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

		$env = Environment::createGFMEnvironment();
		$env->addExtension( new HeadingPermalinkExtension() );

		$converter = new CommonMarkConverter(
			[
				'heading_permalink' => [
					'symbol' => '#',
				],
			],
			$env
		);
		$content   = $converter->convertToHtml( $this->get_file_contents( $current_file['path'] ) );

		?>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.20.0/themes/prism-solarizedlight.min.css" rel="stylesheet" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.20.0/prism.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.20.0/plugins/autoloader/prism-autoloader.min.js"></script>
		<link href="https://fonts.googleapis.com/css2?family=Source+Code+Pro&family=Open+Sans:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
		<style type="text/css">
		.mantle-docs-wrap {
			font-family: 'Open Sans', sans-serif;
			margin-left: 10px;
		}

		.mantle-docs-wrap p {
			font-size: 15px;
			max-width: 800px;
		}

		.mantle-docs-wrap h2,
		.mantle-docs-wrap h3 {
			margin: 2em 0 1em;
		}

		.mantle-docs-wrap h2 {
			font-size: 1.8em
		}

		.mantle-docs-wrap p code {
			background: rgb(247, 250, 252);
			border-radius: 4px;
			border: 1px solid rgb(227, 232, 238);
			color: #e3423b;
			font-family: 'Source Code Pro', monospace, sans-serif;
		}

		.mantle-docs-wrap pre, .mantle-docs-wrap table {
			background: #fbfbfd;
			box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075);
			font-family: 'Source Code Pro', monospace, sans-serif;
			max-width: 1000px;
			padding: 20px;
		}

		.mantle-docs-wrap table {
			padding: 0;
		}

		.mantle-docs-wrap table td,
		.mantle-docs-wrap table th {
			padding: 10px;
		}

		.mantle-docs-wrap table th {
			font-family: 'Open Sans', sans-serif;
			text-align: left;
		}

		.mantle-docs-wrap pre code {
			background: transparent;
			color: #090910;
			font-size: .8rem;
			font-weight: 500;
			line-height: 1.9;
			padding: 0;
		}

		.mantle-docs-wrap code[class*="language-"],
		.mantle-docs-wrap pre[class*="language-"] {
			font-family: 'Source Code Pro', monospace, sans-serif;
			font-size: .8rem;
			font-weight: 500;
			line-height: 1.9;
		}

		.mantle-docs-wrap h1 + ul ul {
			margin-top: 0.5em;
			padding: 0;
		}

		.mantle-docs-wrap h1 + ul ul li {
			padding-left: 1.5em;
		}

		.mantle-docs-wrap h1 + ul li {
			display: block;
			margin-bottom: 0.5em;
		}

		.mantle-docs-wrap a.heading-permalink {
			color: #00000038;
			float: left;
			line-height: 1;
			margin-left: -20px;
			margin-top: -5px;
			padding-right: 4px;
			text-decoration: none;
			visibility: hidden;
		}

		.mantle-docs-wrap h1:hover a.heading-permalink,
		.mantle-docs-wrap h2:hover a.heading-permalink,
		.mantle-docs-wrap h3:hover a.heading-permalink,
		.mantle-docs-wrap h4:hover a.heading-permalink,
		.mantle-docs-wrap h5:hover a.heading-permalink,
		.mantle-docs-wrap h6:hover a.heading-permalink {
			visibility: visible;
		}

		@media screen and (min-width: 55em) {
			.mantle-docs-wrap,
			.mantle-docs-wrap p {
				font-size: 1rem;
			}
		}
		</style>
		<?php

		printf(
			'<div class="wrap mantle-docs-wrap">%s</div>',
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

		foreach ( Finder::create()->files()->name( '*.md' )->in( $this->get_docs_path() )->sortByName() as $file ) {
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
