<?php

namespace Mantle\Tests\Support;

use Exception;
use Mantle\Support\Str;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionClass;

class Test_Str extends TestCase {

	public function testStringCanBeLimitedByWords() {
		$this->assertSame( 'Taylor...', Str::words( 'Taylor Otwell', 1 ) );
		$this->assertSame( 'Taylor___', Str::words( 'Taylor Otwell', 1, '___' ) );
		$this->assertSame( 'Taylor Otwell', Str::words( 'Taylor Otwell', 3 ) );
	}

	public function testStringCanBeLimitedByWordsNonAscii() {
		$this->assertSame( '这是...', Str::words( '这是 段中文', 1 ) );
		$this->assertSame( '这是___', Str::words( '这是 段中文', 1, '___' ) );
		$this->assertSame( '这是-段中文', Str::words( '这是-段中文', 3, '___' ) );
		$this->assertSame( '这是___', Str::words( '这是	 段中文', 1, '___' ) );
	}

	public function testStringTrimmedOnlyWhereNecessary() {
		 $this->assertSame( ' Taylor Otwell ', Str::words( ' Taylor Otwell ', 3 ) );
		$this->assertSame( ' Taylor...', Str::words( ' Taylor Otwell ', 1 ) );
	}

	public function testStringTitle() {
		 $this->assertSame( 'Jefferson Costella', Str::title( 'jefferson costella' ) );
		$this->assertSame( 'Jefferson Costella', Str::title( 'jefFErson coSTella' ) );
	}

