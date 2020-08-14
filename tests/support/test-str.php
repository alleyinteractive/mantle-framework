<?php

namespace Mantle\Tests\Support;

use Mantle\Framework\Support\Str;
use PHPUnit\Framework\TestCase;

class SupportStrTest extends TestCase {
	public function testStringCanBeLimitedByWords() {
		$this->assertSame( 'Taylor...', Str::words( 'Taylor Otwell', 1 ) );
		$this->assertSame( 'Taylor___', Str::words( 'Taylor Otwell', 1, '___' ) );
		$this->assertSame( 'Taylor Otwell', Str::words( 'Taylor Otwell', 3 ) );
	}

	public function testStringTrimmedOnlyWhereNecessary() {
		$this->assertSame( ' Taylor Otwell ', Str::words( ' Taylor Otwell ', 3 ) );
		$this->assertSame( ' Taylor...', Str::words( ' Taylor Otwell ', 1 ) );
	}

	public function testStringTitle() {
		$this->assertSame( 'Jefferson Costella', Str::title( 'jefferson costella' ) );
		$this->assertSame( 'Jefferson Costella', Str::title( 'jefFErson coSTella' ) );
	}

	public function testStringWithoutWordsDoesntProduceError() {
		$nbsp = chr( 0xC2 ) . chr( 0xA0 );
		$this->assertSame( ' ', Str::words( ' ' ) );
		$this->assertEquals( $nbsp, Str::words( $nbsp ) );
	}

	public function testStringAscii() {
		$this->assertSame( '@', Str::ascii( '@' ) );
		$this->assertSame( 'u', Str::ascii( 'ü' ) );
	}

	public function testStringAsciiWithSpecificLocale() {
		$this->assertSame( 'h H sht Sht a A ia yo', Str::ascii( 'х Х щ Щ ъ Ъ иа йо', 'bg' ) );
		$this->assertSame( 'ae oe ue Ae Oe Ue', Str::ascii( 'ä ö ü Ä Ö Ü', 'de' ) );
	}

	public function testStartsWith() {
		$this->assertTrue( Str::starts_with( 'jason', 'jas' ) );
		$this->assertTrue( Str::starts_with( 'jason', 'jason' ) );
		$this->assertTrue( Str::starts_with( 'jason', [ 'jas' ] ) );
		$this->assertTrue( Str::starts_with( 'jason', [ 'day', 'jas' ] ) );
		$this->assertFalse( Str::starts_with( 'jason', 'day' ) );
		$this->assertFalse( Str::starts_with( 'jason', [ 'day' ] ) );
		$this->assertFalse( Str::starts_with( 'jason', null ) );
		$this->assertFalse( Str::starts_with( 'jason', [ null ] ) );
		$this->assertFalse( Str::starts_with( '0123', [ null ] ) );
		$this->assertTrue( Str::starts_with( '0123', 0 ) );
		$this->assertFalse( Str::starts_with( 'jason', 'J' ) );
		$this->assertFalse( Str::starts_with( 'jason', '' ) );
		$this->assertFalse( Str::starts_with( '7', ' 7' ) );
		$this->assertTrue( Str::starts_with( '7a', '7' ) );
		$this->assertTrue( Str::starts_with( '7a', 7 ) );
		$this->assertTrue( Str::starts_with( '7.12a', 7.12 ) );
		$this->assertFalse( Str::starts_with( '7.12a', 7.13 ) );
		$this->assertTrue( Str::starts_with( 7.123, '7' ) );
		$this->assertTrue( Str::starts_with( 7.123, '7.12' ) );
		$this->assertFalse( Str::starts_with( 7.123, '7.13' ) );
		// Test for multibyte string support
		$this->assertTrue( Str::starts_with( 'Jönköping', 'Jö' ) );
		$this->assertTrue( Str::starts_with( 'Malmö', 'Malmö' ) );
		$this->assertFalse( Str::starts_with( 'Jönköping', 'Jonko' ) );
		$this->assertFalse( Str::starts_with( 'Malmö', 'Malmo' ) );
		$this->assertTrue( Str::starts_with( '你好', '你' ) );
		$this->assertFalse( Str::starts_with( '你好', '好' ) );
		$this->assertFalse( Str::starts_with( '你好', 'a' ) );
	}

