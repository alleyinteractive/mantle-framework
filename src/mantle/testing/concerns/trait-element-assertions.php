<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * Element_Assertions trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;
use DOMDocument;
use DOMXPath;
use Gt\CssXPath\Translator;

/**
 * Assorted Test_Cast assertions for checking for elements in a response.
 */
trait Element_Assertions {
	/**
	 * DOM Document Storage.
	 *
	 * @var DOMDocument
	 */
	protected DOMDocument $document;

	/**
	 * Retrieve the DOM Document for the response.
	 */
	protected function get_dom_document(): DOMDocument {
		if ( isset( $this->document ) ) {
			return $this->document;
		}

		libxml_use_internal_errors( true );

		$this->document = new DOMDocument();
		$this->document->loadHTML( $this->get_content() );

		return $this->document;
	}

	/**
	 * Assert that an element exists in the response.
	 *
	 * @param string $expression The XPath expression to execute.
	 */
	public function assertElementExists( string $expression ): static {
		$nodes = ( new DOMXPath( $this->get_dom_document() ) )->query( $expression );

		PHPUnit::assertTrue( ! $nodes ? false : $nodes->length > 0 );

		return $this;
	}

	/**
	 * Assert that an element exists by its ID.
	 *
	 * @param string $id The ID of the element to check.
	 */
	public function assertElementExistsById( string $id ): static {
		if ( 0 === strpos( $id, '#' ) ) {
			$id = substr( $id, 1 );
		}

		return $this->assertElementExists( sprintf( '//*[@id="%s"]', $id ) );
	}

	/**
	 * Assert that an element exists by its class name.
	 *
	 * @param string $classname The classname of the element to check.
	 */
	public function assertElementExistsByClassName( string $classname ): static {
		if ( 0 === strpos( $classname, '.' ) ) {
			$classname = substr( $classname, 1 );
		}

		return $this->assertElementExists( sprintf( '//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $classname ) );
	}

	/**
	 * Assert that an element is missing in the response.
	 *
	 * @param string $expression The XPath expression to execute.
	 */
	public function assertElementMissing( string $expression ): static {
		$nodes = ( new DOMXPath( $this->get_dom_document() ) )->query( $expression );

		PHPUnit::assertTrue( false === $nodes || 0 === $nodes->length );

		return $this;
	}

	/**
	 * Assert that an element is missing by its ID.
	 *
	 * @param string $id The ID of the element to check.
	 */
	public function assertElementMissingById( string $id ): static {
		if ( 0 === strpos( $id, '#' ) ) {
			$id = substr( $id, 1 );
		}

		return $this->assertElementMissing( sprintf( '//*[@id="%s"]', $id ) );
	}

	/**
	 * Assert that an element is missing by its class name.
	 *
	 * @param string $classname The classname of the element to check.
	 */
	public function assertElementMissingByClassName( string $classname ): static {
		if ( 0 === strpos( $classname, '.' ) ) {
			$classname = substr( $classname, 1 );
		}

		return $this->assertElementMissing( sprintf( '//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $classname ) );
	}

	/**
	 * Assert that an element exists by tag name.
	 *
	 * @param string $type The type of element to check.
	 */
	public function assertElementExistsByTagName( string $type ): static {
		return $this->assertElementExists( sprintf( '//*[local-name()="%s"]', $type ) );
	}

	/**
	 * Assert that an element is missing by tag name.
	 *
	 * @param string $type The type of element to check.
	 */
	public function assertElementMissingByTagName( string $type ): static {
		return $this->assertElementMissing( sprintf( '//*[local-name()="%s"]', $type ) );
	}

	/**
	 * Assert that an element exists by query selector.
	 *
	 * @param string $selector The selector to use.
	 */
	public function assertElementExistsByQuerySelector( string $selector ): static {
		return $this->assertElementExists( new Translator( $selector ) );
	}

	/**
	 * Alias for assertElementExistsByQuerySelector.
	 *
	 * @param string $selector
	 */
	public function assertQuerySelectorExists( string $selector ): static {
		return $this->assertElementExistsByQuerySelector( $selector );
	}

	/**
	 * Assert that an element is missing by query selector.
	 *
	 * @param string $selector The selector to use.
	 */
	public function assertElementMissingByQuerySelector( string $selector ): static {
		return $this->assertElementMissing( new Translator( $selector ) );
	}

	/**
	 * Alias for assertElementMissingByQuerySelector.
	 *
	 * @param string $selector
	 */
	public function assertQuerySelectorMissing( string $selector ): static {
		return $this->assertElementMissingByQuerySelector( $selector );
	}
}