	public function testStringHeadline() {
		$this->assertSame( 'Jefferson Costella', Str::headline( 'jefferson costella' ) );
		$this->assertSame( 'Jefferson Costella', Str::headline( 'jefFErson coSTella' ) );
		$this->assertSame( 'Jefferson Costella Uses Laravel', Str::headline( 'jefferson_costella uses-_Laravel' ) );
		$this->assertSame( 'Jefferson Costella Uses Laravel', Str::headline( 'jefferson_costella uses__Laravel' ) );

		$this->assertSame( 'Laravel P H P Framework', Str::headline( 'laravel_p_h_p_framework' ) );
		$this->assertSame( 'Laravel P H P Framework', Str::headline( 'laravel _p _h _p _framework' ) );
		$this->assertSame( 'Laravel Php Framework', Str::headline( 'laravel_php_framework' ) );
		$this->assertSame( 'Laravel Ph P Framework', Str::headline( 'laravel-phP-framework' ) );
		$this->assertSame( 'Laravel Php Framework', Str::headline( 'laravel  -_-  php   -_-   framework   ' ) );

		$this->assertSame( 'Foo Bar', Str::headline( 'fooBar' ) );
		$this->assertSame( 'Foo Bar', Str::headline( 'foo_bar' ) );
		$this->assertSame( 'Foo Bar Baz', Str::headline( 'foo-barBaz' ) );
		$this->assertSame( 'Foo Bar Baz', Str::headline( 'foo-bar_baz' ) );

		$this->assertSame( 'Öffentliche Überraschungen', Str::headline( 'öffentliche-überraschungen' ) );
		$this->assertSame( 'Öffentliche Überraschungen', Str::headline( '-_öffentliche_überraschungen_-' ) );
		$this->assertSame( 'Öffentliche Überraschungen', Str::headline( '-öffentliche überraschungen' ) );

		$this->assertSame( 'Sind Öde Und So', Str::headline( 'sindÖdeUndSo' ) );

		$this->assertSame( 'Orwell 1984', Str::headline( 'orwell 1984' ) );
		$this->assertSame( 'Orwell 1984', Str::headline( 'orwell   1984' ) );
		$this->assertSame( 'Orwell 1984', Str::headline( '-orwell-1984 -' ) );
		$this->assertSame( 'Orwell 1984', Str::headline( ' orwell_- 1984 ' ) );
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
		$this->assertTrue( Str::starts_with( 'jason', collect( [ 'day', 'jas' ] ) ) );
		$this->assertFalse( Str::starts_with( 'jason', 'day' ) );
		$this->assertFalse( Str::starts_with( 'jason', [ 'day' ] ) );
		$this->assertFalse( Str::starts_with( 'jason', null ) );
		$this->assertFalse( Str::starts_with( 'jason', [ null ] ) );
		$this->assertFalse( Str::starts_with( '0123', [ null ] ) );
		$this->assertTrue( Str::starts_with( '0123', 0 ) );
		$this->assertFalse( Str::starts_with( 'jason', 'J' ) );
		$this->assertFalse( Str::starts_with( 'jason', '' ) );
		$this->assertFalse( Str::starts_with( '', '' ) );
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
		$this->assertTrue( Str::ends_with( 'jason', collect( [ 'no', 'on' ] ) ) );
		$this->assertFalse( Str::ends_with( 'jason', 'no' ) );
		$this->assertFalse( Str::ends_with( 'jason', [ 'no' ] ) );
		$this->assertFalse( Str::ends_with( 'jason', '' ) );
		$this->assertFalse( Str::ends_with( '', '' ) );
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

	public function testStrExcerpt() {
		$this->assertSame( '...is a beautiful morn...', Str::excerpt( 'This is a beautiful morning', 'beautiful', [ 'radius' => 5 ] ) );
		$this->assertSame( 'This is a...', Str::excerpt( 'This is a beautiful morning', 'this', [ 'radius' => 5 ] ) );
		$this->assertSame( '...iful morning', Str::excerpt( 'This is a beautiful morning', 'morning', [ 'radius' => 5 ] ) );
		$this->assertNull( Str::excerpt( 'This is a beautiful morning', 'day' ) );
		$this->assertSame( '...is a beautiful! mor...', Str::excerpt( 'This is a beautiful! morning', 'Beautiful', [ 'radius' => 5 ] ) );
		$this->assertSame( '...is a beautiful? mor...', Str::excerpt( 'This is a beautiful? morning', 'beautiful', [ 'radius' => 5 ] ) );
		$this->assertSame( '', Str::excerpt( '', '', [ 'radius' => 0 ] ) );
		$this->assertSame( 'a', Str::excerpt( 'a', 'a', [ 'radius' => 0 ] ) );
		$this->assertSame( '...b...', Str::excerpt( 'abc', 'B', [ 'radius' => 0 ] ) );
		$this->assertSame( 'abc', Str::excerpt( 'abc', 'b', [ 'radius' => 1 ] ) );
		$this->assertSame( 'abc...', Str::excerpt( 'abcd', 'b', [ 'radius' => 1 ] ) );
		$this->assertSame( '...abc', Str::excerpt( 'zabc', 'b', [ 'radius' => 1 ] ) );
		$this->assertSame( '...abc...', Str::excerpt( 'zabcd', 'b', [ 'radius' => 1 ] ) );
		$this->assertSame( 'zabcd', Str::excerpt( 'zabcd', 'b', [ 'radius' => 2 ] ) );
		$this->assertSame( 'zabcd', Str::excerpt( '  zabcd  ', 'b', [ 'radius' => 4 ] ) );
		$this->assertSame( '...abc...', Str::excerpt( 'z  abc  d', 'b', [ 'radius' => 1 ] ) );
		$this->assertSame(
			'[...]is a beautiful morn[...]',
			Str::excerpt(
				'This is a beautiful morning',
				'beautiful',
				[
					'omission' => '[...]',
					'radius'   => 5,
				]
			)
		);
		$this->assertSame(
			'This is the ultimate supercalifragilisticexpialidoceous very looooooooooooooooooong looooooooooooong beautiful morning with amazing sunshine and awesome tempera[...]',
			Str::excerpt(
				'This is the ultimate supercalifragilisticexpialidoceous very looooooooooooooooooong looooooooooooong beautiful morning with amazing sunshine and awesome temperatures. So what are you gonna do about it?',
				'very',
				[ 'omission' => '[...]' ],
			)
		);

		$this->assertSame( '...y...', Str::excerpt( 'taylor', 'y', [ 'radius' => 0 ] ) );
		$this->assertSame( '...ayl...', Str::excerpt( 'taylor', 'Y', [ 'radius' => 1 ] ) );
		$this->assertSame( '<div> The article description </div>', Str::excerpt( '<div> The article description </div>', 'article' ) );
		$this->assertSame( '...The article desc...', Str::excerpt( '<div> The article description </div>', 'article', [ 'radius' => 5 ] ) );
		$this->assertSame( 'The article description', Str::excerpt( strip_tags( '<div> The article description </div>' ), 'article' ) );
		$this->assertSame( '', Str::excerpt( null ) );
		$this->assertSame( '', Str::excerpt( '' ) );
		$this->assertSame( '', Str::excerpt( null, '' ) );
		$this->assertSame( 'T...', Str::excerpt( 'The article description', null, [ 'radius' => 1 ] ) );
		$this->assertSame( 'The arti...', Str::excerpt( 'The article description', '', [ 'radius' => 8 ] ) );
		$this->assertSame( '', Str::excerpt( ' ' ) );
		$this->assertSame( 'The arti...', Str::excerpt( 'The article description', ' ', [ 'radius' => 4 ] ) );
		$this->assertSame( '...cle description', Str::excerpt( 'The article description', 'description', [ 'radius' => 4 ] ) );
		$this->assertSame( 'T...', Str::excerpt( 'The article description', 'T', [ 'radius' => 0 ] ) );
		$this->assertSame(
			'What i?',
			Str::excerpt(
				'What is the article?',
				'What',
				[
					'radius'   => 2,
					'omission' => '?',
				]
			)
		);

		$this->assertSame( '...ö - 二 sān 大åè...', Str::excerpt( 'åèö - 二 sān 大åèö', '二 sān', [ 'radius' => 4 ] ) );
		$this->assertSame( 'åèö - 二...', Str::excerpt( 'åèö - 二 sān 大åèö', 'åèö', [ 'radius' => 4 ] ) );
		$this->assertSame( 'åèö - 二 sān 大åèö', Str::excerpt( 'åèö - 二 sān 大åèö', 'åèö - 二 sān 大åèö', [ 'radius' => 4 ] ) );
		$this->assertSame( 'åèö - 二 sān 大åèö', Str::excerpt( 'åèö - 二 sān 大åèö', 'åèö - 二 sān 大åèö', [ 'radius' => 4 ] ) );
		$this->assertSame( '...༼...', Str::excerpt( '㏗༼㏗', '༼', [ 'radius' => 0 ] ) );
		$this->assertSame( '...༼...', Str::excerpt( '㏗༼㏗', '༼', [ 'radius' => 0 ] ) );
		$this->assertSame( '...ocê e...', Str::excerpt( 'Como você está', 'ê', [ 'radius' => 2 ] ) );
		$this->assertSame( '...ocê e...', Str::excerpt( 'Como você está', 'Ê', [ 'radius' => 2 ] ) );
		$this->assertSame( 'João...', Str::excerpt( 'João Antônio ', 'jo', [ 'radius' => 2 ] ) );
		$this->assertSame( 'João Antô...', Str::excerpt( 'João Antônio', 'JOÃO', [ 'radius' => 5 ] ) );
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

	public function testStrBetweenFirst() {
		 $this->assertSame( 'abc', Str::between_first( 'abc', '', 'c' ) );
		$this->assertSame( 'abc', Str::between_first( 'abc', 'a', '' ) );
		$this->assertSame( 'abc', Str::between_first( 'abc', '', '' ) );
		$this->assertSame( 'b', Str::between_first( 'abc', 'a', 'c' ) );
		$this->assertSame( 'b', Str::between_first( 'dddabc', 'a', 'c' ) );
		$this->assertSame( 'b', Str::between_first( 'abcddd', 'a', 'c' ) );
		$this->assertSame( 'b', Str::between_first( 'dddabcddd', 'a', 'c' ) );
		$this->assertSame( 'nn', Str::between_first( 'hannah', 'ha', 'ah' ) );
		$this->assertSame( 'a', Str::between_first( '[a]ab[b]', '[', ']' ) );
		$this->assertSame( 'foo', Str::between_first( 'foofoobar', 'foo', 'bar' ) );
		$this->assertSame( '', Str::between_first( 'foobarbar', 'foo', 'bar' ) );
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

	/**
	 * @dataProvider strContainsProvider
	 */
	public function testStrContains( $haystack, $needles, $expected, $ignoreCase = false ) {
		$this->assertEquals( $expected, Str::contains( $haystack, $needles, $ignoreCase ) );
	}

	/**
	 * @dataProvider strContainsAllProvider
	 */
	public function testStrContainsAll( $haystack, $needles, $expected, $ignoreCase = false ) {
		$this->assertEquals( $expected, Str::contains_all( $haystack, $needles, $ignoreCase ) );
	}

	public function testParseCallback() {
		$this->assertEquals( [ 'Class', 'method' ], Str::parse_callback( 'Class@method', 'foo' ) );
		$this->assertEquals( [ 'Class', 'foo' ], Str::parse_callback( 'Class', 'foo' ) );
		$this->assertEquals( [ 'Class', null ], Str::parse_callback( 'Class' ) );
	}

	public function testSlug() {
		$this->assertSame( 'hello-world', Str::slug( 'hello world' ) );
		$this->assertSame( 'hello-world', Str::slug( 'hello-world' ) );
		$this->assertSame( 'hello-world', Str::slug( 'hello_world' ) );
		$this->assertSame( 'hello_world', Str::slug( 'hello_world', '_' ) );
		$this->assertSame( 'user-at-host', Str::slug( 'user@host' ) );
		$this->assertSame( 'سلام-دنیا', Str::slug( 'سلام دنیا', '-', null ) );
		$this->assertSame( 'sometext', Str::slug( 'some text', '' ) );
		$this->assertSame( '', Str::slug( '', '' ) );
		$this->assertSame( '', Str::slug( '' ) );
		$this->assertSame( 'bsm-allah', Str::slug( 'بسم الله', '-', 'en', [ 'allh' => 'allah' ] ) );
		$this->assertSame( '500-dollar-bill', Str::slug( '500$ bill', '-', 'en', [ '$' => 'dollar' ] ) );
		$this->assertSame( '500-dollar-bill', Str::slug( '500--$----bill', '-', 'en', [ '$' => 'dollar' ] ) );
		$this->assertSame( '500-dollar-bill', Str::slug( '500-$-bill', '-', 'en', [ '$' => 'dollar' ] ) );
		$this->assertSame( '500-dollar-bill', Str::slug( '500$--bill', '-', 'en', [ '$' => 'dollar' ] ) );
		$this->assertSame( '500-dollar-bill', Str::slug( '500-$--bill', '-', 'en', [ '$' => 'dollar' ] ) );
		$this->assertSame( 'أحمد-في-المدرسة', Str::slug( 'أحمد@المدرسة', '-', null, [ '@' => 'في' ] ) );
	}

	public function testStrStart() {
		$this->assertSame( '/test/string', Str::start( 'test/string', '/' ) );
		$this->assertSame( '/test/string', Str::start( '/test/string', '/' ) );
		$this->assertSame( '/test/string', Str::start( '//test/string', '/' ) );
	}

	public function testFlushCache() {
		$reflection = new ReflectionClass( Str::class );
		$property   = $reflection->getProperty( 'snake_cache' );
		$property->setAccessible( true );

		Str::flush_cache();
		$this->assertEmpty( $property->getValue() );

		Str::snake( 'Taylor Otwell' );
		$this->assertNotEmpty( $property->getValue() );

		Str::flush_cache();
		$this->assertEmpty( $property->getValue() );
	}

	public function testFinish() {
		$this->assertSame( 'abbc', Str::finish( 'ab', 'bc' ) );
		$this->assertSame( 'abbc', Str::finish( 'abbcbc', 'bc' ) );
		$this->assertSame( 'abcbbc', Str::finish( 'abcbbcbc', 'bc' ) );
	}

	public function testWrap() {
		$this->assertEquals( '"value"', Str::wrap( 'value', '"' ) );
		$this->assertEquals( 'foo-bar-baz', Str::wrap( '-bar-', 'foo', 'baz' ) );
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

		$valueObject   = new StringableObjectStub( 'foo/bar/baz' );
		$patternObject = new StringableObjectStub( 'foo/*' );

		$this->assertTrue( Str::is( 'foo/bar/baz', $valueObject ) );
		$this->assertTrue( Str::is( $patternObject, $valueObject ) );

		// empty patterns
		$this->assertFalse( Str::is( [], 'test' ) );

		$this->assertFalse( Str::is( '', 0 ) );
		$this->assertFalse( Str::is( [ null ], 0 ) );
		$this->assertTrue( Str::is( [ null ], null ) );
	}

	/**
	 * @dataProvider validUuidList
	 */
	public function testIsUuidWithValidUuid( $uuid ) {
		$this->assertTrue( Str::is_uuid( $uuid ) );
	}

	/**
	 * @dataProvider invalidUuidList
	 */
	public function testIsUuidWithInvalidUuid( $uuid ) {
		$this->assertFalse( Str::is_uuid( $uuid ) );
	}

	public function testIsJson() {
		$this->assertTrue( Str::is_json( '1' ) );
		$this->assertTrue( Str::is_json( '[1,2,3]' ) );
		$this->assertTrue( Str::is_json( '[1,   2,   3]' ) );
		$this->assertTrue( Str::is_json( '{"first": "John", "last": "Doe"}' ) );
		$this->assertTrue( Str::is_json( '[{"first": "John", "last": "Doe"}, {"first": "Jane", "last": "Doe"}]' ) );

		$this->assertFalse( Str::is_json( '1,' ) );
		$this->assertFalse( Str::is_json( '[1,2,3' ) );
		$this->assertFalse( Str::is_json( '[1,   2   3]' ) );
		$this->assertFalse( Str::is_json( '{first: "John"}' ) );
		$this->assertFalse( Str::is_json( '[{first: "John"}, {first: "Jane"}]' ) );
		$this->assertFalse( Str::is_json( '' ) );
		$this->assertFalse( Str::is_json( null ) );
		$this->assertFalse( Str::is_json( [] ) );
	}

	public function testIsMatch() {
		 $this->assertTrue( Str::is_match( '/.*,.*!/', 'Hello, Laravel!' ) );
		$this->assertTrue( Str::is_match( '/^.*$(.*)/', 'Hello, Laravel!' ) );
		$this->assertTrue( Str::is_match( '/laravel/i', 'Hello, Laravel!' ) );
		$this->assertTrue( Str::is_match( '/^(.*(.*(.*)))/', 'Hello, Laravel!' ) );

		$this->assertFalse( Str::is_match( '/H.o/', 'Hello, Laravel!' ) );
		$this->assertFalse( Str::is_match( '/^laravel!/i', 'Hello, Laravel!' ) );
		$this->assertFalse( Str::is_match( '/laravel!(.*)/', 'Hello, Laravel!' ) );
		$this->assertFalse( Str::is_match( '/^[a-zA-Z,!]+$/', 'Hello, Laravel!' ) );

		$this->assertTrue( Str::is_match( [ '/.*,.*!/', '/H.o/' ], 'Hello, Laravel!' ) );
		$this->assertTrue( Str::is_match( [ '/^laravel!/i', '/^.*$(.*)/' ], 'Hello, Laravel!' ) );
		$this->assertTrue( Str::is_match( [ '/laravel/i', '/laravel!(.*)/' ], 'Hello, Laravel!' ) );
		$this->assertTrue( Str::is_match( [ '/^[a-zA-Z,!]+$/', '/^(.*(.*(.*)))/' ], 'Hello, Laravel!' ) );
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
		$this->assertIsString( Str::random() );
	}

	/** @test */
	public function TestWhetherTheNumberOfGeneratedCharactersIsEquallyDistributed() {
		$results = [];
		// take 6.200.000 samples, because there are 62 different characters
		for ( $i = 0; $i < 620000; $i++ ) {
			$random             = Str::random( 1 );
			$results[ $random ] = ( $results[ $random ] ?? 0 ) + 1;
		}

		// each character should occur 100.000 times with a variance of 5%.
		foreach ( $results as $result ) {
			$this->assertEqualsWithDelta( 10000, $result, 500 );
		}
	}

	public function testRandomStringFactoryCanBeSet() {
		 Str::create_random_strings_using( fn ( $length) => 'length:' . $length );

		$this->assertSame( 'length:7', Str::random( 7 ) );
		$this->assertSame( 'length:7', Str::random( 7 ) );

		Str::create_random_strings_normally();

		$this->assertNotSame( 'length:7', Str::random() );
	}

	public function testItCanSpecifyASequenceOfRandomStringsToUtilise() {
		Str::create_random_strings_using_sequence(
			[
				0 => 'x',
				// 1 => just generate a random one here...
				2 => 'y',
				3 => 'z',
			// ... => continue to generate random strings...
			]
		);

		$this->assertSame( 'x', Str::random() );
		$this->assertSame( 16, mb_strlen( Str::random() ) );
		$this->assertSame( 'y', Str::random() );
		$this->assertSame( 'z', Str::random() );
		$this->assertSame( 16, mb_strlen( Str::random() ) );
		$this->assertSame( 16, mb_strlen( Str::random() ) );

		Str::create_random_strings_normally();
	}

	public function testItCanSpecifyAFallbackForARandomStringSequence() {
		Str::create_random_strings_using_sequence( [ Str::random(), Str::random() ], fn () => throw new Exception( 'Out of random strings.' ) );
		Str::random();
		Str::random();

		try {
			$this->expectExceptionMessage( 'Out of random strings.' );
			Str::random();
			$this->fail();
		} finally {
			Str::create_random_strings_normally();
		}
	}

	public function testReplace() {
		 $this->assertSame( 'foo bar laravel', Str::replace( 'baz', 'laravel', 'foo bar baz' ) );
		$this->assertSame( 'foo bar laravel', Str::replace( 'baz', 'laravel', 'foo bar Baz', false ) );
		$this->assertSame( 'foo bar baz 8.x', Str::replace( '?', '8.x', 'foo bar baz ?' ) );
		$this->assertSame( 'foo bar baz 8.x', Str::replace( 'x', '8.x', 'foo bar baz X', false ) );
		$this->assertSame( 'foo/bar/baz', Str::replace( ' ', '/', 'foo bar baz' ) );
		$this->assertSame( 'foo bar baz', Str::replace( [ '?1', '?2', '?3' ], [ 'foo', 'bar', 'baz' ], '?1 ?2 ?3' ) );
		$this->assertSame( [ 'foo', 'bar', 'baz' ], Str::replace( collect( [ '?1', '?2', '?3' ] ), collect( [ 'foo', 'bar', 'baz' ] ), collect( [ '?1', '?2', '?3' ] ) ) );
	}

	public function testReplaceArray() {
		$this->assertSame( 'foo/bar/baz', Str::replace_array( '?', [ 'foo', 'bar', 'baz' ], '?/?/?' ) );
		$this->assertSame( 'foo/bar/baz/?', Str::replace_array( '?', [ 'foo', 'bar', 'baz' ], '?/?/?/?' ) );
		$this->assertSame( 'foo/bar', Str::replace_array( '?', [ 'foo', 'bar', 'baz' ], '?/?' ) );
		$this->assertSame( '?/?/?', Str::replace_array( 'x', [ 'foo', 'bar', 'baz' ], '?/?/?' ) );
		// Ensure recursive replacements are avoided
		$this->assertSame( 'foo?/bar/baz', Str::replace_array( '?', [ 'foo?', 'bar', 'baz' ], '?/?/?' ) );
		// Test for associative array support
		$this->assertSame(
			'foo/bar',
			Str::replace_array(
				'?',
				[
					1 => 'foo',
					2 => 'bar',
				],
				'?/?'
			)
		);
		$this->assertSame(
			'foo/bar',
			Str::replace_array(
				'?',
				[
					'x' => 'foo',
					'y' => 'bar',
				],
				'?/?'
			)
		);
	}

	public function testReplaceFirst() {
		$this->assertSame( 'fooqux foobar', Str::replace_first( 'bar', 'qux', 'foobar foobar' ) );
		$this->assertSame( 'foo/qux? foo/bar?', Str::replace_first( 'bar?', 'qux?', 'foo/bar? foo/bar?' ) );
		$this->assertSame( 'foo foobar', Str::replace_first( 'bar', '', 'foobar foobar' ) );
		$this->assertSame( 'foobar foobar', Str::replace_first( 'xxx', 'yyy', 'foobar foobar' ) );
		$this->assertSame( 'foobar foobar', Str::replace_first( '', 'yyy', 'foobar foobar' ) );
		$this->assertSame( '1', Str::replace_first( 0, '1', '0' ) );
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

	public function testRemove() {
		$this->assertSame( 'Fbar', Str::remove( 'o', 'Foobar' ) );
		$this->assertSame( 'Foo', Str::remove( 'bar', 'Foobar' ) );
		$this->assertSame( 'oobar', Str::remove( 'F', 'Foobar' ) );
		$this->assertSame( 'Foobar', Str::remove( 'f', 'Foobar' ) );
		$this->assertSame( 'oobar', Str::remove( 'f', 'Foobar', false ) );

		$this->assertSame( 'Fbr', Str::remove( [ 'o', 'a' ], 'Foobar' ) );
		$this->assertSame( 'Fooar', Str::remove( [ 'f', 'b' ], 'Foobar' ) );
		$this->assertSame( 'ooar', Str::remove( [ 'f', 'b' ], 'Foobar', false ) );
		$this->assertSame( 'Foobar', Str::remove( [ 'f', '|' ], 'Foo|bar' ) );
	}

	public function testReverse() {
		 $this->assertSame( 'FooBar', Str::reverse( 'raBooF' ) );
		$this->assertSame( 'Teniszütő', Str::reverse( 'őtüzsineT' ) );
		$this->assertSame( '❤MultiByte☆', Str::reverse( '☆etyBitluM❤' ) );
	}

	public function testSnake() {
		$this->assertSame( 'laravel_p_h_p_framework', Str::snake( 'LaravelPHPFramework' ) );
		$this->assertSame( 'laravel_php_framework', Str::snake( 'LaravelPhpFramework' ) );
		$this->assertSame( 'laravel php framework', Str::snake( 'LaravelPhpFramework', ' ' ) );
		$this->assertSame( 'laravel_php_framework', Str::snake( 'Laravel Php Framework' ) );
		$this->assertSame( 'laravel_php_framework', Str::snake( 'Laravel	Php	  Framework   ' ) );
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

	public function testSquish() {
		$this->assertSame( 'laravel php framework', Str::squish( ' laravel   php  framework ' ) );
		$this->assertSame( 'laravel php framework', Str::squish( "laravel\t\tphp\n\nframework" ) );
		$this->assertSame(
			'laravel php framework',
			Str::squish(
				'
			laravel
			php
			framework
		'
			)
		);
		$this->assertSame( 'laravel php framework', Str::squish( '   laravel   php   framework   ' ) );
		$this->assertSame( '123', Str::squish( '   123    ' ) );
		$this->assertSame( 'だ', Str::squish( 'だ' ) );
		$this->assertSame( 'ム', Str::squish( 'ム' ) );
		$this->assertSame( 'だ', Str::squish( '   だ    ' ) );
		$this->assertSame( 'ム', Str::squish( '   ム    ' ) );
		$this->assertSame( 'laravel php framework', Str::squish( 'laravelㅤㅤㅤphpㅤframework' ) );
		$this->assertSame( 'laravel php framework', Str::squish( 'laravelᅠᅠᅠᅠᅠᅠᅠᅠᅠᅠphpᅠᅠframework' ) );
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

		$this->assertSame( 'ÖffentlicheÜberraschungen', Str::studly( 'öffentliche-überraschungen' ) );
	}

	public function testMask() {
		$this->assertSame( 'tay*************', Str::mask( 'taylor@email.com', '*', 3 ) );
		$this->assertSame( '******@email.com', Str::mask( 'taylor@email.com', '*', 0, 6 ) );
		$this->assertSame( 'tay*************', Str::mask( 'taylor@email.com', '*', -13 ) );
		$this->assertSame( 'tay***@email.com', Str::mask( 'taylor@email.com', '*', -13, 3 ) );

		$this->assertSame( '****************', Str::mask( 'taylor@email.com', '*', -17 ) );
		$this->assertSame( '*****r@email.com', Str::mask( 'taylor@email.com', '*', -99, 5 ) );

		$this->assertSame( 'taylor@email.com', Str::mask( 'taylor@email.com', '*', 16 ) );
		$this->assertSame( 'taylor@email.com', Str::mask( 'taylor@email.com', '*', 16, 99 ) );

		$this->assertSame( 'taylor@email.com', Str::mask( 'taylor@email.com', '', 3 ) );

		$this->assertSame( 'taysssssssssssss', Str::mask( 'taylor@email.com', 'something', 3 ) );
		$this->assertSame( 'taysssssssssssss', Str::mask( 'taylor@email.com', Str::of( 'something' ), 3 ) );

		$this->assertSame( '这是一***', Str::mask( '这是一段中文', '*', 3 ) );
		$this->assertSame( '**一段中文', Str::mask( '这是一段中文', '*', 0, 2 ) );

		$this->assertSame( 'ma*n@email.com', Str::mask( 'maan@email.com', '*', 2, 1 ) );
		$this->assertSame( 'ma***email.com', Str::mask( 'maan@email.com', '*', 2, 3 ) );
		$this->assertSame( 'ma************', Str::mask( 'maan@email.com', '*', 2 ) );

		$this->assertSame( 'mari*@email.com', Str::mask( 'maria@email.com', '*', 4, 1 ) );
		$this->assertSame( 'tamar*@email.com', Str::mask( 'tamara@email.com', '*', 5, 1 ) );

		$this->assertSame( '*aria@email.com', Str::mask( 'maria@email.com', '*', 0, 1 ) );
		$this->assertSame( 'maria@email.co*', Str::mask( 'maria@email.com', '*', -1, 1 ) );
		$this->assertSame( 'maria@email.co*', Str::mask( 'maria@email.com', '*', -1 ) );
		$this->assertSame( '***************', Str::mask( 'maria@email.com', '*', -15 ) );
		$this->assertSame( '***************', Str::mask( 'maria@email.com', '*', 0 ) );
	}

	public function testMatch() {
		$this->assertSame( 'bar', Str::match( '/bar/', 'foo bar' ) );
		$this->assertSame( 'bar', Str::match( '/foo (.*)/', 'foo bar' ) );
		$this->assertEmpty( Str::match( '/nothing/', 'foo bar' ) );

		$this->assertEquals( [ 'bar', 'bar' ], Str::match_all( '/bar/', 'bar foo bar' )->all() );

		$this->assertEquals( [ 'un', 'ly' ], Str::match_all( '/f(\w*)/', 'bar fun bar fly' )->all() );
		$this->assertEmpty( Str::match_all( '/nothing/', 'bar fun bar fly' ) );
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

	public function testCharAt() {
		$this->assertEquals( 'р', Str::char_at( 'Привет, мир!', 1 ) );
		$this->assertEquals( 'ち', Str::char_at( '「こんにちは世界」', 4 ) );
		$this->assertEquals( 'w', Str::char_at( 'Привет, world!', 8 ) );
		$this->assertEquals( '界', Str::char_at( '「こんにちは世界」', -2 ) );
		$this->assertEquals( null, Str::char_at( '「こんにちは世界」', -200 ) );
		$this->assertEquals( null, Str::char_at( 'Привет, мир!', 100 ) );
	}

	public function testSubstr() {
		$this->assertSame( 'Ё', Str::substr( 'БГДЖИЛЁ', -1 ) );
		$this->assertSame( 'ЛЁ', Str::substr( 'БГДЖИЛЁ', -2 ) );
		$this->assertSame( 'И', Str::substr( 'БГДЖИЛЁ', -3, 1 ) );
		$this->assertSame( 'ДЖИЛ', Str::substr( 'БГДЖИЛЁ', 2, -1 ) );
		$this->assertEmpty( Str::substr( 'БГДЖИЛЁ', 4, -4 ) );
		$this->assertSame( 'ИЛ', Str::substr( 'БГДЖИЛЁ', -3, -1 ) );
		$this->assertSame( 'ГДЖИЛЁ', Str::substr( 'БГДЖИЛЁ', 1 ) );
		$this->assertSame( 'ГДЖ', Str::substr( 'БГДЖИЛЁ', 1, 3 ) );
		$this->assertSame( 'БГДЖ', Str::substr( 'БГДЖИЛЁ', 0, 4 ) );
		$this->assertSame( 'Ё', Str::substr( 'БГДЖИЛЁ', -1, 1 ) );
		$this->assertEmpty( Str::substr( 'Б', 2 ) );
	}

	public function testSubstrCount() {
		 $this->assertSame( 3, Str::substr_count( 'laravelPHPFramework', 'a' ) );
		$this->assertSame( 0, Str::substr_count( 'laravelPHPFramework', 'z' ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'l', 2 ) );
		$this->assertSame( 0, Str::substr_count( 'laravelPHPFramework', 'z', 2 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'k', -1 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'k', -1 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'a', 1, 2 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'a', 1, 2 ) );
		$this->assertSame( 3, Str::substr_count( 'laravelPHPFramework', 'a', 1, -2 ) );
		$this->assertSame( 1, Str::substr_count( 'laravelPHPFramework', 'a', -10, -3 ) );
	}

	public function testSubstrReplace() {
		$this->assertSame( '12:00', Str::substr_replace( '1200', ':', 2, 0 ) );
		$this->assertSame( 'The Laravel Framework', Str::substr_replace( 'The Framework', 'Laravel ', 4, 0 ) );
		$this->assertSame( 'Laravel – The PHP Framework for Web Artisans', Str::substr_replace( 'Laravel Framework', '– The PHP Framework for Web Artisans', 8 ) );
	}

	public function testLcfirst() {
		 $this->assertSame( 'laravel', Str::lcfirst( 'Laravel' ) );
		$this->assertSame( 'laravel framework', Str::lcfirst( 'Laravel framework' ) );
		$this->assertSame( 'мама', Str::lcfirst( 'Мама' ) );
		$this->assertSame( 'мама мыла раму', Str::lcfirst( 'Мама мыла раму' ) );
	}

	public function testUcfirst() {
		 $this->assertSame( 'Laravel', Str::ucfirst( 'laravel' ) );
		$this->assertSame( 'Laravel framework', Str::ucfirst( 'laravel framework' ) );
		$this->assertSame( 'Мама', Str::ucfirst( 'мама' ) );
		$this->assertSame( 'Мама мыла раму', Str::ucfirst( 'мама мыла раму' ) );
	}

	public function testUcsplit() {
		 $this->assertSame( [ 'Laravel_p_h_p_framework' ], Str::ucsplit( 'Laravel_p_h_p_framework' ) );
		$this->assertSame( [ 'Laravel_', 'P_h_p_framework' ], Str::ucsplit( 'Laravel_P_h_p_framework' ) );
		$this->assertSame( [ 'laravel', 'P', 'H', 'P', 'Framework' ], Str::ucsplit( 'laravelPHPFramework' ) );
		$this->assertSame( [ 'Laravel-ph', 'P-framework' ], Str::ucsplit( 'Laravel-phP-framework' ) );

		$this->assertSame( [ 'Żółta', 'Łódka' ], Str::ucsplit( 'ŻółtaŁódka' ) );
		$this->assertSame( [ 'sind', 'Öde', 'Und', 'So' ], Str::ucsplit( 'sindÖdeUndSo' ) );
		$this->assertSame( [ 'Öffentliche', 'Überraschungen' ], Str::ucsplit( 'ÖffentlicheÜberraschungen' ) );
	}

	public function testUuid() {
		$this->assertInstanceOf( UuidInterface::class, Str::uuid() );
		$this->assertIsString( (string) Str::uuid() );
	}

	public function testAsciiNull() {
		$this->assertSame( '', Str::ascii( null ) );
		$this->assertTrue( Str::is_ascii( null ) );
		$this->assertSame( '', Str::slug( null ) );
	}

	public function testPadBoth() {
		$this->assertSame( '__Alien___', Str::pad_both( 'Alien', 10, '_' ) );
		$this->assertSame( '  Alien   ', Str::pad_both( 'Alien', 10 ) );
		$this->assertSame( '  ❤MultiByte☆   ', Str::pad_both( '❤MultiByte☆', 16 ) );
		$this->assertSame( '❤☆❤MultiByte☆❤☆❤', Str::pad_both( '❤MultiByte☆', 16, '❤☆' ) );
	}

	public function testPadLeft() {
		 $this->assertSame( '-=-=-Alien', Str::pad_left( 'Alien', 10, '-=' ) );
		$this->assertSame( '     Alien', Str::pad_left( 'Alien', 10 ) );
		$this->assertSame( '     ❤MultiByte☆', Str::pad_left( '❤MultiByte☆', 16 ) );
		$this->assertSame( '❤☆❤☆❤❤MultiByte☆', Str::pad_left( '❤MultiByte☆', 16, '❤☆' ) );
	}

	public function testPadRight() {
		$this->assertSame( 'Alien-=-=-', Str::pad_right( 'Alien', 10, '-=' ) );
		$this->assertSame( 'Alien     ', Str::pad_right( 'Alien', 10 ) );
		$this->assertSame( '❤MultiByte☆     ', Str::pad_right( '❤MultiByte☆', 16 ) );
		$this->assertSame( '❤MultiByte☆❤☆❤☆❤', Str::pad_right( '❤MultiByte☆', 16, '❤☆' ) );
	}

	public function testSwapKeywords(): void {
		$this->assertSame(
			'PHP 8 is fantastic',
			Str::swap(
				[
					'PHP'     => 'PHP 8',
					'awesome' => 'fantastic',
				],
				'PHP is awesome'
			)
		);

		$this->assertSame(
			'foo bar baz',
			Str::swap(
				[
					'ⓐⓑ' => 'baz',
				],
				'foo bar ⓐⓑ'
			)
		);
	}

	public function testWordCount() {
		$this->assertEquals( 2, Str::word_count( 'Hello, world!' ) );
		$this->assertEquals( 10, Str::word_count( 'Hi, this is my first contribution to the Laravel framework.' ) );

		$this->assertEquals( 0, Str::word_count( 'мама' ) );
		$this->assertEquals( 0, Str::word_count( 'мама мыла раму' ) );

		$this->assertEquals( 1, Str::word_count( 'мама', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ' ) );
		$this->assertEquals( 3, Str::word_count( 'мама мыла раму', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ' ) );

		$this->assertEquals( 1, Str::word_count( 'МАМА', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ' ) );
		$this->assertEquals( 3, Str::word_count( 'МАМА МЫЛА РАМУ', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ' ) );
	}

	public static function validUuidList() {
		return [
			[ 'a0a2a2d2-0b87-4a18-83f2-2529882be2de' ],
			[ '145a1e72-d11d-11e8-a8d5-f2801f1b9fd1' ],
			[ '00000000-0000-0000-0000-000000000000' ],
			[ 'e60d3f48-95d7-4d8d-aad0-856f29a27da2' ],
			[ 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66' ],
			[ 'ff6f8cb0-c57d-21e1-9b21-0800200c9a66' ],
			[ 'ff6f8cb0-c57d-31e1-9b21-0800200c9a66' ],
			[ 'ff6f8cb0-c57d-41e1-9b21-0800200c9a66' ],
			[ 'ff6f8cb0-c57d-51e1-9b21-0800200c9a66' ],
			[ 'FF6F8CB0-C57D-11E1-9B21-0800200C9A66' ],
		];
	}

	public static function invalidUuidList() {
		return [
			[ 'not a valid uuid so we can test this' ],
			[ 'zf6f8cb0-c57d-11e1-9b21-0800200c9a66' ],
			[ '145a1e72-d11d-11e8-a8d5-f2801f1b9fd1' . PHP_EOL ],
			[ '145a1e72-d11d-11e8-a8d5-f2801f1b9fd1 ' ],
			[ ' 145a1e72-d11d-11e8-a8d5-f2801f1b9fd1' ],
			[ '145a1e72-d11d-11e8-a8d5-f2z01f1b9fd1' ],
			[ '3f6f8cb0-c57d-11e1-9b21-0800200c9a6' ],
			[ 'af6f8cb-c57d-11e1-9b21-0800200c9a66' ],
			[ 'af6f8cb0c57d11e19b210800200c9a66' ],
			[ 'ff6f8cb0-c57da-51e1-9b21-0800200c9a66' ],
		];
	}

	public static function strContainsProvider() {
		return [
			[ 'Taylor', 'ylo', true, true ],
			[ 'Taylor', 'ylo', true, false ],
			[ 'Taylor', 'taylor', true, true ],
			[ 'Taylor', 'taylor', false, false ],
			[ 'Taylor', [ 'ylo' ], true, true ],
			[ 'Taylor', [ 'ylo' ], true, false ],
			[ 'Taylor', [ 'xxx', 'ylo' ], true, true ],
			[ 'Taylor', collect( [ 'xxx', 'ylo' ] ), true, true ],
			[ 'Taylor', [ 'xxx', 'ylo' ], true, false ],
			[ 'Taylor', 'xxx', false ],
			[ 'Taylor', [ 'xxx' ], false ],
			[ 'Taylor', '', false ],
			[ '', '', false ],
		];
	}

	public static function strContainsAllProvider() {
		return [
			[ 'Taylor Otwell', [ 'taylor', 'otwell' ], false, false ],
			[ 'Taylor Otwell', [ 'taylor', 'otwell' ], true, true ],
			[ 'Taylor Otwell', [ 'taylor' ], false, false ],
			[ 'Taylor Otwell', [ 'taylor' ], true, true ],
			[ 'Taylor Otwell', [ 'taylor', 'xxx' ], false, false ],
			[ 'Taylor Otwell', [ 'taylor', 'xxx' ], false, true ],
		];
	}

	public function testMarkdown() {
		$this->assertSame( "<p><em>hello world</em></p>\n", Str::markdown( '*hello world*' ) );
		$this->assertSame( "<h1>hello world</h1>\n", Str::markdown( '# hello world' ) );
	}

	public function testInlineMarkdown() {
		$this->assertSame( "<em>hello world</em>\n", Str::inline_markdown( '*hello world*' ) );
		$this->assertSame( "<a href=\"https://laravel.com\"><strong>Laravel</strong></a>\n", Str::inline_markdown( '[**Laravel**](https://laravel.com)' ) );
	}

	public function testRepeat() {
		$this->assertSame( 'aaaaa', Str::repeat( 'a', 5 ) );
		$this->assertSame( '', Str::repeat( '', 5 ) );
	}

	/**
	 * @dataProvider specialCharacterProvider
	 */
	public function testTransliterate( string $value, string $expected ): void {
		$this->assertSame( $expected, Str::transliterate( $value ) );
	}

	public static function specialCharacterProvider(): array {
		return [
			[ 'ⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ', 'abcdefghijklmnopqrstuvwxyz' ],
			[ '⓪①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳', '01234567891011121314151617181920' ],
			[ '⓵⓶⓷⓸⓹⓺⓻⓼⓽⓾', '12345678910' ],
			[ '⓿⓫⓬⓭⓮⓯⓰⓱⓲⓳⓴', '011121314151617181920' ],
			[ 'ⓣⓔⓢⓣ@ⓛⓐⓡⓐⓥⓔⓛ.ⓒⓞⓜ', 'test@laravel.com' ],
			[ '🎂', '?' ],
			[ 'abcdefghijklmnopqrstuvwxyz', 'abcdefghijklmnopqrstuvwxyz' ],
			[ '0123456789', '0123456789' ],
		];
	}

	public function testTransliterateOverrideUnknown(): void {
		$this->assertSame( 'HHH', Str::transliterate( '🎂🚧🏆', 'H' ) );
		$this->assertSame( 'Hello', Str::transliterate( '🎂', 'Hello' ) );
	}

	/**
	 * @dataProvider specialCharacterProvider
	 */
	public function testTransliterateStrict( string $value, string $expected ): void {
		$this->assertSame( $expected, Str::transliterate( $value, '?', true ) );
	}

	public function testPasswordCreation() {
		$this->assertTrue( strlen( Str::password() ) === 32 );
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