	public function testEndsWith() {
		$this->assertTrue( Str::ends_with( 'jason', 'on' ) );
		$this->assertTrue( Str::ends_with( 'jason', 'jason' ) );
		$this->assertTrue( Str::ends_with( 'jason', [ 'on' ] ) );
		$this->assertTrue( Str::ends_with( 'jason', [ 'no', 'on' ] ) );
		$this->assertFalse( Str::ends_with( 'jason', 'no' ) );
		$this->assertFalse( Str::ends_with( 'jason', [ 'no' ] ) );
		$this->assertFalse( Str::ends_with( 'jason', '' ) );
		$this->assertFalse( Str::ends_with( 'jason', [ null ] ) );
		$this->assertFalse( Str::ends_with( 'jason', null ) );
		$this->assertFalse( Str::ends_with( 'jason', 'N' ) );
		$this->assertFalse( Str::ends_with( '7', ' 7' ) );
		$this->assertTrue( Str::ends_with( 'a7', '7' ) );
		$this->assertTrue( Str::ends_with( 'a7', 7 ) );
		$this->assertTrue( Str::ends_with( 'a7.12', 7.12 ) );
		$this->assertFalse( Str::ends_with( 'a7.12', 7.13 ) );
		$this->assertTrue( Str::ends_with( 0.27, '7' ) );
		$this->assertTrue( Str::ends_with( 0.27, '0.27' ) );
		$this->assertFalse( Str::ends_with( 0.27, '8' ) );
		// Test for multibyte string support
		$this->assertTrue( Str::ends_with( 'Jönköping', 'öping' ) );
		$this->assertTrue( Str::ends_with( 'Malmö', 'mö' ) );
		$this->assertFalse( Str::ends_with( 'Jönköping', 'oping' ) );
		$this->assertFalse( Str::ends_with( 'Malmö', 'mo' ) );
		$this->assertTrue( Str::ends_with( '你好', '好' ) );
		$this->assertFalse( Str::ends_with( '你好', '你' ) );
		$this->assertFalse( Str::ends_with( '你好', 'a' ) );
	}

	public function testStrBefore() {
		$this->assertSame( 'han', Str::before( 'hannah', 'nah' ) );
		$this->assertSame( 'ha', Str::before( 'hannah', 'n' ) );
		$this->assertSame( 'ééé ', Str::before( 'ééé hannah', 'han' ) );
		$this->assertSame( 'hannah', Str::before( 'hannah', 'xxxx' ) );
		$this->assertSame( 'hannah', Str::before( 'hannah', '' ) );
		$this->assertSame( 'han', Str::before( 'han0nah', '0' ) );
		$this->assertSame( 'han', Str::before( 'han0nah', 0 ) );
		$this->assertSame( 'han', Str::before( 'han2nah', 2 ) );
	}

	public function testStrBeforeLast() {
		$this->assertSame( 'yve', Str::before_last( 'yvette', 'tte' ) );
		$this->assertSame( 'yvet', Str::before_last( 'yvette', 't' ) );
		$this->assertSame( 'ééé ', Str::before_last( 'ééé yvette', 'yve' ) );
		$this->assertSame( '', Str::before_last( 'yvette', 'yve' ) );
		$this->assertSame( 'yvette', Str::before_last( 'yvette', 'xxxx' ) );
		$this->assertSame( 'yvette', Str::before_last( 'yvette', '' ) );
		$this->assertSame( 'yv0et', Str::before_last( 'yv0et0te', '0' ) );
		$this->assertSame( 'yv0et', Str::before_last( 'yv0et0te', 0 ) );
		$this->assertSame( 'yv2et', Str::before_last( 'yv2et2te', 2 ) );
	}

