<?php
/**
 * Hides_Attributes trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Concerns;

use Closure;
use function Mantle\Support\Helpers\value;

/**
 * Concern to hide attributes from serialization.
 */
trait Hides_Attributes {

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var string[]
	 */
	protected $hidden = [];

	/**
	 * The attributes that should be visible in serialization.
	 *
	 * @var string[]
	 */
	protected $visible = [];

	/**
	 * Get the hidden attributes for the model.
	 *
	 * @return string[]
	 */
	public function get_hidden(): array {
		return $this->hidden;
	}

	/**
	 * Set the hidden attributes for the model.
	 *
	 * @param string ...$hidden Hidden attributes.
	 */
	public function set_hidden( string ...$hidden ): static {
		$this->hidden = $hidden;

		return $this;
	}

	/**
	 * Get the visible attributes for the model.
	 *
	 * @return string[]
	 */
	public function get_visible(): array {
		return $this->visible;
	}

	/**
	 * Set the visible attributes for the model.
	 *
	 * @param string ...$visible Visible attributes.
	 */
	public function set_visible( string ...$visible ): static {
		$this->visible = $visible;

		return $this;
	}

	/**
	 * Make the given, typically hidden, attributes visible.
	 *
	 * @param string ...$attributes Attributes to make visible.
	 */
	public function make_visible( string ...$attributes ): static {
		$this->hidden = array_diff( $this->hidden, $attributes );

		if ( ! empty( $this->visible ) ) {
			$this->visible = array_merge( $this->visible, $attributes );
		}

		return $this;
	}

	/**
	 * Make the given, typically hidden, attributes visible if the given truth test passes.
	 *
	 * @param  bool|Closure $condition Condition to check.
	 * @param  string       ...$attributes Attributes to make visible.
	 */
	public function make_visible_if( mixed $condition, string ...$attributes ): static {
		$condition = $condition instanceof Closure ? $condition( $this ) : $condition;

		return $condition ? $this->make_visible( ...$attributes ) : $this;
	}

	/**
	 * Make the given, typically visible, attributes hidden.
	 *
	 * @param  string ...$attributes Attributes to make hidden.
	 */
	public function make_hidden( string ...$attributes ): static {
		$this->hidden = array_merge( $this->hidden, $attributes );

		return $this;
	}

	/**
	 * Make the given, typically visible, attributes hidden if the given truth test passes.
	 *
	 * @param  bool|Closure $condition Condition to check.
	 * @param  string       ...$attributes Attributes to make hidden.
	 */
	public function make_hidden_if( mixed $condition, string ...$attributes ): static {
		$condition = $condition instanceof Closure ? $condition( $this ) : $condition;

		return value( $condition ) ? $this->make_hidden( ...$attributes ) : $this;
	}
}
