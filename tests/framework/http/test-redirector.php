<?php
/**
 * Test_Redirector test file.
 *
 * @package Mantle
 */

namespace Mantle\Tests\Framework\Console;

// use Mantle\Framework\Contracts\Http\Routing\Url_Generator;
use Mantle\Framework\Http\Request;
use Mantle\Framework\Http\Routing\Redirector;
use Mantle\Framework\Http\Routing\Response_Factory;
use Mantle\Framework\Http\Routing\Url_Generator;
use Mockery as m;
use Symfony\Component\HttpFoundation\HeaderBag;

class Test_Redirector extends \Mockery\Adapter\Phpunit\MockeryTestCase {

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Url_Generator
	 */
	protected $url;

	/**
	 * @var Redirector
	 */
	protected $redirect;

	public function setUp(): void {
		parent::setUp();

		$this->headers = m::mock( HeaderBag::class );

		$this->request = m::mock( Request::class );
		$this->request->shouldReceive( 'isMethod' )->andReturn( true )->byDefault();
		$this->request->shouldReceive( 'method' )->andReturn( 'GET' )->byDefault();
		$this->request->shouldReceive( 'route' )->andReturn( true )->byDefault();
		$this->request->shouldReceive( 'ajax' )->andReturn( false )->byDefault();
		$this->request->shouldReceive( 'expectsJson' )->andReturn( false )->byDefault();
		$this->request->headers = $this->headers;

		$this->url = m::mock( Url_Generator::class );
		$this->url->shouldReceive( 'getRequest' )->andReturn( $this->request );
		$this->url->shouldReceive( 'to' )->with( 'bar', [], null )->andReturn( 'http://foo.com/bar' );
		$this->url->shouldReceive( 'to' )->with( 'bar', [], true )->andReturn( 'https://foo.com/bar' );
		$this->url->shouldReceive( 'to' )->with( 'login', [], null )->andReturn( 'http://foo.com/login' );
		$this->url->shouldReceive( 'to' )->with( 'http://foo.com/bar', [], null )->andReturn( 'http://foo.com/bar' );
		$this->url->shouldReceive( 'to' )->with( '/', [], null )->andReturn( 'http://foo.com/' );
		$this->url->shouldReceive( 'to' )->with( 'http://foo.com/bar?signature=secret', [], null )->andReturn( 'http://foo.com/bar?signature=secret' );

		$this->redirect = new Redirector( $this->url );
	}

	public function testBasicRedirectTo() {
		 $response = $this->redirect->to( 'bar' );

		$this->assertInstanceOf( RedirectResponse::class, $response );
		$this->assertSame( 'http://foo.com/bar', $response->getTargetUrl() );
		$this->assertEquals( 302, $response->getStatusCode() );
		$this->assertEquals( $this->session, $response->getSession() );
	}

	public function testComplexRedirectTo() {
		$response = $this->redirect->to(
			'bar',
			303,
			[
				'X-RateLimit-Limit'     => 60,
				'X-RateLimit-Remaining' => 59,
			],
			true
		);

		$this->assertSame( 'https://foo.com/bar', $response->getTargetUrl() );
		$this->assertEquals( 303, $response->getStatusCode() );
		$this->assertEquals( 60, $response->headers->get( 'X-RateLimit-Limit' ) );
		$this->assertEquals( 59, $response->headers->get( 'X-RateLimit-Remaining' ) );
	}

}