	public function testStrBetween() {
		$this->assertSame( 'abc', Str::between( 'abc', '', 'c' ) );
		$this->assertSame( 'abc', Str::between( 'abc', 'a', '' ) );
		$this->assertSame( 'abc', Str::between( 'abc', '', '' ) );
		$this->assertSame( 'b', Str::between( 'abc', 'a', 'c' ) );
		$this->assertSame( 'b', Str::between( 'dddabc', 'a', 'c' ) );
		$this->assertSame( 'b', Str::between( 'abcddd', 'a', 'c' ) );
		$this->assertSame( 'b', Str::between( 'dddabcddd', 'a', 'c' ) );
		$this->assertSame( 'nn', Str::between( 'hannah', 'ha', 'ah' ) );
		$this->assertSame( 'a]ab[b', Str::between( '[a]ab[b]', '[', ']' ) );
		$this->assertSame( 'foo', Str::between( 'foofoobar', 'foo', 'bar' ) );
		$this->assertSame( 'bar', Str::between( 'foobarbar', 'foo', 'bar' ) );
	}

	public function testStrAfter() {
		$this->assertSame( 'nah', Str::after( 'hannah', 'han' ) );
		$this->assertSame( 'nah', Str::after( 'hannah', 'n' ) );
		$this->assertSame( 'nah', Str::after( 'ééé hannah', 'han' ) );
		$this->assertSame( 'hannah', Str::after( 'hannah', 'xxxx' ) );
		$this->assertSame( 'hannah', Str::after( 'hannah', '' ) );
		$this->assertSame( 'nah', Str::after( 'han0nah', '0' ) );
		$this->assertSame( 'nah', Str::after( 'han0nah', 0 ) );
		$this->assertSame( 'nah', Str::after( 'han2nah', 2 ) );
	}

	public function testStrAfterLast() {
		$this->assertSame( 'tte', Str::after_last( 'yvette', 'yve' ) );
		$this->assertSame( 'e', Str::after_last( 'yvette', 't' ) );
		$this->assertSame( 'e', Str::after_last( 'ééé yvette', 't' ) );
		$this->assertSame( '', Str::after_last( 'yvette', 'tte' ) );
		$this->assertSame( 'yvette', Str::after_last( 'yvette', 'xxxx' ) );
		$this->assertSame( 'yvette', Str::after_last( 'yvette', '' ) );
		$this->assertSame( 'te', Str::after_last( 'yv0et0te', '0' ) );
		$this->assertSame( 'te', Str::after_last( 'yv0et0te', 0 ) );
		$this->assertSame( 'te', Str::after_last( 'yv2et2te', 2 ) );
		$this->assertSame( 'foo', Str::after_last( '----foo', '---' ) );
	}

	public function testStrContains() {
		$this->assertTrue( Str::contains( 'taylor', 'ylo' ) );
		$this->assertTrue( Str::contains( 'taylor', 'taylor' ) );
		$this->assertTrue( Str::contains( 'taylor', [ 'ylo' ] ) );
		$this->assertTrue( Str::contains( 'taylor', [ 'xxx', 'ylo' ] ) );
		$this->assertFalse( Str::contains( 'taylor', 'xxx' ) );
		$this->assertFalse( Str::contains( 'taylor', [ 'xxx' ] ) );
		$this->assertFalse( Str::contains( 'taylor', '' ) );
	}

	public function testStrContainsAll() {
		$this->assertTrue( Str::contains_all( 'taylor otwell', [
			'taylor',
			'otwell',
		] ) );
		$this->assertTrue( Str::contains_all( 'taylor otwell', [ 'taylor' ] ) );
		$this->assertFalse( Str::contains_all( 'taylor otwell', [
			'taylor',
			'xxx',
		] ) );
	}

