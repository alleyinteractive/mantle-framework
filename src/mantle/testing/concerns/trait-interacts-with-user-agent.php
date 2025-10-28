<?php
/**
 * Interacts_With_User_Agent trait file
 *
 * phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Attributes\UserAgent;

use function Mantle\Support\Helpers\collect;

/**
 * Mock the User Agent in tests with attributes.
 *
 * @mixin \PHPUnit\Framework\TestCase
 * @mixin Makes_Http_Requests
 */
trait Interacts_With_User_Agent {
	use Reads_Annotations;

	/**
	 * Flag if the user agent was set.
	 */
	private ?string $set_user_agent = null;

	/**
	 * Set up the user agent for the test.
	 */
	protected function interacts_with_user_agent_set_up(): void {
		$attributes = collect( $this->get_attributes_for_method( UserAgent::class ) );

		if ( ! $attributes->is_empty() ) {
			$ua = $attributes->first()?->newInstance()->ua;

			if ( $ua ) {
				$this->set_user_agent( $ua );

				// Set the user agent for any request being made.
				$this->before_request( function (): void {
					$_SERVER['HTTP_USER_AGENT'] = $this->set_user_agent;
				} );
			}
		}
	}

	/**
	 * Tear down the user agent for the test.
	 */
	protected function interacts_with_user_agent_tear_down(): void {
		if ( $this->set_user_agent ) {
			$this->set_user_agent = null;

			unset( $_SERVER['HTTP_USER_AGENT'] );
		}
	}

	/**
	 * Set the user agent.
	 *
	 * @param string $user_agent The user agent to set.
	 */
	public function set_user_agent( string $user_agent ): void {
		$this->set_user_agent = $user_agent;

		$_SERVER['HTTP_USER_AGENT'] = $user_agent;
	}
}
