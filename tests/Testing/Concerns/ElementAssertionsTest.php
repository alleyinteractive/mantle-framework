<?php
namespace Mantle\Tests\Testing\Concerns;

use DOMNode;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Test_Response;

use function Mantle\Testing\html_string;

/**
 * @group testing
 */
class ElementAssertionsTest extends Framework_Test_Case {
	public string $test_content = '
	<div>
		<section>Example Section</section>
		<div class="test-class">Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
		<ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul>
	</div>';

	protected function response(): Test_Response {
		return new Test_Response( $this->test_content );
	}

	public function test_element_exists_by_xpath() {
		$this->response()
			->assertElementExists( '//div' )
			->assertElementExists( '//section' )
			->assertElementMissing( '//article' );
	}

	public function test_element_exists_by_id() {
		$this->response()
			->assertElementExistsById( 'test-id' )
			->assertElementExistsById( '#test-id' )
			->assertElementMissingById( 'missing-id' )
			->assertElementMissingById( '#missing-id' )
			->assertElementMissingById( '.test-it' ); // A class selector should not match an ID.
	}

	public function test_element_exists_by_class() {
		$this->response()
			->assertElementExistsByClassName( 'test-class' )
			->assertElementExistsByClassName( '.test-class' )
			->assertElementMissingByClassName( 'missing-class' )
			->assertElementMissingByClassName( '.missing-class' );
	}

	public function test_element_exists_by_tag() {
		$this->response()
			->assertElementExistsByTagName( 'div' )
			->assertElementExistsByTagName( 'section' )
			->assertElementMissingByTagName( 'article' );
	}

	public function test_element_exists_by_query_selector() {
		$this->response()
			->assertElementExistsByQuerySelector( 'div' )
			->assertElementExistsByQuerySelector( 'section' )
			->assertElementExistsByQuerySelector( '.test-class' )
			->assertElementExistsByQuerySelector( '#test-id' )
			->assertElementMissingByQuerySelector( 'article' )
			->assertElementMissingByQuerySelector( 'aside' );
	}

	public function test_element_count() {
		$this->response()
			->assertElementCount( '//li', 3 )
			->assertElementCount( '//div', 3 )
			->assertQuerySelectorCount( 'li', 3 )
			->assertQuerySelectorCount( 'div li', 3 )
			->assertQuerySelectorCount( 'div', 3 );
	}

	public function test_by_test_id() {
		$this->response()
			->assertElementExistsByTestId( 'test-item' )
			->assertElementMissingByTestId( 'missing-item' );
	}

	public function test_element_callback() {
		$this->response()
			->assertElement( '//section', fn ( DOMNode $node ) => $node->textContent === 'Example Section' )
			->assertQuerySelector( 'section', fn ( DOMNode $node ) => $node->textContent === 'Example Section' )
			->assertElement( '//li', fn ( DOMNode $node ) => $node->textContent === 'Item 2', pass_any: true )
			->assertQuerySelector( 'li', fn ( DOMNode $node ) => $node->textContent === 'Item 2', pass_any: true );
	}

	public function test_html_string() {
		html_string( $this->test_content )
			->assertContains( 'Example Section' )
			->assertElementExists( '//div' )
			->assertElementExists( '//section' )
			->assertElementMissing( '//article' );
	}
}