	public function testParseCallback() {
		$this->assertEquals( [
			'Class',
			'method',
		], Str::parse_callback( 'Class@method', 'foo' ) );
		$this->assertEquals( [
			'Class',
			'foo',
		], Str::parse_callback( 'Class', 'foo' ) );
		$this->assertEquals( [ 'Class', null ], Str::parse_callback( 'Class' ) );
	}

	public function testSlug() {
		$this->assertSame( 'hello-world', Str::slug( 'hello world' ) );
		$this->assertSame( 'hello-world', Str::slug( 'hello-world' ) );
		$this->assertSame( 'hello_world', Str::slug( 'hello_world' ) );
		$this->assertSame( '', Str::slug( '' ) );
	}

	public function testStrStart() {
		$this->assertSame( '/test/string', Str::start( 'test/string', '/' ) );
		$this->assertSame( '/test/string', Str::start( '/test/string', '/' ) );
		$this->assertSame( '/test/string', Str::start( '//test/string', '/' ) );
	}

	public function testFinish() {
		$this->assertSame( 'abbc', Str::finish( 'ab', 'bc' ) );
		$this->assertSame( 'abbc', Str::finish( 'abbcbc', 'bc' ) );
		$this->assertSame( 'abcbbc', Str::finish( 'abcbbcbc', 'bc' ) );
	}

	public function testIs() {
		$this->assertTrue( Str::is( '/', '/' ) );
		$this->assertFalse( Str::is( '/', ' /' ) );
		$this->assertFalse( Str::is( '/', '/a' ) );
		$this->assertTrue( Str::is( 'foo/*', 'foo/bar/baz' ) );

		$this->assertTrue( Str::is( '*@*', 'App\Class@method' ) );
		$this->assertTrue( Str::is( '*@*', 'app\Class@' ) );
		$this->assertTrue( Str::is( '*@*', '@method' ) );

		// is case sensitive
		$this->assertFalse( Str::is( '*BAZ*', 'foo/bar/baz' ) );
		$this->assertFalse( Str::is( '*FOO*', 'foo/bar/baz' ) );
		$this->assertFalse( Str::is( 'A', 'a' ) );

		// Accepts array of patterns
		$this->assertTrue( Str::is( [ 'a*', 'b*' ], 'a/' ) );
		$this->assertTrue( Str::is( [ 'a*', 'b*' ], 'b/' ) );
		$this->assertFalse( Str::is( [ 'a*', 'b*' ], 'f/' ) );

		// numeric values and patterns
		$this->assertFalse( Str::is( [ 'a*', 'b*' ], 123 ) );
		$this->assertTrue( Str::is( [ '*2*', 'b*' ], 11211 ) );

		$this->assertTrue( Str::is( '*/foo', 'blah/baz/foo' ) );

		$valueObject = new StringableObjectStub( 'foo/bar/baz' );
		$patternObject = new StringableObjectStub( 'foo/*' );

		$this->assertTrue( Str::is( 'foo/bar/baz', $valueObject ) );
		$this->assertTrue( Str::is( $patternObject, $valueObject ) );

		// empty patterns
		$this->assertFalse( Str::is( [], 'test' ) );
	}

	public function testKebab() {
		$this->assertSame( 'laravel-php-framework', Str::kebab( 'LaravelPhpFramework' ) );
	}

	public function testLower() {
		$this->assertSame( 'foo bar baz', Str::lower( 'FOO BAR BAZ' ) );
		$this->assertSame( 'foo bar baz', Str::lower( 'fOo Bar bAz' ) );
	}

	public function testUpper() {
		$this->assertSame( 'FOO BAR BAZ', Str::upper( 'foo bar baz' ) );
		$this->assertSame( 'FOO BAR BAZ', Str::upper( 'foO bAr BaZ' ) );
	}

