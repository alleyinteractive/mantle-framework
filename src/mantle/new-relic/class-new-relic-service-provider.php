<?php
/**
 * New_Relic_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\New_Relic;

use Mantle\Contracts\Application;
use Mantle\Contracts\Events\Dispatcher;
use Mantle\Http\Routing\Events\Route_Matched;
use Mantle\Http\Routing\Route;
use Mantle\Support\Service_Provider;
use WP;

use function Mantle\Support\Helpers\collect;

/**
 * New Relic Service Provider
 */
class New_Relic_Service_Provider extends Service_Provider {
	/**
	 * Events dispatcher.
	 *
	 * @var Dispatcher
	 */
	protected $events;

	/**
	 * Flag if the transaction was already named to prevent duplicates.
	 *
	 * @var bool
	 */
	protected $named = false;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		parent::__construct( $app );

		$this->events = $app['events'];
	}

	/**
	 * Boot the provider.
	 */
	public function boot(): void {
		parent::boot();

		if ( ! static::is_supported() ) {
			return;
		}

		$this->events->listen( Route_Matched::class, [ $this, 'handle_route_matched' ] );
	}

	/**
	 * Check if the extension is supported.
	 */
	protected static function is_supported(): bool {
		return extension_loaded( 'newrelic' )
			&& function_exists( 'newrelic_add_custom_parameter' )
			&& function_exists( 'newrelic_name_transaction' );
	}

	/**
	 * Handle the Route Matched event.
	 *
	 * @param Route_Matched $event Event instance.
	 */
	public function handle_route_matched( Route_Matched $event ): void {
		if ( $event->route instanceof Route ) {
			newrelic_name_transaction( $event->route->getPath() );
			newrelic_add_custom_parameter( 'mantle-request', true );
			newrelic_add_custom_parameter( 'mantle-route-method', implode( ' ', $event->route->getMethods() ) );
		} elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$route = collect(
				[
					$event->route['namespace'] ?? '',
					$event->route['route'] ?? '',
				]
			)
				->unique()
				->join( '/' );

			$name = preg_replace(
				'/\(\?P(<\w+?>).*?\)/',
				'$1',
				$route
			);

			newrelic_name_transaction( $name );
			newrelic_add_custom_parameter( 'wp-api', 'true' );
			newrelic_add_custom_parameter( 'wp-api-route', $route );
		}

		newrelic_add_custom_parameter( 'logged-in', \is_user_logged_in() );

		$this->named = true;
	}

	/**
	 * Handle the 'wp' event to set the transaction name.
	 *
	 * @param WP $wp Global WP object.
	 */
	public function on_wp( WP $wp ): void {
		if ( $this->named || ! static::is_supported() ) {
			return;
		}

		if (
			! empty( $wp->query_vars['rest_route'] )
			|| is_admin()
			|| wp_doing_cron()
			|| $this->app->is_running_in_console()
		) {
			return;
		}

		switch ( true ) {
			case is_feed():
				$feed_type = get_query_var( 'feed' );
				if ( 'feed' !== $feed_type ) {
					$name = "feed.{$feed_type}";
				} else {
					$name = 'feed';
				}

				newrelic_add_custom_parameter( 'feed', 'true' );
				break;
			case is_embed():
				$name = 'embed';
				newrelic_add_custom_parameter( 'embed', get_query_var( 'embed' ) );
				break;
			case is_404():
				$name = 'error-404';
				break;
			case is_search():
				$name = 'search';
				newrelic_add_custom_parameter( 's', get_query_var( 's' ) );
				break;
			case is_front_page():
			case is_home():
				$name = 'homepage';
				break;
			case is_privacy_policy():
				$name = 'privacy_policy';
				break;
			case is_post_type_archive():
				$name = 'post_type_archive.' . get_query_var( 'post_type' );
				break;
			case is_tax():
			case is_category():
			case is_tag():
				$name = 'taxonomy';
				$term = get_queried_object();
				if ( $term instanceof \WP_Term ) {
					$name .= ".{$term->taxonomy}";
					newrelic_add_custom_parameter( 'term_id', $term->term_id );
					newrelic_add_custom_parameter( 'slug', $term->slug );
				}
				break;
			case is_attachment():
				$name = 'attachment';
				break;
			case is_single():
			case is_page():
			case is_singular():
				$name = 'post';
				$post = get_queried_object();
				if ( $post instanceof \WP_Post ) {
					if ( 'post' !== $post->post_type ) {
						$name .= ".{$post->post_type}";
					}

					newrelic_add_custom_parameter( 'post_id', $post->ID );
				}
				break;
			case is_author():
				$name = 'author_archive';
				break;
			case is_date():
				$name = 'date_archive';
				break;
			case is_archive():
				$name = 'archive';
				break;
		}

		if ( ! empty( $name ) ) {
			newrelic_name_transaction( $name );
		}

		newrelic_add_custom_parameter( 'logged-in', \is_user_logged_in() );

		if ( is_paged() ) {
			newrelic_add_custom_parameter( 'paged', 'true' );
			newrelic_add_custom_parameter( 'page', get_query_var( 'paged' ) );
		}

		$this->named = true;
	}
}
