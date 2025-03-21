<?php
/**
 * Crawler class file
 *
 * @package Mantle
 */

namespace Mantle\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use InvalidArgumentException;
use Mantle\Contracts\Support\Htmlable;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;

use function Mantle\Support\Helpers\classname;
use function Mantle\Support\Helpers\stringable;

/**
 * HTML Crawler class for parsing and manipulating HTML documents.
 *
 * This class extends the Symfony DomCrawler and provides additional
 * methods for querying and modifying HTML elements using CSS selectors
 * and XPath expressions. It also includes methods for setting and
 * removing attributes, adding and removing classes, and modifying
 * elements using callback functions.
 *
 * @link https://symfony.com/doc/current/components/dom_crawler.html
 */
class Crawler extends SymfonyCrawler implements Htmlable {
	/**
	 * Constructor.
	 *
	 * @param \DOMNodeList|\DOMNode|array|string|null|null $node The DOM node, list, or HTML string to initialize the crawler with.
	 */
	public function __construct( \DOMNodeList|\DOMNode|array|string|null $node = null, ...$args ) {
		if ( is_string( $node ) ) {
			$document = new DOMDocument( '1.0' );

			$previous = libxml_use_internal_errors( true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			$document->preserveWhiteSpace = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$document->formatOutput       = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			@$document->loadHTML( $node, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Generic.PHP.NoSilencedErrors.Forbidden

			libxml_use_internal_errors( $previous );

			$node = $document->documentElement;
		}

		parent::__construct( $node, ...$args );
	}

	/**
	 * Query the document for all elements matching a CSS selector.
	 *
	 * @param string $selector
	 */
	public function get_by_selector( string $selector ): static {
		return $this->filter( $selector );
	}

	/**
	 * Query the document for a single element matching a CSS selector.
	 *
	 * @param string $selector
	 */
	public function first_by_id( string $id ): static {
		return $this->filter( "#{$id}" )->first();
	}

	/**
	 * Query the document for a single element using a tag name.
	 *
	 * @param string $tag The tag name to match.
	 */
	public function first_by_tag( string $tag ): static {
		return $this->filter( $tag )->first();
	}

	/**
	 * Query the document for a single element using a class name.
	 *
	 * @param string $class The class name to match.
	 */
	public function first_by_testid( string $test_id ): static {
		return $this->filter( "[data-testid=\"{$test_id}\"]" )->first();
	}

	/**
	 * Query the document for a single element using a CSS selector.
	 *
	 * @param string $selector The CSS selector to match.
	 */
	public function first_by_selector( string $selector ): static {
		return $this->filter( $selector )->first();
	}

	/**
	 * Retrieve all elements matching an XPath expression.
	 *
	 * @param string $xpath XPath expression to match.
	 */
	public function get_by_xpath( string $xpath ): static {
		return $this->filterXPath( $xpath );
	}

	/**
	 * Retrieve all elements matching a specific tag name.
	 *
	 * @param string $tag The tag name to match.
	 */
	public function get_by_tag( string $tag ): static {
		return $this->filter( $tag );
	}

	/**
	 * Retrieve all elements matching a specific test ID (data-testid) attribute.
	 *
	 * @param string $xpath XPath expression to match.
	 */
	public function get_by_testid( string $test_id ): static {
		return $this->filter( "[data-testid=\"{$test_id}\"]" );
	}

	/**
	 * Query the document for a single element using an XPath expression.
	 *
	 * @param string $xpath
	 */
	public function first_by_xpath( string $xpath ): static {
		return $this->filterXPath( $xpath )->first();
	}

	/**
	 * Modify the elements matching a given selector using a callback function.
	 *
	 * @param string   $selector CSS selector to match.
	 * @param callable $callback A callback function that receives the matched element and its index.
	 * @phpstan-param callable(Crawler $crawler, int $i): (DOMNode|string|null) $callback
	 */
	public function modify( string $selector, callable $callback ): static {
		$converter = new CssSelectorConverter( true );

		return $this->modify_xpath( $converter->toXPath( $selector ), $callback );
	}

	/**
	 * Modify the elements matching a given XPath expression using a callback function.
	 *
	 * @param string   $xpath XPath expression to match.
	 * @param callable $callback A callback function that receives the matched element and its index.
	 * @phpstan-param callable(Crawler $crawler, int $i): (DOMNode|string|null) $callback
	 */
	public function modify_xpath( string $path, callable $callback ): static {
		$this->filterXPath( $path )->each( function ( Crawler $item, int $index ) use ( $callback ): Crawler {
			$result = $callback( $item, $index );

			// If the callback returns null, we can assume the callback modified the
			// node and we can return it as-is.
			if ( null === $result || $result instanceof static ) {
				return $item;
			}

			// Convert the result to a DOMNode if it's a string.
			if ( is_string( $result ) ) {
				$result = ( new static( $result ) )->getNode( 0 );
			}

			// If the callback did return something and it's a DOMNode we'll assume
			// they want to replace the node with the new one.
			if ( $result instanceof DOMNode ) {
				// If the new node is the same as the existing one, return the item.
				if ( $result === $item->getNode( 0 ) ) {
					return $item;
				}

				$node = $item->getNode( 0 );

				$node->parentNode->replaceChild( static::import_new_node( $result, $node ), $node );

				return $item;
			}

			throw new InvalidArgumentException( 'Callback must return null or a DOMNode instance.' );
		} );

		return $this;
	}

	/**
	 * Set an attribute for all elements in the Crawler instance.
	 *
	 * @param string $name  The name of the attribute to set.
	 * @param string $value The value to set for the attribute.
	 */
	public function set_attribute( string $name, string $value ): static {
		foreach ( $this as $node ) {
			if ( $node instanceof DOMElement ) {
				$node->setAttribute( $name, $value );
			}
		}

		return $this;
	}

	/**
	 * Set a data attribute for all elements in the Crawler instance.
	 *
	 * @param string $name  The name of the data attribute to set (without "data-" prefix).
	 * @param string $value The value to set for the data attribute.
	 */
	public function set_data( string $name, string $value ): static {
		return $this->set_attribute( "data-{$name}", $value );
	}

	/**
	 * Remove an attribute from all elements in the Crawler instance.
	 *
	 * @param string $name
	 */
	public function remove_attribute( string $name ): static {
		foreach ( $this as $node ) {
			if ( $node instanceof DOMElement ) {
				$node->removeAttribute( $name );
			}
		}

		return $this;
	}

	/**
	 * Remove a data attribute from all elements in the Crawler instance.
	 *
	 * @param string $name The name of the data attribute to remove (without "data-" prefix).
	 */
	public function remove_data( string $name ): static {
		return $this->remove_attribute( "data-{$name}" );
	}

	/**
	 * Get the value of a data attribute for the first element in the Crawler instance.
	 *
	 * @param string $name The name of the data attribute to retrieve (without "data-" prefix).
	 * @return string|null The value of the data attribute, or null if not found.
	 */
	public function get_data( string $name ): ?string {
		return $this->attr( "data-{$name}" );
	}

	/**
	 * Add a class to all elements in the Crawler instance.
	 *
	 * @param string ...$class
	 */
	public function add_class( string ...$class ): static {
		$class = Arr::wrap( $class );

		foreach ( $this as $node ) {
			if ( $node instanceof DOMElement ) {
				$node->setAttribute( 'class', classname( $node->getAttribute( 'class' ), ...$class ) );
			}
		}

		return $this;
	}

	/**
	 * Remove a class from all elements in the Crawler instance.
	 *
	 * @param string ...$class Class names to remove from the elements.
	 */
	public function remove_class( string ...$class ): static {
		$class = Arr::wrap( $class );

		foreach ( $this as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$existing = stringable( $node->getAttribute( 'class' ) )->explode( ' ' );

			// Remove the classes from the existing class list.
			$value = $existing->diff( $class )->implode( ' ' );

			if ( empty( $value ) ) {
				$node->removeAttribute( 'class' );
			} else {
				$node->setAttribute( 'class', $value );
			}
		}

		return $this;
	}

	/**
	 * Check if any of the elements in the Crawler instance have a specific class.
	 *
	 * @param string ...$class Class names to check for.
	 */
	public function has_class( string ...$class ): bool {
		$class = Arr::wrap( $class );

		foreach ( $this as $node ) {
			if ( $node instanceof DOMElement ) {
				$existing = stringable( $node->getAttribute( 'class' ) )->explode( ' ' );

				if ( $existing->intersect( $class )->count() === count( $class ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if any of the elements in the Crawler instance have any of the
	 * specified classes.
	 *
	 * @param string ...$class Class names to check for.
	 */
	public function has_any_class( string ...$class ): bool {
		$class = Arr::wrap( $class );

		foreach ( $this as $node ) {
			if ( $node instanceof DOMElement ) {
				$existing = stringable( $node->getAttribute( 'class' ) )->explode( ' ' );

				if ( $existing->intersect( $class )->is_not_empty() ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Remove all elements matching a given CSS selector from the document.
	 *
	 * @param string $selector CSS selector to match.
	 */
	public function remove( string $selector ): static {
		$this->filter( $selector )->each( function ( Crawler $item ): void {
			$node = $item->getNode( 0 );

			if ( $node && $node->parentNode ) {
				$node->parentNode->removeChild( $node );
			}
		} );

		return $this;
	}

	// public function prepend()

	// public function append()

	// public function nextUntil()

	// public function prevUntil()

	/**
	 * Wrap all the elements in the Crawler instance with a specified wrapping element.
	 *
	 * @throws InvalidArgumentException If the wrapping element is invalid.
	 *
	 * @param string|Crawler|DOMNode $wrapping_element The wrapping element to use. Can be a string, a Crawler instance, or a DOMNode.
	 * @return string
	 */
	public function wrap( string|Crawler|DOMNode $wrapping_element ): static {
		$wrapping_element = $this->resolve_wrapping_element( $wrapping_element );

		if ( is_null( $wrapping_element ) ) {
			throw new InvalidArgumentException( 'Invalid wrapping element provided.' );
		}

		// Bail out if there are no nodes to wrap.
		if ( 0 === $this->count() ) {
			return $this;
		}

		foreach ( $this as $node ) {
			// if ( ! $node instanceof DOMElement || ! $node->parentNode instanceof DOMElement ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$new_node = static::import_new_node( $wrapping_element, $node );

			$node->parentNode->insertBefore( $new_node, $node );
			$new_node->appendChild( $node );
		}

		return $this;
	}

	/**
	 * Wrap all elements that match the Crawler instance with a single wrapping
	 * element.
	 *
	 * Example:
	 *
	 * Before wrapping:
	 *
	 * ```php
	 * <div>
	 *   <h3>Title</h3>
	 *   <p>Content</p>
	 *   <p>More content</p>
	 *   <p>Even more content</p>
	 * </div>
	 * ```
	 *
	 * After wrapping 'p' elements with `<div class="wrapper">`:
	 *
	 * ```php
	 * <div>
	 * 	<h3>Title</h3>
	 *  <div class="wrapper">
	 * 		<p>Content</p>
	 * 		<p>More content</p>
	 * 		<p>Even more content</p>
	 * 	</div>
	 * </div>
	 * ```
	 *
	 * @throws InvalidArgumentException If the wrapping element is invalid.
	 * @param string|Crawler|DOMNode $wrapping_element
	 * @return static
	 */
	public function wrap_all( string|Crawler|DOMNode $wrapping_element ): static {
		$wrapping_element = $this->resolve_wrapping_element( $wrapping_element );

		if ( is_null( $wrapping_element ) ) {
			throw new InvalidArgumentException( 'Invalid wrapping element provided.' );
		}


		// Bail out if there are no nodes to wrap.
		if ( 0 === $this->count() ) {
			return $this;
		}

		$parent = $this->getNode( 0 )?->parentNode;

		if ( $parent instanceof DOMDocument ) {
			throw new InvalidArgumentException( 'Cannot wrap nodes that are direct children of a DOMDocument' );
		}

		foreach ( $this as $node ) {
			if ( $node->parentNode !== $parent ) {
				throw new InvalidArgumentException( 'Nodes to be wrapped with wrap_all() must all have the same parent' );
			}
		}

		// Create a new wrapping element and insert it before the first node.
		$new_node = static::import_new_node( $wrapping_element, $this->getNode( 0 ) );
		$parent->insertBefore( $new_node, $this->getNode( 0 ) );

		foreach ( $this as $node ) {
			$new_node->appendChild( $node );
		}

		if ( ! $parent->hasChildNodes() ) {
			$parent->parentNode->removeChild( $parent );
		}

		return $this;
	}

	/**
	 * Wrap all the inner content of the elements in the Crawler instance with a
	 * specified wrapping element.
	 *
	 * @param string|Crawler|DOMNode $wrapping_element The wrapping element to use.
	 * @return static
	 */
	public function wrap_inner( string|Crawler|DOMNode $wrapping_element ): static {
		$wrapping_element = $this->resolve_wrapping_element( $wrapping_element );

		if ( is_null( $wrapping_element ) ) {
			throw new InvalidArgumentException( 'Invalid wrapping element provided.' );
		}

		foreach ( $this as $node ) {
		}

		return $this;
	}

	/**
	 * Empty the content of all elements in the Crawler instance.
	 *
	 * This method sets the nodeValue of each element to an empty string,
	 * effectively removing all child nodes and text content.
	 *
	 * @return static
	 */
	public function empty(): static {
		foreach ( $this as $node ) {
			$node->nodeValue = '';
		}

		return $this;
	}

	/**
	 * Convert the Crawler instance to an HTML string.
	 */
	public function to_html(): string {
		return $this->outerHtml();
	}

	/**
	 * Import a new node into the existing document and replace the existing node.
	 *
	 * @param DOMNode $new_node The new node to import.
	 * @param DOMNode $existing_node The existing node to replace.
	 */
	protected static function import_new_node( DOMNode $new_node, DOMNode $existing_node ): DOMNode {
		if ( $new_node->ownerDocument !== $existing_node->ownerDocument ) {
			$existing_node->ownerDocument->preserveWhiteSpace = false;

			$new_node = $existing_node->ownerDocument->importNode( $new_node, true );
		}

		return $new_node;
	}

	protected function resolve_wrapping_element( string|Crawler|DOMNode $wrapping_element ): ?DOMNode {
		if ( is_string( $wrapping_element ) ) {
			return ( new static( $wrapping_element ) )->getNode( 0 );
		}

		if ( $wrapping_element instanceof Crawler ) {
			return $wrapping_element->getNode( 0 );
		}

		if ( $wrapping_element instanceof DOMNode ) {
			return $wrapping_element;
		}

		throw new InvalidArgumentException( 'Invalid wrapping element provided.' );
	}
}