	public function testLimit() {
		$this->assertSame( 'Laravel is...', Str::limit( 'Laravel is a free, open source PHP web application framework.', 10 ) );
		$this->assertSame( '这是一...', Str::limit( '这是一段中文', 6 ) );

		$string = 'The PHP framework for web artisans.';
		$this->assertSame( 'The PHP...', Str::limit( $string, 7 ) );
		$this->assertSame( 'The PHP', Str::limit( $string, 7, '' ) );
		$this->assertSame( 'The PHP framework for web artisans.', Str::limit( $string, 100 ) );

		$nonAsciiString = '这是一段中文';
		$this->assertSame( '这是一...', Str::limit( $nonAsciiString, 6 ) );
		$this->assertSame( '这是一', Str::limit( $nonAsciiString, 6, '' ) );
	}

	public function testLength() {
		$this->assertEquals( 11, Str::length( 'foo bar baz' ) );
		$this->assertEquals( 11, Str::length( 'foo bar baz', 'UTF-8' ) );
	}

	public function testRandom() {
		$this->assertEquals( 16, strlen( Str::random() ) );
		$randomInteger = random_int( 1, 100 );
		$this->assertEquals( $randomInteger, strlen( Str::random( $randomInteger ) ) );
		// $this->assertIsString( Str::random() );
	}

	public function testReplaceArray() {
		$this->assertSame( 'foo/bar/baz', Str::replace_array( '?', [
			'foo',
			'bar',
			'baz',
		], '?/?/?' ) );
		$this->assertSame( 'foo/bar/baz/?', Str::replace_array( '?', [
			'foo',
			'bar',
			'baz',
		], '?/?/?/?' ) );
		$this->assertSame( 'foo/bar', Str::replace_array( '?', [
			'foo',
			'bar',
			'baz',
		], '?/?' ) );
		$this->assertSame( '?/?/?', Str::replace_array( 'x', [
			'foo',
			'bar',
			'baz',
		], '?/?/?' ) );
		// Ensure recursive replacements are avoided
		$this->assertSame( 'foo?/bar/baz', Str::replace_array( '?', [
			'foo?',
			'bar',
			'baz',
		], '?/?/?' ) );
		// Test for associative array support
		$this->assertSame( 'foo/bar', Str::replace_array( '?', [
			1 => 'foo',
			2 => 'bar',
		], '?/?' ) );
		$this->assertSame( 'foo/bar', Str::replace_array( '?', [
			'x' => 'foo',
			'y' => 'bar',
		], '?/?' ) );
	}

	public function testReplaceFirst() {
		$this->assertSame( 'fooqux foobar', Str::replace_first( 'bar', 'qux', 'foobar foobar' ) );
		$this->assertSame( 'foo/qux? foo/bar?', Str::replace_first( 'bar?', 'qux?', 'foo/bar? foo/bar?' ) );
		$this->assertSame( 'foo foobar', Str::replace_first( 'bar', '', 'foobar foobar' ) );
		$this->assertSame( 'foobar foobar', Str::replace_first( 'xxx', 'yyy', 'foobar foobar' ) );
		$this->assertSame( 'foobar foobar', Str::replace_first( '', 'yyy', 'foobar foobar' ) );
		// Test for multibyte string support
		$this->assertSame( 'Jxxxnköping Malmö', Str::replace_first( 'ö', 'xxx', 'Jönköping Malmö' ) );
		$this->assertSame( 'Jönköping Malmö', Str::replace_first( '', 'yyy', 'Jönköping Malmö' ) );
	}

	public function testReplaceLast() {
		$this->assertSame( 'foobar fooqux', Str::replace_last( 'bar', 'qux', 'foobar foobar' ) );
		$this->assertSame( 'foo/bar? foo/qux?', Str::replace_last( 'bar?', 'qux?', 'foo/bar? foo/bar?' ) );
		$this->assertSame( 'foobar foo', Str::replace_last( 'bar', '', 'foobar foobar' ) );
		$this->assertSame( 'foobar foobar', Str::replace_last( 'xxx', 'yyy', 'foobar foobar' ) );
		$this->assertSame( 'foobar foobar', Str::replace_last( '', 'yyy', 'foobar foobar' ) );
		// Test for multibyte string support
		$this->assertSame( 'Malmö Jönkxxxping', Str::replace_last( 'ö', 'xxx', 'Malmö Jönköping' ) );
		$this->assertSame( 'Malmö Jönköping', Str::replace_last( '', 'yyy', 'Malmö Jönköping' ) );
	}

