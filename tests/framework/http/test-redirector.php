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
use Symfony\Component\HttpFoundation\RedirectResponse;

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

		$root = home_url();

		$this->url = m::mock( Url_Generator::class );
		$this->url->shouldReceive( 'get_request' )->andReturn( $this->request );
		$this->url->shouldReceive( 'to' )->with( 'bar', array(), null )->andReturn( 'http://foo.com/bar' );
		$this->url->shouldReceive( 'to' )->with( 'bar', array(), true )->andReturn( 'https://foo.com/bar' );
		$this->url->shouldReceive( 'to' )->with( 'login', array(), null )->andReturn( 'http://foo.com/login' );
		$this->url->shouldReceive( 'to' )->with( 'http://foo.com/bar', array(), null )->andReturn( 'http://foo.com/bar' );
		// $this->url->shouldReceive( 'to' )->with( '/', array(), null )->andReturn( 'http://foo.com/' );
		$this->url->shouldReceive( 'to' )->with( '/', array(), null )->andReturn( home_url() );
		$this->url->shouldReceive( 'to' )->with( 'http://foo.com/bar?signature=secret', array(), null )->andReturn( 'http://foo.com/bar?signature=secret' );

		$this->redirect = new Redirector( $this->url );
	}

	public function testBasicRedirectTo() {
		 $response = $this->redirect->to( 'bar' );

		$this->assertInstanceOf( RedirectResponse::class, $response );
		$this->assertSame( 'http://foo.com/bar', $response->getTargetUrl() );
		$this->assertEquals( 302, $response->getStatusCode() );
	}

	public function testComplexRedirectTo() {
		$response = $this->redirect->to(
			'bar',
			303,
			array(
				'X-RateLimit-Limit'     => 60,
				'X-RateLimit-Remaining' => 59,
			),
			true
		);

		$this->assertSame( 'https://foo.com/bar', $response->getTargetUrl() );
		$this->assertEquals( 303, $response->getStatusCode() );
		$this->assertEquals( 60, $response->headers->get( 'X-RateLimit-Limit' ) );
		$this->assertEquals( 59, $response->headers->get( 'X-RateLimit-Remaining' ) );
	}

	public function testRefreshRedirectToCurrentUrl() {
		$this->request->shouldReceive( 'path' )->andReturn( 'http://foo.com/bar' );
		$response = $this->redirect->refresh();
		$this->assertSame( 'http://foo.com/bar', $response->getTargetUrl() );
	}

	public function testBackRedirectToHttpReferer() {
		$this->headers->shouldReceive( 'has' )->with( 'referer' )->andReturn( true );
		$this->url->shouldReceive( 'previous' )->andReturn( 'http://foo.com/bar' );
		$response = $this->redirect->back();
		$this->assertSame( 'http://foo.com/bar', $response->getTargetUrl() );
	}

	public function testAwayDoesntValidateTheUrl() {
		$response = $this->redirect->away( 'bar' );
		$this->assertSame( 'bar', $response->getTargetUrl() );
	}

	public function testSecureRedirectToHttpsUrl() {
		$response = $this->redirect->secure( 'bar' );
		$this->assertSame( 'https://foo.com/bar', $response->getTargetUrl() );
	}

	public function test_home_redirect() {
		$response = $this->redirect->home();
		$this->assertSame( home_url(), $response->getTargetUrl() );
	}

	// public function testAction()
	// {
	// $this->url->shouldReceive('action')->with('bar@index', [])->andReturn('http://foo.com/bar');
	// $response = $this->redirect->action('bar@index');
	// $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
		// }

	// public function testRoute() {
	// 	$this->url->shouldReceive( 'route' )->with( 'home' )->andReturn( 'http://foo.com/bar' );
	// 	$this->url->shouldReceive( 'route' )->with( 'home', array() )->andReturn( 'http://foo.com/bar' );

	// 	$response = $this->redirect->route( 'home' );
	// 	$this->assertSame( 'http://foo.com/bar', $response->getTargetUrl() );

	// 	$response = $this->redirect->home();
	// 	$this->assertSame( 'http://foo.com/bar', $response->getTargetUrl() );
	// }
}
