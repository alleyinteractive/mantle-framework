<?php

namespace Mantle\Tests\Support\HTML;

use DOMElement;
use DOMNode;
use Mantle\Support\Crawler;
use Mantle\Testing\Concerns\Assertions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Mantle\Support\Helpers\stringable;

class CrawlerTest extends TestCase {
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

	public function test_it_can_make_a_document_from_a_string(): void {
		$html = "<html>
<head>
<title>Test</title>
</head>
<body>
<p>Hello, World!</p>
</body>
</html>";
		$crawler = new Crawler( $html );

		$this->assertInstanceOf( Crawler::class, $crawler );
		$this->assertEquals( $html, $crawler->to_html() );
	}

	public function test_it_can_convert_html_back_to_the_original_html(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$this->assertTrimmedStringEquals( self::TEST_CONTENT, $crawler->to_html() );
	}

	public function test_it_can_match_an_element_by_id(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$query = $crawler->first_by_id( 'test-id' );

		$this->assertInstanceOf( Crawler::class, $query );
		$this->assertEquals( 'Example Div By ID', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-id', $query->attr( 'id' ) );
	}

	public function test_it_can_match_an_element_by_query_selector(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$query = $crawler->first_by_selector( '.test-class' );

		$this->assertInstanceOf( Crawler::class, $query );
		$this->assertEquals( 'Example Div By Class', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-class', $query->attr( 'class' ) );
	}

	public function test_it_can_match_an_element_by_xpath(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$query = $crawler->first_by_xpath( '//div[@id="test-id"]' );

		$this->assertInstanceOf( Crawler::class, $query );
		$this->assertEquals( 'Example Div By ID', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-id', $query->attr( 'id' ) );
	}

	public function test_it_can_modify_an_element_in_the_document(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$crawler->modify(
			'.test-class',
			fn ( Crawler $element ) => $element->set_attribute( 'data-modified', 'true' ),
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
		$crawler = new Crawler( self::TEST_CONTENT );

		$crawler->modify(
			'.test-class',
			fn ( Crawler $element ) => $element->add_class( 'class1', 'class2' ),
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
		$crawler = new Crawler( self::TEST_CONTENT );

		$crawler->modify(
			'.test-class',
			fn ( Crawler $element ) => $element->remove_class( 'test-class' ),
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
		$crawler = new Crawler( self::TEST_CONTENT );

		$crawler->modify_xpath(
			'//div[@id="test-id"]',
			fn ( Crawler $element ) => $element->set_attribute( 'data-modified', 'true' ),
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
		$crawler = new Crawler( self::TEST_CONTENT );

		$query = $crawler->first_by_selector( '.test-class' );

		$this->assertTrue( $query->has_class( 'test-class' ) );
		$this->assertTrue( $query->has_any_class( 'test-class', 'another' ) );
		$this->assertFalse( $query->has_class( 'non-existent-class' ) );
		$this->assertFalse( $query->has_class( 'test-class', 'another' ) );
	}

	public function test_it_can_replace_an_element_in_the_document(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$crawler->modify(
			'.test-class',
			function ( Crawler $element ): DOMElement {
				// Create a new DOMElement to replace the existing one.
				$crawler = new Crawler( '<span class="replaced-class">Replaced Element</span>' );

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
		$crawler = new Crawler( self::TEST_CONTENT );

		$crawler->modify(
			'.test-class',
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
		$crawler = new Crawler( self::TEST_CONTENT );

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

	// public function test_it_can_modify_links(): void

	/**
	 * @dataProvider wrap_data_provider
	 */
	#[DataProvider( 'wrap_data_provider' )]
	public function test_it_can_wrap_elements( string|Crawler|DOMNode $wrapping_element ): void {
		$crawler = new Crawler( self::TEST_CONTENT );

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
			'crawler' => [ new Crawler( $html ) ],
			'domnode' => [ ( new Crawler( $html ) )->getNode( 0 ) ],
		];
	}

	public function test_it_can_wrap_multiple_elements(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

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
		$crawler = new Crawler( '
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
	// public function test_it_can_inner_wrap_elements( string|Crawler|DOMNode $wrapping_element ): void {
	// 	$crawler = new Crawler( self::TEST_CONTENT );

	// 	$crawler->filter( 'li' )->wrap_inner( $wrapping_element );
	// 	dd($crawler->to_html());

	// 	$this->assertTrimmedStringEquals( '
	// <div>
	// 	<section>Example Section</section>
	// 	<div class="test-class">Example Div By Class</div>
	// 	<div id="test-id">Example Div By ID</div>
	// 	<ul>
	// 		<li><span class="li-wrapper">Item 1</span></li>
	// 		<li><span class="li-wrapper">Item 2</span></li>
	// 		<li data-testid="test-item"><span class="li-wrapper">Item 3</span></li>
	// 	</ul></div>
	// </div>',
	// 		$crawler->to_html(),
	// 	);
	// }

	public static function inner_wrap_data_provider(): array {
		$html = '<span class="li-wrapper"></span>';

		return [
			'string' => [ $html ],
			'crawler' => [ new Crawler( $html ) ],
			'domnode' => [ ( new Crawler( $html ) )->getNode( 0 ) ],
		];
	}

	public function test_it_can_empty_an_element(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

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
}