	public function testSnake() {
		$this->assertSame( 'laravel_p_h_p_framework', Str::snake( 'LaravelPHPFramework' ) );
		$this->assertSame( 'laravel_php_framework', Str::snake( 'LaravelPhpFramework' ) );
		$this->assertSame( 'laravel php framework', Str::snake( 'LaravelPhpFramework', ' ' ) );
		$this->assertSame( 'laravel_php_framework', Str::snake( 'Laravel Php Framework' ) );
		$this->assertSame( 'laravel_php_framework', Str::snake( 'Laravel    Php      Framework   ' ) );
		// ensure cache keys don't overlap
		$this->assertSame( 'laravel__php__framework', Str::snake( 'LaravelPhpFramework', '__' ) );
		$this->assertSame( 'laravel_php_framework_', Str::snake( 'LaravelPhpFramework_', '_' ) );
		$this->assertSame( 'laravel_php_framework', Str::snake( 'laravel php Framework' ) );
		$this->assertSame( 'laravel_php_frame_work', Str::snake( 'laravel php FrameWork' ) );
		// prevent breaking changes
		$this->assertSame( 'foo-bar', Str::snake( 'foo-bar' ) );
		$this->assertSame( 'foo-_bar', Str::snake( 'Foo-Bar' ) );
		$this->assertSame( 'foo__bar', Str::snake( 'Foo_Bar' ) );
		$this->assertSame( 'żółtałódka', Str::snake( 'ŻółtaŁódka' ) );
	}

	public function testStudly() {
		$this->assertSame( 'LaravelPHPFramework', Str::studly( 'laravel_p_h_p_framework' ) );
		$this->assertSame( 'LaravelPhpFramework', Str::studly( 'laravel_php_framework' ) );
		$this->assertSame( 'LaravelPhPFramework', Str::studly( 'laravel-phP-framework' ) );
		$this->assertSame( 'LaravelPhpFramework', Str::studly( 'laravel  -_-  php   -_-   framework   ' ) );

		$this->assertSame( 'FooBar', Str::studly( 'fooBar' ) );
		$this->assertSame( 'FooBar', Str::studly( 'foo_bar' ) );
		$this->assertSame( 'FooBar', Str::studly( 'foo_bar' ) ); // test cache
		$this->assertSame( 'FooBarBaz', Str::studly( 'foo-barBaz' ) );
		$this->assertSame( 'FooBarBaz', Str::studly( 'foo-bar_baz' ) );
	}

	public function testCamel() {
		$this->assertSame( 'laravelPHPFramework', Str::camel( 'Laravel_p_h_p_framework' ) );
		$this->assertSame( 'laravelPhpFramework', Str::camel( 'Laravel_php_framework' ) );
		$this->assertSame( 'laravelPhPFramework', Str::camel( 'Laravel-phP-framework' ) );
		$this->assertSame( 'laravelPhpFramework', Str::camel( 'Laravel  -_-  php   -_-   framework   ' ) );

		$this->assertSame( 'fooBar', Str::camel( 'FooBar' ) );
		$this->assertSame( 'fooBar', Str::camel( 'foo_bar' ) );
		$this->assertSame( 'fooBar', Str::camel( 'foo_bar' ) ); // test cache
		$this->assertSame( 'fooBarBaz', Str::camel( 'Foo-barBaz' ) );
		$this->assertSame( 'fooBarBaz', Str::camel( 'foo-bar_baz' ) );
	}

