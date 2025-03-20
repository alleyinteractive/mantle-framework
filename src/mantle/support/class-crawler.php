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
	public function getBySelector( string $selector ): static {
		return $this->filter( $selector );
	}

	/**
	 * Query the document for a single element matching a CSS selector.
	 *
	 * @param string $selector
	 */
	public function firstById( string $id ): static {
		return $this->filter( "#{$id}" )->first();
	}

	/**
	 * Query the document for a single element using a tag name.
	 *
	 * @param string $tag The tag name to match.
	 */
	public function firstByTag( string $tag ): static {
		return $this->filter( $tag )->first();
	}

	/**
	 * Query the document for a single element using a CSS selector.
	 *
	 * @param string $selector The CSS selector to match.
	 */
	public function firstBySelector( string $selector ): static {
		return $this->filter( $selector )->first();
	}

	/**
	 * Retrieve all elements matching an XPath expression.
	 *
	 * @param string $xpath XPath expression to match.
	 */
	public function getByXPath( string $xpath ): static {
		return $this->filterXPath( $xpath );
	}

	/**
	 * Query the document for a single element using an XPath expression.
	 *
	 * @param string $xpath
	 */
	public function firstByXPath( string $xpath ): static {
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

		return $this->modifyXPath( $converter->toXPath( $selector ), $callback );
	}

	/**
	 * Modify the elements matching a given XPath expression using a callback function.
	 *
	 * @param string   $xpath XPath expression to match.
	 * @param callable $callback A callback function that receives the matched element and its index.
	 * @phpstan-param callable(Crawler $crawler, int $i): (DOMNode|string|null) $callback
	 */
	public function modifyXPath( string $path, callable $callback ): static {
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

	// public function remove()

	/**
	 * Set an attribute for all elements in the Crawler instance.
	 *
	 * @param string $name  The name of the attribute to set.
	 * @param string $value The value to set for the attribute.
	 */
	public function setAttribute( string $name, string $value ): static {
		foreach ( $this as $node ) {
			if ( $node instanceof DOMElement ) {
				$node->setAttribute( $name, $value );
			}
		}

		return $this;
	}

	/**
	 * Remove an attribute from all elements in the Crawler instance.
	 *
	 * @param string $name
	 */
	public function removeAttribute( string $name ): static {
		foreach ( $this as $node ) {
			if ( $node instanceof DOMElement ) {
				$node->removeAttribute( $name );
			}
		}

		return $this;
	}

	/**
	 * Add a class to all elements in the Crawler instance.
	 *
	 * @param string ...$class
	 */
	public function addClass( string ...$class ): static {
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
	public function removeClass( string ...$class ): static {
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
	public function hasClass( string ...$class ): bool {
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
	public function hasAnyClass( string ...$class ): bool {
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

	// public function prepend()

	// public function append()

	// public function wrap()

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
}
