<?php

namespace Mantle\Tests\Support;

use DOMElement;
use DOMNode;
use Mantle\Support\HTML;
use Mantle\Testing\Concerns\Assertions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase {
	use Assertions;

	public const TEST_CONTENT = '
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

	public function test_is_html_document(): void {
		$this->assertFalse( ( new HTML( self::TEST_CONTENT ) )->is_html_document() );
		$this->assertTrue( ( new HTML( '<html></html>' ) )->is_html_document() );
		$this->assertTrue( ( new HTML( '<html>' . self::TEST_CONTENT . '</html>' ) )->is_html_document() );
	}

	public function test_it_can_make_a_document_from_a_string(): void {
		$html = "<!DOCTYPE html>
<html>
<head>
<title>Test</title>
</head>
<body>
<p>Hello, World!</p>
</body>
</html>
";
		$crawler = new HTML( $html );

		$this->assertStringsEqualsWithoutWhitespace( $html, $crawler->to_html() );
	}

	public function test_it_can_convert_html_back_to_the_original_html(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$this->assertTrimmedStringEquals( self::TEST_CONTENT, $crawler->to_html() );
	}

	public function test_it_can_match_an_element_by_id(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$query = $crawler->first_by_id( 'test-id' );

		$this->assertInstanceOf( HTML::class, $query );
		$this->assertEquals( 'Example Div By ID', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-id', $query->attr( 'id' ) );
	}

	public function test_it_can_match_an_element_by_query_selector(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$query = $crawler->first_by_selector( '.test-class' );

		$this->assertInstanceOf( HTML::class, $query );
		$this->assertEquals( 'Example Div By Class', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-class', $query->attr( 'class' ) );
	}

	public function test_it_can_match_an_element_by_xpath(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$query = $crawler->first_by_xpath( '//div[@id="test-id"]' );

		$this->assertInstanceOf( HTML::class, $query );
		$this->assertEquals( 'Example Div By ID', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-id', $query->attr( 'id' ) );
	}

	public function test_it_can_modify_an_element_in_the_document(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filter( '.test-class' )->modify(
			fn ( HTML $element ) => $element->set_data( 'modified', 'true' ),
		);

		$this->assertTrimmedStringEquals(
			'
	<div>
		<section>Example Section</section>
		<div class="test-class" data-modified="true">Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
		<ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul>
	</div>
',
			$crawler->to_html(),
		);
	}

	public function test_it_can_add_a_class_to_an_element_in_the_document(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filter( '.test-class' )->modify(
			fn ( HTML $element ) => $element->add_class( 'class1', 'class2' ),
		);

		$this->assertTrimmedStringEquals(
			'
	<div>
		<section>Example Section</section>
		<div class="test-class class1 class2">Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
		<ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul>
	</div>
',
			$crawler->to_html(),
		);
	}

	public function test_it_can_remove_a_class_from_an_element_in_the_document(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filter( '.test-class' )->modify(
			fn ( HTML $element ) => $element->remove_class( 'test-class' ),
		);

		$this->assertTrimmedStringEquals(
			'
	<div>
		<section>Example Section</section>
		<div>Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
		<ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul>
	</div>
',
			$crawler->to_html(),
		);
	}

	public function test_it_can_modify_by_xpath(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filterXPath( '//div[@id="test-id"]' )->modify(
			fn ( HTML $element ) => $element->set_attribute( 'data-modified', 'true' ),
		);

		$this->assertTrimmedStringEquals(
			'
	<div>
		<section>Example Section</section>
		<div class="test-class">Example Div By Class</div>
		<div id="test-id" data-modified="true">Example Div By ID</div>
		<ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul>
	</div>
',
			$crawler->to_html(),
		);
	}

	public function test_it_can_see_if_an_element_has_a_class(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$query = $crawler->first_by_selector( '.test-class' );

		$this->assertTrue( $query->has_class( 'test-class' ) );
		$this->assertTrue( $query->has_any_class( 'test-class', 'another' ) );
		$this->assertFalse( $query->has_class( 'non-existent-class' ) );
		$this->assertFalse( $query->has_class( 'test-class', 'another' ) );
	}

	public function test_it_can_replace_an_element_in_the_document(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filter( '.test-class' )->modify(
			function ( HTML $element ): DOMElement {
				// Create a new DOMElement to replace the existing one.
				$crawler = new HTML( '<span class="replaced-class">Replaced Element</span>' );

				return $crawler->getNode( 0 );
			}
		);

		$this->assertTrimmedStringEquals(
			'
	<div>
		<section>Example Section</section>
		<span class="replaced-class">Replaced Element</span>
		<div id="test-id">Example Div By ID</div>
		<ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul>
	</div>
',
			$crawler->to_html(),
		);
	}

	public function test_it_can_replace_an_element_in_the_document_with_a_string(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filter( '.test-class' )->modify(
			fn () => '<span class="replaced-class">Replaced Element</span>',
		);

		$this->assertTrimmedStringEquals(
			'
	<div>
		<section>Example Section</section>
		<span class="replaced-class">Replaced Element</span>
		<div id="test-id">Example Div By ID</div>
		<ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul>
	</div>
',
			$crawler->to_html(),
		);
	}

	public function test_it_can_remove_an_element_from_the_document(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->remove( '.test-class' );

		$this->assertStringsEqualsWithoutWhitespace(
			'
	<div>
		<section>Example Section</section>

		<div id="test-id">Example Div By ID</div>
		<ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul>
	</div>',
			$crawler->to_html(),
		);
	}

	/**
	 * @dataProvider wrap_data_provider
	 */
	#[DataProvider( 'wrap_data_provider' )]
	public function test_it_can_wrap_elements( string|HTML|DOMNode $wrapping_element ): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filter( 'ul' )->wrap( $wrapping_element );

		$this->assertTrimmedStringEquals( '
	<div>
		<section>Example Section</section>
		<div class="test-class">Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
		<div class="ul-wrapper"><ul>
			<li>Item 1</li>
			<li>Item 2</li>
			<li data-testid="test-item">Item 3</li>
		</ul></div>
	</div>',
			$crawler->to_html(),
		);
	}

	public static function wrap_data_provider(): array {
		$html = '<div class="ul-wrapper"></div>';

		return [
			'string' => [ $html ],
			'crawler' => [ new HTML( $html ) ],
			'domnode' => [ ( new HTML( $html ) )->getNode( 0 ) ],
		];
	}

	public function test_it_can_wrap_multiple_elements(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filter( 'li' )->wrap( '<span class="li-wrapper"></span>' );

		$this->assertTrimmedStringEquals( '
	<div>
		<section>Example Section</section>
		<div class="test-class">Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
		<ul>
			<span class="li-wrapper"><li>Item 1</li></span>
			<span class="li-wrapper"><li>Item 2</li></span>
			<span class="li-wrapper"><li data-testid="test-item">Item 3</li></span>
		</ul>
	</div>',
			$crawler->to_html(),
		);
	}

	public function test_it_can_wrap_all_elements(): void {
		$crawler = new HTML( '
	<div>
		<h3>Example</h3>
		<p>Test</p>
		<p>Test 2</p>
		<p>Test 3</p>
	</div>' );

		$crawler->filter( 'p' )->wrap_all( '<div class="p-wrapper"></div>' );

		$this->assertStringsEqualsWithoutWhitespace(
			'
	<div>
		<h3>Example</h3>
		<div class="p-wrapper">
			<p>Test</p>
			<p>Test 2</p>
			<p>Test 3</p>
		</div>
	</div>',
			$crawler->to_html(),
		);
	}

	/**
	 * @dataProvider inner_wrap_data_provider
	 */
	#[DataProvider( 'inner_wrap_data_provider' )]
	public function test_it_can_inner_wrap_elements( string|HTML|DOMNode $wrapping_element ): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->filter( 'li' )->wrap_inner( $wrapping_element );

		$this->assertStringsEqualsWithoutWhitespace( '
	<div>
		<section>Example Section</section>
		<div class="test-class">Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
		<ul>
			<li><span class="li-wrapper">Item 1</span></li>
			<li><span class="li-wrapper">Item 2</span></li>
			<li data-testid="test-item"><span class="li-wrapper">Item 3</span></li>
		</ul>
	</div>',
			$crawler->to_html(),
		);
	}

	public static function inner_wrap_data_provider(): array {
		$html = '<span class="li-wrapper"></span>';

		return [
			'string' => [ $html ],
			'crawler' => [ new HTML( $html ) ],
			'domnode' => [ ( new HTML( $html ) )->getNode( 0 ) ],
		];
	}

	public function test_it_can_empty_an_element(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$crawler->first_by_selector( 'ul' )->empty();

		$this->assertTrimmedStringEquals( '
	<div>
		<section>Example Section</section>
		<div class="test-class">Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
		<ul></ul>
	</div>',
			$crawler->to_html(),
		);
	}

	public function test_it_can_traverse_elements_using_next_until(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$elements = $crawler->filter( 'li' )->next_until(
			fn ( HTML $element ) => $element->text() === 'Item 2',
		);

		$this->assertCount( 1, $elements );
		$this->assertEquals( 'Item 3', $elements->text() );

		// Include the first element.
		$crawler = new HTML( self::TEST_CONTENT );

		$elements = $crawler->filter( 'li' )->next_until(
			fn ( HTML $element ) => $element->text() === 'Item 2',
			include: true,
		);

		$this->assertCount( 2, $elements );
		$this->assertEquals( 'Item 2', $elements->first()->text() );
		$this->assertEquals( 'Item 3', $elements->last()->text() );
	}

	public function test_it_can_traverse_elements_using_prev_until(): void {
		$crawler = new HTML( self::TEST_CONTENT );

		$elements = $crawler->filter( 'li' )->previous_until(
			fn ( HTML $element ) => $element->text() === 'Item 2',
		);

		$this->assertCount( 1, $elements );
		$this->assertEquals( 'Item 1', $elements->text() );

		// Include the first element.
		$crawler = new HTML( self::TEST_CONTENT );

		$elements = $crawler->filter( 'li' )->previous_until(
			fn ( HTML $element ) => $element->text() === 'Item 2',
			include: true,
		);

		$this->assertCount( 2, $elements );
		$this->assertEquals( 'Item 1', $elements->first()->text() );
		$this->assertEquals( 'Item 2', $elements->last()->text() );
	}

	/**
	 * @dataProvider append_dataprovider
	 */
	#[DataProvider( 'append_dataprovider' )]
	public function test_it_can_append_elements( string $base, string|HTML|DOMNode $element, string $expected ): void {
		$crawler = new HTML( $base );
		$crawler->filter( 'p' )->append( $element );

		$this->assertStringsEqualsWithoutWhitespace( $expected, $crawler->to_html() );
	}

	public static function append_dataprovider(): array {
		$base = '
		<div>
			<p>Paragraph 1</p>
			<p>Paragraph 2</p>
			<p>Paragraph 3</p>
		</div>';

		return [
			'element string' => [
				$base,
				'<span>Appended Text</span>',
				'<div>
					<p>Paragraph 1<span>Appended Text</span></p>
					<p>Paragraph 2<span>Appended Text</span></p>
					<p>Paragraph 3<span>Appended Text</span></p>
				</div>'
			],
			'text string' => [
				$base,
				' Appended Text',
				'<div>
					<p>Paragraph 1 Appended Text</p>
					<p>Paragraph 2 Appended Text</p>
					<p>Paragraph 3 Appended Text</p>
				</div>',
			],
			'text string with br' => [
				$base,
				'<br>Appended Text',
				'<div>
					<p>Paragraph 1<br>Appended Text</p>
					<p>Paragraph 2<br>Appended Text</p>
					<p>Paragraph 3<br>Appended Text</p>
				</div>',
			],
		];
	}

	/**
	 * @dataProvider prepend_dataprovider
	 */
	#[DataProvider( 'prepend_dataprovider' )]
	public function test_it_can_prepend_elements( string $base, string|HTML|DOMNode $element, string $expected ): void {
		$crawler = new HTML( $base );
		$crawler->filter( 'p' )->prepend( $element );

		$this->assertStringsEqualsWithoutWhitespace( $expected, $crawler->to_html() );
	}

	public static function prepend_dataprovider(): array {
		$base = '
		<div>
			<p>Paragraph 1</p>
			<p>Paragraph 2</p>
			<p>Paragraph 3</p>
		</div>';

		return [
			'element string' => [
				$base,
				'<span>prepended Text</span>',
				'<div>
					<p><span>prepended Text</span>Paragraph 1</p>
					<p><span>prepended Text</span>Paragraph 2</p>
					<p><span>prepended Text</span>Paragraph 3</p>
				</div>'
			],
			'text string' => [
				$base,
				'prepended Text ',
				'<div>
					<p>prepended Text Paragraph 1</p>
					<p>prepended Text Paragraph 2</p>
					<p>prepended Text Paragraph 3</p>
				</div>',
			],
			'text string with br' => [
				$base,
				'prepended Text<br>',
				'<div>
					<p>prepended Text<br>Paragraph 1</p>
					<p>prepended Text<br>Paragraph 2</p>
					<p>prepended Text<br>Paragraph 3</p>
				</div>',
			],
		];
	}

	public function test_it_can_insert_before(): void {
		$crawler = new HTML( '<div id="test"><p>Test</p></div>' );

		$crawler->filter( 'div p' )->before( '<h1>Inserted Before</h1>' );

		$this->assertEquals(
			'<div id="test"><h1>Inserted Before</h1><p>Test</p></div>',
			$crawler->to_html(),
		);
	}

	public function test_it_can_insert_after(): void {
		$crawler = new HTML( '<div id="test"><p>Test</p></div>' );

		$crawler->filter( 'div p' )->after( '<h1>Inserted After</h1>' );

		$this->assertEquals(
			'<div id="test"><p>Test</p><h1>Inserted After</h1></div>',
			$crawler->to_html(),
		);
	}

	public function test_element_assertions(): void {
		( new HTML( self::TEST_CONTENT ) )
			->assertQuerySelectorExists( '.test-class' )
			->assertElementExistsByTestId( 'test-item' )
			->assertQuerySelectorMissing( '.non-existent-class' );
	}
}