	public function testSubstr() {
		$this->assertSame( 'Ё', Str::substr( 'БГДЖИЛЁ', - 1 ) );
		$this->assertSame( 'ЛЁ', Str::substr( 'БГДЖИЛЁ', - 2 ) );
		$this->assertSame( 'И', Str::substr( 'БГДЖИЛЁ', - 3, 1 ) );
		$this->assertSame( 'ДЖИЛ', Str::substr( 'БГДЖИЛЁ', 2, - 1 ) );
		$this->assertEmpty( Str::substr( 'БГДЖИЛЁ', 4, - 4 ) );
		$this->assertSame( 'ИЛ', Str::substr( 'БГДЖИЛЁ', - 3, - 1 ) );
		$this->assertSame( 'ГДЖИЛЁ', Str::substr( 'БГДЖИЛЁ', 1 ) );
		$this->assertSame( 'ГДЖ', Str::substr( 'БГДЖИЛЁ', 1, 3 ) );
		$this->assertSame( 'БГДЖ', Str::substr( 'БГДЖИЛЁ', 0, 4 ) );
		$this->assertSame( 'Ё', Str::substr( 'БГДЖИЛЁ', - 1, 1 ) );
		$this->assertEmpty( Str::substr( 'Б', 2 ) );
	}

	public function testSubstrCount() {
		$this->assertSame( 3, Str::substr_count( 'laravelPHPFramework', 'a' ) );
		$this->assertSame( 0, Str::substr_count( 'laravelPHPFramework', 'z' ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'l', 2 ) );
		$this->assertSame( 0, Str::substr_count( 'laravelPHPFramework', 'z', 2 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'k', - 1 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'k', - 1 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'a', 1, 2 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'a', 1, 2 ) );
		$this->assertSame( 3, Str::substr_count( 'laravelPHPFramework', 'a', 1, - 2 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'a', - 10, - 3 ) );
	}

	public function testUcfirst() {
		$this->assertSame( 'Laravel', Str::ucfirst( 'laravel' ) );
		$this->assertSame( 'Laravel framework', Str::ucfirst( 'laravel framework' ) );
		$this->assertSame( 'Мама', Str::ucfirst( 'мама' ) );
		$this->assertSame( 'Мама мыла раму', Str::ucfirst( 'мама мыла раму' ) );
	}

	public function testAsciiNull() {
		$this->assertSame( '', Str::ascii( null ) );
		$this->assertTrue( Str::is_ascii( null ) );
		$this->assertSame( '', Str::slug( null ) );
	}

	public function testLineNumber() {
		$contents = 'Vestibulum aliquet consequat neque, eget lobortis urna porta volutpat.
Donec dapibus ac ligula eget sodales. Aliquam sed efficitur arcu, ut imperdiet sapien.
In vitae euismod dui, ut rhoncus purus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae;
Maecenas a euismod ex. Maecenas placerat turpis eu suscipit tristique. Ut euismod mi eget tellus euismod fermentum.
item_to_match
Integer nec metus pellentesque, blandit libero id, porttitor urna. Quisque feugiat maximus elit in tristique.
     Sed et porttitor sapien. Curabitur. another_item_to_match Duis at placerat mauris. Cras.
Suspendisse eget auctor est. Maecenas.';

		$expected = [
			'item_to_match' => 5,
			'another_item_to_match' => 7,
		];

		foreach ( $expected as $search => $line_num ) {
			preg_match_all( '/' . $search . '/', $contents, $matches, PREG_OFFSET_CAPTURE );

			[ $match, $char_pos ] = $matches[0][0] ?? [ 0, 0 ];
			$this->assertNotEmpty( $char_pos );

			$this->assertEquals( $line_num, Str::line_number( $contents, $char_pos ) );
		}
	}
}

class StringableObjectStub {
	private $value;

	public function __construct( $value ) {
		$this->value = $value;
	}

	public function __toString() {
		return $this->value;
	}
}
