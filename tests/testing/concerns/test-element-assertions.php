<?php
namespace Mantle\Tests\Testing\Concerns;

use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Test_Response;

class Test_Element_Assertions extends Framework_Test_Case {
	public string $test_content = '
	<div>
		<section>Example Section</section>
		<div class="test-class">Example Div By Class</div>
		<div id="test-id">Example Div By ID</div>
	</div>';

	public function test_element_exists_by_xpath() {
		$response = new Test_Response( $this->test_content );

		$response->assertElementExists( '//div' );
		$response->assertElementExists( '//section' );
		$response->assertElementMissing( '//article' );
	}

	public function test_element_exists_by_id() {
		$response = new Test_Response( $this->test_content );

		$response->assertElementExistsById( '#test-id' );
		$response->assertElementExistsById( '#test-id' );
		$response->assertElementMissingById( 'missing-id' );
		$response->assertElementMissingById( '.missing-id' );
	}

	public function test_element_exists_by_class() {
		$response = new Test_Response( $this->test_content );

		$response->assertElementExistsByClassName( 'test-class' );
		$response->assertElementExistsByClassName( '.test-class' );
		$response->assertElementMissingByClassName( 'missing-class' );
		$response->assertElementMissingByClassName( '.missing-class' );
	}

	public function test_element_exists_by_tag() {
		$response = new Test_Response( $this->test_content );

		$response->assertElementExistsByTagName( 'div' );
		$response->assertElementExistsByTagName( 'section' );
		$response->assertElementMissingByTagName( 'article' );
	}
}
