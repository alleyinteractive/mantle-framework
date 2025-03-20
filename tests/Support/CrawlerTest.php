<?php

namespace Mantle\Tests\Support\HTML;

use DOMElement;
use Mantle\Support\Crawler;
use Mantle\Testing\Concerns\Assertions;
use PHPUnit\Framework\TestCase;

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

		$query = $crawler->firstById( 'test-id' );

		$this->assertInstanceOf( Crawler::class, $query );
		$this->assertEquals( 'Example Div By ID', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-id', $query->attr( 'id' ) );
	}

	public function test_it_can_match_an_element_by_query_selector(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$query = $crawler->firstBySelector( '.test-class' );

		$this->assertInstanceOf( Crawler::class, $query );
		$this->assertEquals( 'Example Div By Class', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-class', $query->attr( 'class' ) );
	}

	public function test_it_can_match_an_element_by_xpath(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$query = $crawler->firstByXPath( '//div[@id="test-id"]' );

		$this->assertInstanceOf( Crawler::class, $query );
		$this->assertEquals( 'Example Div By ID', $query->text() );
		$this->assertEquals( 'div', $query->nodeName() );
		$this->assertEquals( 'test-id', $query->attr( 'id' ) );
	}

	public function test_it_can_modify_an_element_in_the_document(): void {
		$crawler = new Crawler( self::TEST_CONTENT );

		$crawler->modify(
			'.test-class',
			fn ( Crawler $element ) => $element->setAttribute( 'data-modified', 'true' ),
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
			fn ( Crawler $element ) => $element->addClass( 'class1', 'class2' ),
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
			fn ( Crawler $element ) => $element->removeClass( 'test-class' ),
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

		$crawler->modifyXPath(
			'//div[@id="test-id"]',
			fn ( Crawler $element ) => $element->setAttribute( 'data-modified', 'true' ),
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

		$query = $crawler->firstBySelector( '.test-class' );

		$this->assertTrue( $query->hasClass( 'test-class' ) );
		$this->assertTrue( $query->hasAnyClass( 'test-class', 'another' ) );
		$this->assertFalse( $query->hasClass( 'non-existent-class' ) );
		$this->assertFalse( $query->hasClass( 'test-class', 'another' ) );
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

	// public function test_it_can_remove_an_element_from_the_document(): void {}
}
