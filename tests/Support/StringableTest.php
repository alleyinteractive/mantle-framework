<?php

namespace Mantle\Tests\Support;

use Carbon\Carbon;
use Mantle\Support\Collection;
use Mantle\Support\Stringable;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/StrTest.php';

class StringableTest extends TestCase {

	/**
	 * @param  mixed $string
	 */
	protected function stringable( mixed $string = '' ): Stringable {
		return new Stringable( $string );
	}

	public function testClassBasename(): void {
		$this->assertEquals(
			class_basename( static::class ),
			$this->stringable( static::class )->class_basename()
		);
	}

	public function testIsAscii(): void {
		$this->assertTrue( $this->stringable( 'A' )->is_ascii() );
		$this->assertFalse( $this->stringable( 'ù' )->is_ascii() );
	}

	public function testIsUuid(): void {
		$this->assertTrue( $this->stringable( '2cdc7039-65a6-4ac7-8e5d-d554a98e7b15' )->is_uuid() );
		$this->assertFalse( $this->stringable( '2cdc7039-65a6-4ac7-8e5d-d554a98' )->is_uuid() );
	}

	public function testIsJson(): void {
		$this->assertTrue( $this->stringable( '1' )->is_json() );
		$this->assertTrue( $this->stringable( '[1,2,3]' )->is_json() );
		$this->assertTrue( $this->stringable( '[1,   2,   3]' )->is_json() );
		$this->assertTrue( $this->stringable( '{"first": "John", "last": "Doe"}' )->is_json() );
		$this->assertTrue( $this->stringable( '[{"first": "John", "last": "Doe"}, {"first": "Jane", "last": "Doe"}]' )->is_json() );

		$this->assertFalse( $this->stringable( '1,' )->is_json() );
		$this->assertFalse( $this->stringable( '[1,2,3' )->is_json() );
		$this->assertFalse( $this->stringable( '[1,   2   3]' )->is_json() );
		$this->assertFalse( $this->stringable( '{first: "John"}' )->is_json() );
		$this->assertFalse( $this->stringable( '[{first: "John"}, {first: "Jane"}]' )->is_json() );
		$this->assertFalse( $this->stringable( '' )->is_json() );
		$this->assertFalse( $this->stringable( null )->is_json() );
	}

	public function testIsMatch(): void {
		$this->assertTrue( $this->stringable( 'Hello, Laravel!' )->is_match( '/.*,.*!/' ) );
		$this->assertTrue( $this->stringable( 'Hello, Laravel!' )->is_match( '/^.*$(.*)/' ) );
		$this->assertTrue( $this->stringable( 'Hello, Laravel!' )->is_match( '/laravel/i' ) );
		$this->assertTrue( $this->stringable( 'Hello, Laravel!' )->is_match( '/^(.*(.*(.*)))/' ) );

		$this->assertFalse( $this->stringable( 'Hello, Laravel!' )->is_match( '/H.o/' ) );
		$this->assertFalse( $this->stringable( 'Hello, Laravel!' )->is_match( '/^laravel!/i' ) );
		$this->assertFalse( $this->stringable( 'Hello, Laravel!' )->is_match( '/laravel!(.*)/' ) );
		$this->assertFalse( $this->stringable( 'Hello, Laravel!' )->is_match( '/^[a-zA-Z,!]+$/' ) );

		$this->assertTrue( $this->stringable( 'Hello, Laravel!' )->is_match( [ '/.*,.*!/', '/H.o/' ] ) );
		$this->assertTrue( $this->stringable( 'Hello, Laravel!' )->is_match( [ '/^laravel!/i', '/^.*$(.*)/' ] ) );
		$this->assertTrue( $this->stringable( 'Hello, Laravel!' )->is_match( [ '/laravel/i', '/laravel!(.*)/' ] ) );
		$this->assertTrue( $this->stringable( 'Hello, Laravel!' )->is_match( [ '/^[a-zA-Z,!]+$/', '/^(.*(.*(.*)))/' ] ) );
	}

	public function testIsEmpty(): void {
		$this->assertTrue( $this->stringable( '' )->is_empty() );
		$this->assertFalse( $this->stringable( 'A' )->is_empty() );
		$this->assertFalse( $this->stringable( '0' )->is_empty() );
	}

	public function testIsNotEmpty(): void {
		$this->assertFalse( $this->stringable( '' )->is_not_empty() );
		$this->assertTrue( $this->stringable( 'A' )->is_not_empty() );
	}

	public function testPluralStudly(): void {
		$this->assertSame( 'LaraCon', (string) $this->stringable( 'LaraCon' )->plural_studly( 1 ) );
		$this->assertSame( 'LaraCons', (string) $this->stringable( 'LaraCon' )->plural_studly( 2 ) );
		$this->assertSame( 'LaraCon', (string) $this->stringable( 'LaraCon' )->plural_studly( -1 ) );
		$this->assertSame( 'LaraCons', (string) $this->stringable( 'LaraCon' )->plural_studly( -2 ) );
	}

	public function testMatch(): void {
		$stringable = $this->stringable( 'foo bar' );

		$this->assertSame( 'bar', (string) $stringable->match( '/bar/' ) );
		$this->assertSame( 'bar', (string) $stringable->match( '/foo (.*)/' ) );
		$this->assertTrue( $stringable->match( '/nothing/' )->is_empty() );

		$this->assertSame( [ 'bar', 'bar' ], $this->stringable( 'bar foo bar' )->match_all( '/bar/' )->all() );

		$stringable = $this->stringable( 'bar fun bar fly' );

		$this->assertSame( [ 'un', 'ly' ], $stringable->match_all( '/f(\w*)/' )->all() );
		$this->assertTrue( $stringable->match_all( '/nothing/' )->is_empty() );
	}

	public function testTest(): void {
		$stringable = $this->stringable( 'foo bar' );

		$this->assertTrue( $stringable->test( '/bar/' ) );
		$this->assertTrue( $stringable->test( '/foo (.*)/' ) );
	}

	public function testTrim(): void {
		$this->assertSame( 'foo', (string) $this->stringable( ' foo ' )->trim() );
	}

	public function testLtrim(): void {
		$this->assertSame( 'foo ', (string) $this->stringable( ' foo ' )->ltrim() );
	}

	public function testRtrim(): void {
		$this->assertSame( ' foo', (string) $this->stringable( ' foo ' )->rtrim() );
	}

	public function testCanBeLimitedByWords(): void {
		$this->assertSame( 'Taylor...', (string) $this->stringable( 'Taylor Otwell' )->words( 1 ) );
		$this->assertSame( 'Taylor___', (string) $this->stringable( 'Taylor Otwell' )->words( 1, '___' ) );
		$this->assertSame( 'Taylor Otwell', (string) $this->stringable( 'Taylor Otwell' )->words( 3 ) );
	}

	public function testUnless(): void {
		$this->assertSame(
			'unless false',
			(string) $this->stringable( 'unless' )->unless(
				false,
				function ( $stringable, $value ) {
					return $stringable->append( ' false' );
				}
			)
		);

		$this->assertSame(
			'unless true fallbacks to default',
			(string) $this->stringable( 'unless' )->unless(
				true,
				function ( $stringable, $value ) {
					return $stringable->append( $value );
				},
				function ( $stringable ) {
					return $stringable->append( ' true fallbacks to default' );
				}
			)
		);
	}

	public function testWhenContains(): void {
		$this->assertSame(
			'Tony Stark',
			(string) $this->stringable( 'stark' )->when_contains(
				'tar',
				function ( $stringable ) {
					return $stringable->prepend( 'Tony ' )->title();
				},
				function ( $stringable ) {
					return $stringable->prepend( 'Arno ' )->title();
				}
			)
		);

		$this->assertSame(
			'stark',
			(string) $this->stringable( 'stark' )->when_contains(
				'xxx',
				function ( $stringable ) {
					return $stringable->prepend( 'Tony ' )->title();
				}
			)
		);

		$this->assertSame(
			'Arno Stark',
			(string) $this->stringable( 'stark' )->when_contains(
				'xxx',
				function ( $stringable ) {
					return $stringable->prepend( 'Tony ' )->title();
				},
				function ( $stringable ) {
					return $stringable->prepend( 'Arno ' )->title();
				}
			)
		);
	}

	public function testWhenContainsAll(): void {
		$this->assertSame(
			'Tony Stark',
			(string) $this->stringable( 'tony stark' )->when_contains_all(
				[ 'tony', 'stark' ],
				function ( $stringable ) {
					return $stringable->title();
				},
				function ( $stringable ) {
					return $stringable->studly();
				}
			)
		);

		$this->assertSame(
			'tony stark',
			(string) $this->stringable( 'tony stark' )->when_contains_all(
				[ 'xxx' ],
				function ( $stringable ) {
					return $stringable->title();
				}
			)
		);

		$this->assertSame(
			'TonyStark',
			(string) $this->stringable( 'tony stark' )->when_contains_all(
				[ 'tony', 'xxx' ],
				function ( $stringable ) {
					return $stringable->title();
				},
				function ( $stringable ) {
					return $stringable->studly();
				}
			)
		);
	}

	public function testDirname(): void {
		$this->assertSame( '/framework/tests', (string) $this->stringable( '/framework/tests/Support' )->dirname() );
		$this->assertSame( '/framework', (string) $this->stringable( '/framework/tests/Support' )->dirname( 2 ) );
		$this->assertSame( '.', (string) $this->stringable( 'framework' )->dirname() );

		$this->assertSame( '.', (string) $this->stringable( '.' )->dirname() );

		$this->assertSame( DIRECTORY_SEPARATOR, (string) $this->stringable( '/framework/' )->dirname() );
		$this->assertSame( DIRECTORY_SEPARATOR, (string) $this->stringable( '/' )->dirname() );
	}

	public function testUcsplitOnStringable(): void {
		$this->assertSame( [ 'Taylor', 'Otwell' ], $this->stringable( 'TaylorOtwell' )->ucsplit()->to_array() );
		$this->assertSame( [ 'Hello', 'From', 'Laravel' ], $this->stringable( 'HelloFromLaravel' )->ucsplit()->to_array() );
		$this->assertSame( [ 'He_llo_', 'World' ], $this->stringable( 'He_llo_World' )->ucsplit()->to_array() );
	}

	public function testWhenEndsWith(): void {
		$this->assertSame(
			'Tony Stark',
			(string) $this->stringable( 'tony stark' )->when_ends_with(
				'ark',
				function ( $stringable ) {
					return $stringable->title();
				},
				function ( $stringable ) {
					return $stringable->studly();
				}
			)
		);

		$this->assertSame(
			'Tony Stark',
			(string) $this->stringable( 'tony stark' )->when_ends_with(
				[ 'kra', 'ark' ],
				function ( $stringable ) {
					return $stringable->title();
				},
				function ( $stringable ) {
					return $stringable->studly();
				}
			)
		);

		$this->assertSame(
			'tony stark',
			(string) $this->stringable( 'tony stark' )->when_ends_with(
				[ 'xxx' ],
				function ( $stringable ) {
					return $stringable->title();
				}
			)
		);

		$this->assertSame(
			'TonyStark',
			(string) $this->stringable( 'tony stark' )->when_ends_with(
				[ 'tony', 'xxx' ],
				function ( $stringable ) {
					return $stringable->title();
				},
				function ( $stringable ) {
					return $stringable->studly();
				}
			)
		);
	}

	public function testWhenExactly(): void {
		$this->assertSame(
			'Nailed it...!',
			(string) $this->stringable( 'Tony Stark' )->when_exactly(
				'Tony Stark',
				function ( $stringable ) {
					return 'Nailed it...!';
				},
				function ( $stringable ) {
					return 'Swing and a miss...!';
				}
			)
		);

		$this->assertSame(
			'Swing and a miss...!',
			(string) $this->stringable( 'Tony Stark' )->when_exactly(
				'Iron Man',
				function ( $stringable ) {
					return 'Nailed it...!';
				},
				function ( $stringable ) {
					return 'Swing and a miss...!';
				}
			)
		);

		$this->assertSame(
			'Tony Stark',
			(string) $this->stringable( 'Tony Stark' )->when_exactly(
				'Iron Man',
				function ( $stringable ) {
					return 'Nailed it...!';
				}
			)
		);
	}

	public function testWhenNotExactly(): void {
		$this->assertSame(
			'Iron Man',
			(string) $this->stringable( 'Tony' )->when_not_exactly(
				'Tony Stark',
				function ( $stringable ) {
					return 'Iron Man';
				}
			)
		);

		$this->assertSame(
			'Swing and a miss...!',
			(string) $this->stringable( 'Tony Stark' )->when_not_exactly(
				'Tony Stark',
				function ( $stringable ) {
					return 'Iron Man';
				},
				function ( $stringable ) {
					return 'Swing and a miss...!';
				}
			)
		);
	}

	public function testWhenIs(): void {
		$this->assertSame(
			'Winner: /',
			(string) $this->stringable( '/' )->when_is(
				'/',
				function ( $stringable ) {
					return $stringable->prepend( 'Winner: ' );
				},
				function ( $stringable ) {
					return 'Try again';
				}
			)
		);

		$this->assertSame(
			'/',
			(string) $this->stringable( '/' )->when_is(
				' /',
				function ( $stringable ) {
					return $stringable->prepend( 'Winner: ' );
				}
			)
		);

		$this->assertSame(
			'Try again',
			(string) $this->stringable( '/' )->when_is(
				' /',
				function ( $stringable ) {
					return $stringable->prepend( 'Winner: ' );
				},
				function ( $stringable ) {
					return 'Try again';
				}
			)
		);

		$this->assertSame(
			'Winner: foo/bar/baz',
			(string) $this->stringable( 'foo/bar/baz' )->when_is(
				'foo/*',
				function ( $stringable ) {
					return $stringable->prepend( 'Winner: ' );
				}
			)
		);
	}

	public function testWhenIsAscii(): void {
		$this->assertSame(
			'Ascii: A',
			(string) $this->stringable( 'A' )->when_is_ascii(
				function ( $stringable ) {
					return $stringable->prepend( 'Ascii: ' );
				},
				function ( $stringable ) {
					return $stringable->prepend( 'Not Ascii: ' );
				}
			)
		);

		$this->assertSame(
			'ù',
			(string) $this->stringable( 'ù' )->when_is_ascii(
				function ( $stringable ) {
					return $stringable->prepend( 'Ascii: ' );
				}
			)
		);

		$this->assertSame(
			'Not Ascii: ù',
			(string) $this->stringable( 'ù' )->when_is_ascii(
				function ( $stringable ) {
					return $stringable->prepend( 'Ascii: ' );
				},
				function ( $stringable ) {
					return $stringable->prepend( 'Not Ascii: ' );
				}
			)
		);
	}

	public function testWhenIsUuid(): void {
		$this->assertSame(
			'Uuid: 2cdc7039-65a6-4ac7-8e5d-d554a98e7b15',
			(string) $this->stringable( '2cdc7039-65a6-4ac7-8e5d-d554a98e7b15' )->when_is_uuid(
				function ( $stringable ) {
					return $stringable->prepend( 'Uuid: ' );
				},
				function ( $stringable ) {
					return $stringable->prepend( 'Not Uuid: ' );
				}
			)
		);

		$this->assertSame(
			'2cdc7039-65a6-4ac7-8e5d-d554a98',
			(string) $this->stringable( '2cdc7039-65a6-4ac7-8e5d-d554a98' )->when_is_uuid(
				function ( $stringable ) {
					return $stringable->prepend( 'Uuid: ' );
				}
			)
		);

		$this->assertSame(
			'Not Uuid: 2cdc7039-65a6-4ac7-8e5d-d554a98',
			(string) $this->stringable( '2cdc7039-65a6-4ac7-8e5d-d554a98' )->when_is_uuid(
				function ( $stringable ) {
					return $stringable->prepend( 'Uuid: ' );
				},
				function ( $stringable ) {
					return $stringable->prepend( 'Not Uuid: ' );
				}
			)
		);
	}

	public function testWhenTest(): void {
		$this->assertSame(
			'Winner: foo bar',
			(string) $this->stringable( 'foo bar' )->when_test(
				'/bar/',
				function ( $stringable ) {
					return $stringable->prepend( 'Winner: ' );
				},
				function ( $stringable ) {
					return 'Try again';
				}
			)
		);

		$this->assertSame(
			'Try again',
			(string) $this->stringable( 'foo bar' )->when_test(
				'/link/',
				function ( $stringable ) {
					return $stringable->prepend( 'Winner: ' );
				},
				function ( $stringable ) {
					return 'Try again';
				}
			)
		);

		$this->assertSame(
			'foo bar',
			(string) $this->stringable( 'foo bar' )->when_test(
				'/link/',
				function ( $stringable ) {
					return $stringable->prepend( 'Winner: ' );
				}
			)
		);
	}

	public function testWhenStartsWith(): void {
		$this->assertSame(
			'Tony Stark',
			(string) $this->stringable( 'tony stark' )->when_starts_with(
				'ton',
				function ( $stringable ) {
					return $stringable->title();
				},
				function ( $stringable ) {
					return $stringable->studly();
				}
			)
		);

		$this->assertSame(
			'Tony Stark',
			(string) $this->stringable( 'tony stark' )->when_starts_with(
				[ 'ton', 'not' ],
				function ( $stringable ) {
					return $stringable->title();
				},
				function ( $stringable ) {
					return $stringable->studly();
				}
			)
		);

		$this->assertSame(
			'tony stark',
			(string) $this->stringable( 'tony stark' )->when_starts_with(
				[ 'xxx' ],
				function ( $stringable ) {
					return $stringable->title();
				}
			)
		);

		$this->assertSame(
			'Tony Stark',
			(string) $this->stringable( 'tony stark' )->when_starts_with(
				[ 'tony', 'xxx' ],
				function ( $stringable ) {
					return $stringable->title();
				},
				function ( $stringable ) {
					return $stringable->studly();
				}
			)
		);
	}

	public function testWhenEmpty(): void {
		tap(
			$this->stringable(),
			function ( $stringable ) {
				$this->assertSame(
					$stringable,
					$stringable->when_empty(
						function () {
						}
					)
				);
			}
		);

		$this->assertSame(
			'empty',
			(string) $this->stringable()->when_empty(
				function () {
					return 'empty';
				}
			)
		);

		$this->assertSame(
			'not-empty',
			(string) $this->stringable( 'not-empty' )->when_empty(
				function () {
					return 'empty';
				}
			)
		);
	}

	public function testWhenNotEmpty(): void {
		tap(
			$this->stringable(),
			function ( $stringable ) {
				$this->assertSame(
					$stringable,
					$stringable->when_not_empty(
						function ( $stringable ) {
							return $stringable . '.';
						}
					)
				);
			}
		);

		$this->assertSame(
			'',
			(string) $this->stringable()->when_not_empty(
				function ( $stringable ) {
					return $stringable . '.';
				}
			)
		);

		$this->assertSame(
			'Not empty.',
			(string) $this->stringable( 'Not empty' )->when_not_empty(
				function ( $stringable ) {
					return $stringable . '.';
				}
			)
		);
	}

	public function testWhenFalse(): void {
		$this->assertSame(
			'when',
			(string) $this->stringable( 'when' )->when(
				false,
				function ( $stringable, $value ) {
					return $stringable->append( $value )->append( 'false' );
				}
			)
		);

		$this->assertSame(
			'when false fallbacks to default',
			(string) $this->stringable( 'when false ' )->when(
				false,
				function ( $stringable, $value ) {
					return $stringable->append( $value );
				},
				function ( $stringable ) {
					return $stringable->append( 'fallbacks to default' );
				}
			)
		);
	}

	public function testWhenTrue(): void {
		$this->assertSame(
			'when true',
			(string) $this->stringable( 'when ' )->when(
				true,
				function ( $stringable ) {
					return $stringable->append( 'true' );
				}
			)
		);

		$this->assertSame(
			'gets a value from if',
			(string) $this->stringable( 'gets a value ' )->when(
				'from if',
				function ( $stringable, $value ) {
					return $stringable->append( $value );
				},
				function ( $stringable ) {
					return $stringable->append( 'fallbacks to default' );
				}
			)
		);
	}

	public function testUnlessTruthy(): void {
		$this->assertSame(
			'unless',
			(string) $this->stringable( 'unless' )->unless(
				1,
				function ( $stringable, $value ) {
					return $stringable->append( $value )->append( 'true' );
				}
			)
		);

		$this->assertSame(
			'unless true fallbacks to default with value 1',
			(string) $this->stringable( 'unless true ' )->unless(
				1,
				function ( $stringable, $value ) {
					return $stringable->append( $value );
				},
				function ( $stringable, $value ) {
					return $stringable->append( 'fallbacks to default with value ' )->append( $value );
				}
			)
		);
	}

	public function testUnlessFalsy(): void {
		$this->assertSame(
			'unless 0',
			(string) $this->stringable( 'unless ' )->unless(
				0,
				function ( $stringable, $value ) {
					return $stringable->append( $value );
				}
			)
		);

		$this->assertSame(
			'gets the value 0',
			(string) $this->stringable( 'gets the value ' )->unless(
				0,
				function ( $stringable, $value ) {
					return $stringable->append( $value );
				},
				function ( $stringable ) {
					return $stringable->append( 'fallbacks to default' );
				}
			)
		);
	}

	public function testTrimmedOnlyWhereNecessary(): void {
		$this->assertSame( ' Taylor Otwell ', (string) $this->stringable( ' Taylor Otwell ' )->words( 3 ) );
		$this->assertSame( ' Taylor...', (string) $this->stringable( ' Taylor Otwell ' )->words( 1 ) );
	}

	public function testTitle(): void {
		$this->assertSame( 'Jefferson Costella', (string) $this->stringable( 'jefferson costella' )->title() );
		$this->assertSame( 'Jefferson Costella', (string) $this->stringable( 'jefFErson coSTella' )->title() );
	}

	public function testWithoutWordsDoesntProduceError(): void {
		$nbsp = chr( 0xC2 ) . chr( 0xA0 );
		$this->assertSame( ' ', (string) $this->stringable( ' ' )->words() );
		$this->assertSame( $nbsp, (string) $this->stringable( $nbsp )->words() );
	}

	public function testAscii(): void {
		$this->assertSame( '@', (string) $this->stringable( '@' )->ascii() );
		$this->assertSame( 'u', (string) $this->stringable( 'ü' )->ascii() );
	}

	public function testNewLine(): void {
		$this->assertSame( 'Laravel' . PHP_EOL, (string) $this->stringable( 'Laravel' )->newLine() );
		$this->assertSame( 'foo' . PHP_EOL . PHP_EOL . 'bar', (string) $this->stringable( 'foo' )->newLine( 2 )->append( 'bar' ) );
	}

	public function testAsciiWithSpecificLocale(): void {
		$this->assertSame( 'h H sht Sht a A ia yo', (string) $this->stringable( 'х Х щ Щ ъ Ъ иа йо' )->ascii( 'bg' ) );
		$this->assertSame( 'ae oe ue Ae Oe Ue', (string) $this->stringable( 'ä ö ü Ä Ö Ü' )->ascii( 'de' ) );
	}

	public function testStartsWith(): void {
		$this->assertTrue( $this->stringable( 'jason' )->startsWith( 'jas' ) );
		$this->assertTrue( $this->stringable( 'jason' )->startsWith( 'jason' ) );
		$this->assertTrue( $this->stringable( 'jason' )->startsWith( [ 'jas' ] ) );
		$this->assertTrue( $this->stringable( 'jason' )->startsWith( [ 'day', 'jas' ] ) );
		$this->assertTrue( $this->stringable( 'jason' )->startsWith( collect( [ 'day', 'jas' ] ) ) );
		$this->assertFalse( $this->stringable( 'jason' )->startsWith( 'day' ) );
		$this->assertFalse( $this->stringable( 'jason' )->startsWith( [ 'day' ] ) );
		$this->assertFalse( $this->stringable( 'jason' )->startsWith( 'J' ) );
		$this->assertFalse( $this->stringable( 'jason' )->startsWith( '' ) );
		$this->assertFalse( $this->stringable( '7' )->startsWith( ' 7' ) );
		$this->assertTrue( $this->stringable( '7a' )->startsWith( '7' ) );
		$this->assertTrue( $this->stringable( 7.123 )->startsWith( '7' ) );
		$this->assertTrue( $this->stringable( 7.123 )->startsWith( '7.12' ) );
		$this->assertFalse( $this->stringable( 7.123 )->startsWith( '7.13' ) );
		// Test for multibyte string support
		$this->assertTrue( $this->stringable( 'Jönköping' )->startsWith( 'Jö' ) );
		$this->assertTrue( $this->stringable( 'Malmö' )->startsWith( 'Malmö' ) );
		$this->assertFalse( $this->stringable( 'Jönköping' )->startsWith( 'Jonko' ) );
		$this->assertFalse( $this->stringable( 'Malmö' )->startsWith( 'Malmo' ) );
	}

	public function testEndsWith(): void {
		$this->assertTrue( $this->stringable( 'jason' )->ends_with( 'on' ) );
		$this->assertTrue( $this->stringable( 'jason' )->ends_with( 'jason' ) );
		$this->assertTrue( $this->stringable( 'jason' )->ends_with( [ 'on' ] ) );
		$this->assertTrue( $this->stringable( 'jason' )->ends_with( [ 'no', 'on' ] ) );
		$this->assertTrue( $this->stringable( 'jason' )->ends_with( collect( [ 'no', 'on' ] ) ) );
		$this->assertFalse( $this->stringable( 'jason' )->ends_with( 'no' ) );
		$this->assertFalse( $this->stringable( 'jason' )->ends_with( [ 'no' ] ) );
		$this->assertFalse( $this->stringable( 'jason' )->ends_with( '' ) );
		$this->assertFalse( $this->stringable( 'jason' )->ends_with( [ null ] ) );
		$this->assertFalse( $this->stringable( 'jason' )->ends_with( null ) );
		$this->assertFalse( $this->stringable( 'jason' )->ends_with( 'N' ) );
		$this->assertFalse( $this->stringable( '7' )->ends_with( ' 7' ) );
		$this->assertTrue( $this->stringable( 'a7' )->ends_with( '7' ) );
		$this->assertTrue( $this->stringable( 'a7' )->ends_with( 7 ) );
		$this->assertTrue( $this->stringable( 'a7.12' )->ends_with( 7.12 ) );
		$this->assertFalse( $this->stringable( 'a7.12' )->ends_with( 7.13 ) );
		$this->assertTrue( $this->stringable( 0.27 )->ends_with( '7' ) );
		$this->assertTrue( $this->stringable( 0.27 )->ends_with( '0.27' ) );
		$this->assertFalse( $this->stringable( 0.27 )->ends_with( '8' ) );
		// Test for multibyte string support
		$this->assertTrue( $this->stringable( 'Jönköping' )->ends_with( 'öping' ) );
		$this->assertTrue( $this->stringable( 'Malmö' )->ends_with( 'mö' ) );
		$this->assertFalse( $this->stringable( 'Jönköping' )->ends_with( 'oping' ) );
		$this->assertFalse( $this->stringable( 'Malmö' )->ends_with( 'mo' ) );
	}

	public function testExcerpt(): void {
		$this->assertSame( '...is a beautiful morn...', (string) $this->stringable( 'This is a beautiful morning' )->excerpt( 'beautiful', [ 'radius' => 5 ] ) );
	}

	public function testBefore(): void {
		$this->assertSame( 'han', (string) $this->stringable( 'hannah' )->before( 'nah' ) );
		$this->assertSame( 'ha', (string) $this->stringable( 'hannah' )->before( 'n' ) );
		$this->assertSame( 'ééé ', (string) $this->stringable( 'ééé hannah' )->before( 'han' ) );
		$this->assertSame( 'hannah', (string) $this->stringable( 'hannah' )->before( 'xxxx' ) );
		$this->assertSame( 'hannah', (string) $this->stringable( 'hannah' )->before( '' ) );
		$this->assertSame( 'han', (string) $this->stringable( 'han0nah' )->before( '0' ) );
	}

	public function testBeforeLast(): void {
		$this->assertSame( 'yve', (string) $this->stringable( 'yvette' )->before_last( 'tte' ) );
		$this->assertSame( 'yvet', (string) $this->stringable( 'yvette' )->before_last( 't' ) );
		$this->assertSame( 'ééé ', (string) $this->stringable( 'ééé yvette' )->before_last( 'yve' ) );
		$this->assertSame( '', (string) $this->stringable( 'yvette' )->before_last( 'yve' ) );
		$this->assertSame( 'yvette', (string) $this->stringable( 'yvette' )->before_last( 'xxxx' ) );
		$this->assertSame( 'yvette', (string) $this->stringable( 'yvette' )->before_last( '' ) );
		$this->assertSame( 'yv0et', (string) $this->stringable( 'yv0et0te' )->before_last( '0' ) );
	}

	public function testBetween(): void {
		$this->assertSame( 'abc', (string) $this->stringable( 'abc' )->between( '', 'c' ) );
		$this->assertSame( 'abc', (string) $this->stringable( 'abc' )->between( 'a', '' ) );
		$this->assertSame( 'abc', (string) $this->stringable( 'abc' )->between( '', '' ) );
		$this->assertSame( 'b', (string) $this->stringable( 'abc' )->between( 'a', 'c' ) );
		$this->assertSame( 'b', (string) $this->stringable( 'dddabc' )->between( 'a', 'c' ) );
		$this->assertSame( 'b', (string) $this->stringable( 'abcddd' )->between( 'a', 'c' ) );
		$this->assertSame( 'b', (string) $this->stringable( 'dddabcddd' )->between( 'a', 'c' ) );
		$this->assertSame( 'nn', (string) $this->stringable( 'hannah' )->between( 'ha', 'ah' ) );
		$this->assertSame( 'a]ab[b', (string) $this->stringable( '[a]ab[b]' )->between( '[', ']' ) );
		$this->assertSame( 'foo', (string) $this->stringable( 'foofoobar' )->between( 'foo', 'bar' ) );
		$this->assertSame( 'bar', (string) $this->stringable( 'foobarbar' )->between( 'foo', 'bar' ) );
	}

	public function testBetweenFirst(): void {
		$this->assertSame( 'abc', (string) $this->stringable( 'abc' )->between_first( '', 'c' ) );
		$this->assertSame( 'abc', (string) $this->stringable( 'abc' )->between_first( 'a', '' ) );
		$this->assertSame( 'abc', (string) $this->stringable( 'abc' )->between_first( '', '' ) );
		$this->assertSame( 'b', (string) $this->stringable( 'abc' )->between_first( 'a', 'c' ) );
		$this->assertSame( 'b', (string) $this->stringable( 'dddabc' )->between_first( 'a', 'c' ) );
		$this->assertSame( 'b', (string) $this->stringable( 'abcddd' )->between_first( 'a', 'c' ) );
		$this->assertSame( 'b', (string) $this->stringable( 'dddabcddd' )->between_first( 'a', 'c' ) );
		$this->assertSame( 'nn', (string) $this->stringable( 'hannah' )->between_first( 'ha', 'ah' ) );
		$this->assertSame( 'a', (string) $this->stringable( '[a]ab[b]' )->between_first( '[', ']' ) );
		$this->assertSame( 'foo', (string) $this->stringable( 'foofoobar' )->between_first( 'foo', 'bar' ) );
		$this->assertSame( '', (string) $this->stringable( 'foobarbar' )->between_first( 'foo', 'bar' ) );
	}

	public function testAfter(): void {
		$this->assertSame( 'nah', (string) $this->stringable( 'hannah' )->after( 'han' ) );
		$this->assertSame( 'nah', (string) $this->stringable( 'hannah' )->after( 'n' ) );
		$this->assertSame( 'nah', (string) $this->stringable( 'ééé hannah' )->after( 'han' ) );
		$this->assertSame( 'hannah', (string) $this->stringable( 'hannah' )->after( 'xxxx' ) );
		$this->assertSame( 'hannah', (string) $this->stringable( 'hannah' )->after( '' ) );
		$this->assertSame( 'nah', (string) $this->stringable( 'han0nah' )->after( '0' ) );
	}

	public function testAfterLast(): void {
		$this->assertSame( 'tte', (string) $this->stringable( 'yvette' )->after_last( 'yve' ) );
		$this->assertSame( 'e', (string) $this->stringable( 'yvette' )->after_last( 't' ) );
		$this->assertSame( 'e', (string) $this->stringable( 'ééé yvette' )->after_last( 't' ) );
		$this->assertSame( '', (string) $this->stringable( 'yvette' )->after_last( 'tte' ) );
		$this->assertSame( 'yvette', (string) $this->stringable( 'yvette' )->after_last( 'xxxx' ) );
		$this->assertSame( 'yvette', (string) $this->stringable( 'yvette' )->after_last( '' ) );
		$this->assertSame( 'te', (string) $this->stringable( 'yv0et0te' )->after_last( '0' ) );
		$this->assertSame( 'foo', (string) $this->stringable( '----foo' )->after_last( '---' ) );
	}

	public function testContains(): void {
		$this->assertTrue( $this->stringable( 'taylor' )->contains( 'ylo' ) );
		$this->assertTrue( $this->stringable( 'taylor' )->contains( 'taylor' ) );
		$this->assertTrue( $this->stringable( 'taylor' )->contains( [ 'ylo' ] ) );
		$this->assertTrue( $this->stringable( 'taylor' )->contains( [ 'xxx', 'ylo' ] ) );
		$this->assertTrue( $this->stringable( 'taylor' )->contains( collect( [ 'xxx', 'ylo' ] ) ) );
		$this->assertTrue( $this->stringable( 'taylor' )->contains( [ 'LOR' ], true ) );
		$this->assertFalse( $this->stringable( 'taylor' )->contains( 'xxx' ) );
		$this->assertFalse( $this->stringable( 'taylor' )->contains( [ 'xxx' ] ) );
		$this->assertFalse( $this->stringable( 'taylor' )->contains( '' ) );
	}

	public function testContainsAll(): void {
		$this->assertTrue( $this->stringable( 'taylor otwell' )->contains_all( [ 'taylor', 'otwell' ] ) );
		$this->assertTrue( $this->stringable( 'taylor otwell' )->contains_all( [ 'TAYLOR', 'OTWELL' ], true ) );
		$this->assertTrue( $this->stringable( 'taylor otwell' )->contains_all( collect( [ 'taylor', 'otwell' ] ) ) );
		$this->assertTrue( $this->stringable( 'taylor otwell' )->contains_all( [ 'taylor' ] ) );
		$this->assertFalse( $this->stringable( 'taylor otwell' )->contains_all( [ 'taylor', 'xxx' ] ) );
	}

	public function testParseCallback(): void {
		$this->assertSame( [ 'Class', 'method' ], $this->stringable( 'Class@method' )->parse_callback( 'foo' ) );
		$this->assertSame( [ 'Class', 'foo' ], $this->stringable( 'Class' )->parse_callback( 'foo' ) );
		$this->assertSame( [ 'Class', null ], $this->stringable( 'Class' )->parse_callback() );
	}

	public function testSlug(): void {
		$this->assertSame( 'hello-world', (string) $this->stringable( 'hello world' )->slug() );
		$this->assertSame( 'hello-world', (string) $this->stringable( 'hello-world' )->slug() );
		$this->assertSame( 'hello-world', (string) $this->stringable( 'hello_world' )->slug() );
		$this->assertSame( 'hello_world', (string) $this->stringable( 'hello_world' )->slug( '_' ) );
		$this->assertSame( 'user-at-host', (string) $this->stringable( 'user@host' )->slug() );
		$this->assertSame( 'سلام-دنیا', (string) $this->stringable( 'سلام دنیا' )->slug( '-', null ) );
		$this->assertSame( 'sometext', (string) $this->stringable( 'some text' )->slug( '' ) );
		$this->assertSame( '', (string) $this->stringable( '' )->slug( '' ) );
		$this->assertSame( '', (string) $this->stringable( '' )->slug() );
	}

	public function testSquish(): void {
		$this->assertSame( 'words with spaces', (string) $this->stringable( ' words  with   spaces ' )->squish() );
		$this->assertSame( 'words with spaces', (string) $this->stringable( "words\t\twith\n\nspaces" )->squish() );
		$this->assertSame(
			'words with spaces',
			(string) $this->stringable(
				'
			words
			with
			spaces
		'
			)->squish()
		);
		$this->assertSame( 'laravel php framework', (string) $this->stringable( '   laravel   php   framework   ' )->squish() );
		$this->assertSame( '123', (string) $this->stringable( '   123    ' )->squish() );
		$this->assertSame( 'だ', (string) $this->stringable( 'だ' )->squish() );
		$this->assertSame( 'ム', (string) $this->stringable( 'ム' )->squish() );
		$this->assertSame( 'だ', (string) $this->stringable( '   だ    ' )->squish() );
		$this->assertSame( 'ム', (string) $this->stringable( '   ム    ' )->squish() );
		$this->assertSame( 'ム', (string) $this->stringable( '﻿   ム ﻿﻿   ﻿' )->squish() );
	}

	public function testStart(): void {
		$this->assertSame( '/test/string', (string) $this->stringable( 'test/string' )->start( '/' ) );
		$this->assertSame( '/test/string', (string) $this->stringable( '/test/string' )->start( '/' ) );
		$this->assertSame( '/test/string', (string) $this->stringable( '//test/string' )->start( '/' ) );
	}

	public function testFinish(): void {
		$this->assertSame( 'abbc', (string) $this->stringable( 'ab' )->finish( 'bc' ) );
		$this->assertSame( 'abbc', (string) $this->stringable( 'abbcbc' )->finish( 'bc' ) );
		$this->assertSame( 'abcbbc', (string) $this->stringable( 'abcbbcbc' )->finish( 'bc' ) );
	}

	public function testIs(): void {
		$this->assertTrue( $this->stringable( '/' )->is( '/' ) );
		$this->assertFalse( $this->stringable( '/' )->is( ' /' ) );
		$this->assertFalse( $this->stringable( '/a' )->is( '/' ) );
		$this->assertTrue( $this->stringable( 'foo/bar/baz' )->is( 'foo/*' ) );

		$this->assertTrue( $this->stringable( 'App\Class@method' )->is( '*@*' ) );
		$this->assertTrue( $this->stringable( 'app\Class@' )->is( '*@*' ) );
		$this->assertTrue( $this->stringable( '@method' )->is( '*@*' ) );

		// is case sensitive
		$this->assertFalse( $this->stringable( 'foo/bar/baz' )->is( '*BAZ*' ) );
		$this->assertFalse( $this->stringable( 'foo/bar/baz' )->is( '*FOO*' ) );
		$this->assertFalse( $this->stringable( 'a' )->is( 'A' ) );

		// Accepts array of patterns
		$this->assertTrue( $this->stringable( 'a/' )->is( [ 'a*', 'b*' ] ) );
		$this->assertTrue( $this->stringable( 'b/' )->is( [ 'a*', 'b*' ] ) );
		$this->assertFalse( $this->stringable( 'f/' )->is( [ 'a*', 'b*' ] ) );

		// numeric values and patterns
		$this->assertFalse( $this->stringable( 123 )->is( [ 'a*', 'b*' ] ) );
		$this->assertTrue( $this->stringable( 11211 )->is( [ '*2*', 'b*' ] ) );

		$this->assertTrue( $this->stringable( 'blah/baz/foo' )->is( '*/foo' ) );

		$valueObject   = new StringableObjectStub( 'foo/bar/baz' );
		$patternObject = new StringableObjectStub( 'foo/*' );

		$this->assertTrue( $this->stringable( $valueObject )->is( 'foo/bar/baz' ) );
		$this->assertTrue( $this->stringable( $valueObject )->is( $patternObject ) );

		// empty patterns
		$this->assertFalse( $this->stringable( 'test' )->is( [] ) );
	}

	public function testKebab(): void {
		$this->assertSame( 'laravel-php-framework', (string) $this->stringable( 'LaravelPhpFramework' )->kebab() );
	}

	public function testLower(): void {
		$this->assertSame( 'foo bar baz', (string) $this->stringable( 'FOO BAR BAZ' )->lower() );
		$this->assertSame( 'foo bar baz', (string) $this->stringable( 'fOo Bar bAz' )->lower() );
	}

	public function testUpper(): void {
		$this->assertSame( 'FOO BAR BAZ', (string) $this->stringable( 'foo bar baz' )->upper() );
		$this->assertSame( 'FOO BAR BAZ', (string) $this->stringable( 'foO bAr BaZ' )->upper() );
	}

	public function testLimit(): void {
		$this->assertSame(
			'Laravel is...',
			(string) $this->stringable( 'Laravel is a free, open source PHP web application framework.' )->limit( 10 )
		);
		$this->assertSame( '这是一...', (string) $this->stringable( '这是一段中文' )->limit( 6 ) );

		$string = 'The PHP framework for web artisans.';
		$this->assertSame( 'The PHP...', (string) $this->stringable( $string )->limit( 7 ) );
		$this->assertSame( 'The PHP', (string) $this->stringable( $string )->limit( 7, '' ) );
		$this->assertSame( 'The PHP framework for web artisans.', (string) $this->stringable( $string )->limit( 100 ) );

		$nonAsciiString = '这是一段中文';
		$this->assertSame( '这是一...', (string) $this->stringable( $nonAsciiString )->limit( 6 ) );
		$this->assertSame( '这是一', (string) $this->stringable( $nonAsciiString )->limit( 6, '' ) );
	}

	public function testLength(): void {
		$this->assertSame( 11, $this->stringable( 'foo bar baz' )->length() );
		$this->assertSame( 11, $this->stringable( 'foo bar baz' )->length( 'UTF-8' ) );
	}

	public function testReplace(): void {
		$this->assertSame( 'foo/foo/foo', (string) $this->stringable( '?/?/?' )->replace( '?', 'foo' ) );
		$this->assertSame( 'foo/foo/foo', (string) $this->stringable( 'x/x/x' )->replace( 'X', 'foo', false ) );
		$this->assertSame( 'bar/bar', (string) $this->stringable( '?/?' )->replace( '?', 'bar' ) );
		$this->assertSame( '?/?/?', (string) $this->stringable( '? ? ?' )->replace( ' ', '/' ) );
		$this->assertSame( 'foo/bar/baz/bam', (string) $this->stringable( '?1/?2/?3/?4' )->replace( [ '?1', '?2', '?3', '?4' ], [ 'foo', 'bar', 'baz', 'bam' ] ) );
		$this->assertSame( '?1/?2/?3/?4', (string) $this->stringable( 'foo/bar/baz/bam' )->replace( [ 'Foo', 'BaR', 'BAZ', 'bAm' ], [ '?1', '?2', '?3', '?4' ], false ) );
		$this->assertSame( 'foo/bar/baz/bam', (string) $this->stringable( '?1/?2/?3/?4' )->replace( collect( [ '?1', '?2', '?3', '?4' ] ), collect( [ 'foo', 'bar', 'baz', 'bam' ] ) ) );
	}

	public function testReplaceArray(): void {
		$this->assertSame( 'foo/bar/baz', (string) $this->stringable( '?/?/?' )->replace_array( '?', [ 'foo', 'bar', 'baz' ] ) );
		$this->assertSame( 'foo/bar/baz/?', (string) $this->stringable( '?/?/?/?' )->replace_array( '?', [ 'foo', 'bar', 'baz' ] ) );
		$this->assertSame( 'foo/bar', (string) $this->stringable( '?/?' )->replace_array( '?', [ 'foo', 'bar', 'baz' ] ) );
		$this->assertSame( '?/?/?', (string) $this->stringable( '?/?/?' )->replace_array( 'x', [ 'foo', 'bar', 'baz' ] ) );
		$this->assertSame( 'foo?/bar/baz', (string) $this->stringable( '?/?/?' )->replace_array( '?', [ 'foo?', 'bar', 'baz' ] ) );
		$this->assertSame(
			'foo/bar',
			(string) $this->stringable( '?/?' )->replace_array(
				'?',
				[
					1 => 'foo',
					2 => 'bar',
				]
			)
		);
		$this->assertSame(
			'foo/bar',
			(string) $this->stringable( '?/?' )->replace_array(
				'?',
				[
					'x' => 'foo',
					'y' => 'bar',
				]
			)
		);
		$this->assertSame(
			'foo/bar',
			(string) $this->stringable( '?/?' )->replace_array(
				'?',
				collect(
					[
						'x' => 'foo',
						'y' => 'bar',
					]
				)
			)
		);
	}

	public function testReplaceFirst(): void {
		$this->assertSame( 'fooqux foobar', (string) $this->stringable( 'foobar foobar' )->replace_first( 'bar', 'qux' ) );
		$this->assertSame( 'foo/qux? foo/bar?', (string) $this->stringable( 'foo/bar? foo/bar?' )->replace_first( 'bar?', 'qux?' ) );
		$this->assertSame( 'foo foobar', (string) $this->stringable( 'foobar foobar' )->replace_first( 'bar', '' ) );
		$this->assertSame( 'foobar foobar', (string) $this->stringable( 'foobar foobar' )->replace_first( 'xxx', 'yyy' ) );
		$this->assertSame( 'foobar foobar', (string) $this->stringable( 'foobar foobar' )->replace_first( '', 'yyy' ) );
		// Test for multibyte string support
		$this->assertSame( 'Jxxxnköping Malmö', (string) $this->stringable( 'Jönköping Malmö' )->replace_first( 'ö', 'xxx' ) );
		$this->assertSame( 'Jönköping Malmö', (string) $this->stringable( 'Jönköping Malmö' )->replace_first( '', 'yyy' ) );
	}

	public function testReplaceLast(): void {
		$this->assertSame( 'foobar fooqux', (string) $this->stringable( 'foobar foobar' )->replace_last( 'bar', 'qux' ) );
		$this->assertSame( 'foo/bar? foo/qux?', (string) $this->stringable( 'foo/bar? foo/bar?' )->replace_last( 'bar?', 'qux?' ) );
		$this->assertSame( 'foobar foo', (string) $this->stringable( 'foobar foobar' )->replace_last( 'bar', '' ) );
		$this->assertSame( 'foobar foobar', (string) $this->stringable( 'foobar foobar' )->replace_last( 'xxx', 'yyy' ) );
		$this->assertSame( 'foobar foobar', (string) $this->stringable( 'foobar foobar' )->replace_last( '', 'yyy' ) );
		// Test for multibyte string support
		$this->assertSame( 'Malmö Jönkxxxping', (string) $this->stringable( 'Malmö Jönköping' )->replace_last( 'ö', 'xxx' ) );
		$this->assertSame( 'Malmö Jönköping', (string) $this->stringable( 'Malmö Jönköping' )->replace_last( '', 'yyy' ) );
	}

	public function testRemove(): void {
		$this->assertSame( 'Fbar', (string) $this->stringable( 'Foobar' )->remove( 'o' ) );
		$this->assertSame( 'Foo', (string) $this->stringable( 'Foobar' )->remove( 'bar' ) );
		$this->assertSame( 'oobar', (string) $this->stringable( 'Foobar' )->remove( 'F' ) );
		$this->assertSame( 'Foobar', (string) $this->stringable( 'Foobar' )->remove( 'f' ) );
		$this->assertSame( 'oobar', (string) $this->stringable( 'Foobar' )->remove( 'f', false ) );

		$this->assertSame( 'Fbr', (string) $this->stringable( 'Foobar' )->remove( [ 'o', 'a' ] ) );
		$this->assertSame( 'Fbr', (string) $this->stringable( 'Foobar' )->remove( collect( [ 'o', 'a' ] ) ) );
		$this->assertSame( 'Fooar', (string) $this->stringable( 'Foobar' )->remove( [ 'f', 'b' ] ) );
		$this->assertSame( 'ooar', (string) $this->stringable( 'Foobar' )->remove( [ 'f', 'b' ], false ) );
		$this->assertSame( 'Foobar', (string) $this->stringable( 'Foo|bar' )->remove( [ 'f', '|' ] ) );
	}

	public function testReverse(): void {
		$this->assertSame( 'FooBar', (string) $this->stringable( 'raBooF' )->reverse() );
		$this->assertSame( 'Teniszütő', (string) $this->stringable( 'őtüzsineT' )->reverse() );
		$this->assertSame( '❤MultiByte☆', (string) $this->stringable( '☆etyBitluM❤' )->reverse() );
	}

	public function testSnake(): void {
		$this->assertSame( 'laravel_p_h_p_framework', (string) $this->stringable( 'LaravelPHPFramework' )->snake() );
		$this->assertSame( 'laravel_php_framework', (string) $this->stringable( 'LaravelPhpFramework' )->snake() );
		$this->assertSame( 'laravel php framework', (string) $this->stringable( 'LaravelPhpFramework' )->snake( ' ' ) );
		$this->assertSame( 'laravel_php_framework', (string) $this->stringable( 'Laravel Php Framework' )->snake() );
		$this->assertSame( 'laravel_php_framework', (string) $this->stringable( 'Laravel	Php	  Framework   ' )->snake() );
		// ensure cache keys don't overlap
		$this->assertSame( 'laravel__php__framework', (string) $this->stringable( 'LaravelPhpFramework' )->snake( '__' ) );
		$this->assertSame( 'laravel_php_framework_', (string) $this->stringable( 'LaravelPhpFramework_' )->snake( '_' ) );
		$this->assertSame( 'laravel_php_framework', (string) $this->stringable( 'laravel php Framework' )->snake() );
		$this->assertSame( 'laravel_php_frame_work', (string) $this->stringable( 'laravel php FrameWork' )->snake() );
		// prevent breaking changes
		$this->assertSame( 'foo-bar', (string) $this->stringable( 'foo-bar' )->snake() );
		$this->assertSame( 'foo-_bar', (string) $this->stringable( 'Foo-Bar' )->snake() );
		$this->assertSame( 'foo__bar', (string) $this->stringable( 'Foo_Bar' )->snake() );
		$this->assertSame( 'żółtałódka', (string) $this->stringable( 'ŻółtaŁódka' )->snake() );
	}

	public function testStudly(): void {
		$this->assertSame( 'LaravelPHPFramework', (string) $this->stringable( 'laravel_p_h_p_framework' )->studly() );
		$this->assertSame( 'LaravelPhpFramework', (string) $this->stringable( 'laravel_php_framework' )->studly() );
		$this->assertSame( 'LaravelPhPFramework', (string) $this->stringable( 'laravel-phP-framework' )->studly() );
		$this->assertSame( 'LaravelPhpFramework', (string) $this->stringable( 'laravel  -_-  php   -_-   framework   ' )->studly() );

		$this->assertSame( 'FooBar', (string) $this->stringable( 'fooBar' )->studly() );
		$this->assertSame( 'FooBar', (string) $this->stringable( 'foo_bar' )->studly() );
		$this->assertSame( 'FooBar', (string) $this->stringable( 'foo_bar' )->studly() ); // test cache
		$this->assertSame( 'FooBarBaz', (string) $this->stringable( 'foo-barBaz' )->studly() );
		$this->assertSame( 'FooBarBaz', (string) $this->stringable( 'foo-bar_baz' )->studly() );
	}

	public function testCamel(): void {
		$this->assertSame( 'laravelPHPFramework', (string) $this->stringable( 'Laravel_p_h_p_framework' )->camel() );
		$this->assertSame( 'laravelPhpFramework', (string) $this->stringable( 'Laravel_php_framework' )->camel() );
		$this->assertSame( 'laravelPhPFramework', (string) $this->stringable( 'Laravel-phP-framework' )->camel() );
		$this->assertSame( 'laravelPhpFramework', (string) $this->stringable( 'Laravel  -_-  php   -_-   framework   ' )->camel() );

		$this->assertSame( 'fooBar', (string) $this->stringable( 'FooBar' )->camel() );
		$this->assertSame( 'fooBar', (string) $this->stringable( 'foo_bar' )->camel() );
		$this->assertSame( 'fooBar', (string) $this->stringable( 'foo_bar' )->camel() ); // test cache
		$this->assertSame( 'fooBarBaz', (string) $this->stringable( 'Foo-barBaz' )->camel() );
		$this->assertSame( 'fooBarBaz', (string) $this->stringable( 'foo-bar_baz' )->camel() );
	}

	public function testCharAt(): void {
		$this->assertSame( 'р', $this->stringable( 'Привет, мир!' )->char_at( 1 ) );
		$this->assertSame( 'ち', $this->stringable( '「こんにちは世界」' )->char_at( 4 ) );
		$this->assertSame( 'w', $this->stringable( 'Привет, world!' )->char_at( 8 ) );
		$this->assertSame( '界', $this->stringable( '「こんにちは世界」' )->char_at( -2 ) );
		$this->assertFalse( $this->stringable( '「こんにちは世界」' )->char_at( -200 ) );
	}

	public function testSubstr(): void {
		$this->assertSame( 'Ё', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( -1 ) );
		$this->assertSame( 'ЛЁ', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( -2 ) );
		$this->assertSame( 'И', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( -3, 1 ) );
		$this->assertSame( 'ДЖИЛ', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( 2, -1 ) );
		$this->assertSame( '', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( 4, -4 ) );
		$this->assertSame( 'ИЛ', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( -3, -1 ) );
		$this->assertSame( 'ГДЖИЛЁ', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( 1 ) );
		$this->assertSame( 'ГДЖ', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( 1, 3 ) );
		$this->assertSame( 'БГДЖ', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( 0, 4 ) );
		$this->assertSame( 'Ё', (string) $this->stringable( 'БГДЖИЛЁ' )->substr( -1, 1 ) );
		$this->assertSame( '', (string) $this->stringable( 'Б' )->substr( 2 ) );
	}

	public function testSwap(): void {
		$this->assertSame(
			'PHP 8 is fantastic',
			(string) $this->stringable( 'PHP is awesome' )->swap(
				[
					'PHP'     => 'PHP 8',
					'awesome' => 'fantastic',
				]
			)
		);
	}

	public function testSubstrCount(): void {
		$this->assertSame( 3, $this->stringable( 'laravelPHPFramework' )->substr_count( 'a' ) );
		$this->assertSame( 0, $this->stringable( 'laravelPHPFramework' )->substr_count( 'z' ) );
		$this->assertSame( 1, $this->stringable( 'laravelPHPFramework' )->substr_count( 'l', 2 ) );
		$this->assertSame( 0, $this->stringable( 'laravelPHPFramework' )->substr_count( 'z', 2 ) );
		$this->assertSame( 1, $this->stringable( 'laravelPHPFramework' )->substr_count( 'k', -1 ) );
		$this->assertSame( 1, $this->stringable( 'laravelPHPFramework' )->substr_count( 'k', -1 ) );
		$this->assertSame( 1, $this->stringable( 'laravelPHPFramework' )->substr_count( 'a', 1, 2 ) );
		$this->assertSame( 1, $this->stringable( 'laravelPHPFramework' )->substr_count( 'a', 1, 2 ) );
		$this->assertSame( 3, $this->stringable( 'laravelPHPFramework' )->substr_count( 'a', 1, -2 ) );
		$this->assertSame( 1, $this->stringable( 'laravelPHPFramework' )->substr_count( 'a', -10, -3 ) );
	}

	public function testSubstrReplace(): void {
		$this->assertSame( '12:00', (string) $this->stringable( '1200' )->substr_replace( ':', 2, 0 ) );
		$this->assertSame( 'The Laravel Framework', (string) $this->stringable( 'The Framework' )->substr_replace( 'Laravel ', 4, 0 ) );
		$this->assertSame( 'Laravel – The PHP Framework for Web Artisans', (string) $this->stringable( 'Laravel Framework' )->substr_replace( '– The PHP Framework for Web Artisans', 8 ) );
	}

	public function testPadBoth(): void {
		$this->assertSame( '__Alien___', (string) $this->stringable( 'Alien' )->pad_both( 10, '_' ) );
		$this->assertSame( '  Alien   ', (string) $this->stringable( 'Alien' )->pad_both( 10 ) );
		$this->assertSame( '  ❤MultiByte☆   ', (string) $this->stringable( '❤MultiByte☆' )->pad_both( 16 ) );
	}

	public function testPadLeft(): void {
		$this->assertSame( '-=-=-Alien', (string) $this->stringable( 'Alien' )->pad_left( 10, '-=' ) );
		$this->assertSame( '     Alien', (string) $this->stringable( 'Alien' )->pad_left( 10 ) );
		$this->assertSame( '     ❤MultiByte☆', (string) $this->stringable( '❤MultiByte☆' )->pad_left( 16 ) );
	}

	public function testPadRight(): void {
		$this->assertSame( 'Alien-----', (string) $this->stringable( 'Alien' )->pad_right( 10, '-' ) );
		$this->assertSame( 'Alien     ', (string) $this->stringable( 'Alien' )->pad_right( 10 ) );
		$this->assertSame( '❤MultiByte☆     ', (string) $this->stringable( '❤MultiByte☆' )->pad_right( 16 ) );
	}

	public function testExplode() {
		$this->assertInstanceOf( Collection::class, $this->stringable( 'Foo Bar Baz' )->explode( ' ' ) );

		$this->assertSame( '["Foo","Bar","Baz"]', (string) $this->stringable( 'Foo Bar Baz' )->explode( ' ' ) );

		// with limit
		$this->assertSame( '["Foo","Bar Baz"]', (string) $this->stringable( 'Foo Bar Baz' )->explode( ' ', 2 ) );
		$this->assertSame( '["Foo","Bar"]', (string) $this->stringable( 'Foo Bar Baz' )->explode( ' ', -1 ) );
	}

	public function testChunk() {
		$chunks = $this->stringable( 'foobarbaz' )->split( 3 );

		$this->assertInstanceOf( Collection::class, $chunks );
		$this->assertSame( [ 'foo', 'bar', 'baz' ], $chunks->all() );
	}

	public function testJsonSerialize(): void {
		$this->assertSame( '"foo"', json_encode( $this->stringable( 'foo' ) ) );
		$this->assertSame( '"laravel-php-framework"', json_encode( $this->stringable( 'LaravelPhpFramework' )->kebab() ) );
		$this->assertSame( '["laravel-php-framework"]', json_encode( [ $this->stringable( 'LaravelPhpFramework' )->kebab() ] ) );
		$this->assertSame( '{"title":"laravel-php-framework"}', json_encode( [ 'title' => $this->stringable( 'LaravelPhpFramework' )->kebab() ] ) );
	}

	public function testTap(): void {
		$stringable = $this->stringable( 'foobarbaz' );

		$fromTheTap = '';

		$stringable = $stringable->tap(
			function ( Stringable $string ) use ( &$fromTheTap ) {
				$fromTheTap = $string->substr( 0, 3 );
			}
		);

		$this->assertSame( 'foo', (string) $fromTheTap );
		$this->assertSame( 'foobarbaz', (string) $stringable );
	}

	public function testPipe(): void {
		$callback = function ( $stringable ) {
			return 'bar';
		};

		$this->assertSame( 'bar', (string) $this->stringable( 'foo' )->pipe( $callback ) );
	}

	public function testMarkdown(): void {
		$this->assertEquals( "<p><em>hello world</em></p>\n", $this->stringable( '*hello world*' )->markdown() );
		$this->assertEquals( "<h1>hello world</h1>\n", $this->stringable( '# hello world' )->markdown() );
	}

	public function testInlineMarkdown(): void {
		$this->assertEquals( "<em>hello world</em>\n", $this->stringable( '*hello world*' )->inline_markdown() );
		$this->assertEquals( "<a href=\"https://laravel.com\"><strong>Laravel</strong></a>\n", $this->stringable( '[**Laravel**](https://laravel.com)' )->inline_markdown() );
	}

	public function testMask(): void {
		$this->assertSame( 'tay*************', (string) $this->stringable( 'taylor@email.com' )->mask( '*', 3 ) );
		$this->assertSame( '******@email.com', (string) $this->stringable( 'taylor@email.com' )->mask( '*', 0, 6 ) );
		$this->assertSame( 'tay*************', (string) $this->stringable( 'taylor@email.com' )->mask( '*', -13 ) );
		$this->assertSame( 'tay***@email.com', (string) $this->stringable( 'taylor@email.com' )->mask( '*', -13, 3 ) );

		$this->assertSame( '****************', (string) $this->stringable( 'taylor@email.com' )->mask( '*', -17 ) );
		$this->assertSame( '*****r@email.com', (string) $this->stringable( 'taylor@email.com' )->mask( '*', -99, 5 ) );

		$this->assertSame( 'taylor@email.com', (string) $this->stringable( 'taylor@email.com' )->mask( '*', 16 ) );
		$this->assertSame( 'taylor@email.com', (string) $this->stringable( 'taylor@email.com' )->mask( '*', 16, 99 ) );

		$this->assertSame( 'taylor@email.com', (string) $this->stringable( 'taylor@email.com' )->mask( '', 3 ) );

		$this->assertSame( 'taysssssssssssss', (string) $this->stringable( 'taylor@email.com' )->mask( 'something', 3 ) );

		$this->assertSame( '这是一***', (string) $this->stringable( '这是一段中文' )->mask( '*', 3 ) );
		$this->assertSame( '**一段中文', (string) $this->stringable( '这是一段中文' )->mask( '*', 0, 2 ) );
	}

	public function testRepeat(): void {
		$this->assertSame( 'aaaaa', (string) $this->stringable( 'a' )->repeat( 5 ) );
		$this->assertSame( '', (string) $this->stringable( '' )->repeat( 5 ) );
	}

	public function testWordCount(): void {
		$this->assertSame( 2, $this->stringable( 'Hello, world!' )->word_count() );
		$this->assertSame( 10, $this->stringable( 'Hi, this is my first contribution to the Laravel framework.' )->word_count() );
	}

	public function testWrap(): void {
		$this->assertEquals( 'This is me!', $this->stringable( 'is' )->wrap( 'This ', ' me!' ) );
		$this->assertEquals( '"value"', $this->stringable( 'value' )->wrap( '"' ) );
	}

	public function testStripTags(): void {
		$this->assertSame( 'beforeafter', (string) $this->stringable( 'before<br>after' )->strip_tags() );
		$this->assertSame( 'before<br>after', (string) $this->stringable( 'before<br>after' )->strip_tags( '<br>' ) );
		$this->assertSame( 'before<br>after', (string) $this->stringable( '<strong>before</strong><br>after' )->strip_tags( '<br>' ) );
		$this->assertSame( '<strong>before</strong><br>after', (string) $this->stringable( '<strong>before</strong><br>after' )->strip_tags( '<br><strong>' ) );
	}

	public function testScan(): void {
		$this->assertSame( [ 123456 ], $this->stringable( 'SN/123456' )->scan( 'SN/%d' )->to_array() );
		$this->assertSame( [ 'Otwell', 'Taylor' ], $this->stringable( 'Otwell, Taylor' )->scan( '%[^,],%s' )->to_array() );
		$this->assertSame( [ 'filename', 'jpg' ], $this->stringable( 'filename.jpg' )->scan( '%[^.].%s' )->to_array() );
	}

	public function testGet(): void {
		$this->assertSame( 'foo', $this->stringable( 'foo' )->value() );
		$this->assertSame( 'foo', $this->stringable( 'foo' )->toString() );
	}

	public function testExactly(): void {
		$this->assertTrue( $this->stringable( 'foo' )->exactly( $this->stringable( 'foo' ) ) );
		$this->assertTrue( $this->stringable( 'foo' )->exactly( 'foo' ) );

		$this->assertFalse( $this->stringable( 'Foo' )->exactly( $this->stringable( 'foo' ) ) );
		$this->assertFalse( $this->stringable( 'Foo' )->exactly( 'foo' ) );
		$this->assertFalse( $this->stringable( '[]' )->exactly( [] ) );
		$this->assertFalse( $this->stringable( '0' )->exactly( 0 ) );
	}

	public function testToInteger(): void {
		$this->assertSame( 123, $this->stringable( '123' )->to_integer() );
		$this->assertSame( 456, $this->stringable( 456 )->to_integer() );
		$this->assertSame( 78, $this->stringable( '078' )->to_integer() );
		$this->assertSame( 901, $this->stringable( ' 901' )->to_integer() );
		$this->assertSame( 0, $this->stringable( 'nan' )->to_integer() );
		$this->assertSame( 1, $this->stringable( '1ab' )->to_integer() );
		$this->assertSame( 2, $this->stringable( '2_000' )->to_integer() );
	}

	public function testToFloat(): void {
		$this->assertSame( 1.23, $this->stringable( '1.23' )->to_float() );
		$this->assertSame( 45.6, $this->stringable( 45.6 )->to_float() );
		$this->assertSame( .6, $this->stringable( '.6' )->to_float() );
		$this->assertSame( 0.78, $this->stringable( '0.78' )->to_float() );
		$this->assertSame( 90.1, $this->stringable( ' 90.1' )->to_float() );
		$this->assertSame( 0.0, $this->stringable( 'nan' )->to_float() );
		$this->assertSame( 1.0, $this->stringable( '1.ab' )->to_float() );
		$this->assertSame( 1e3, $this->stringable( '1e3' )->to_float() );
	}

	public function testBooleanMethod(): void {
		$this->assertTrue( $this->stringable( true )->to_boolean() );
		$this->assertTrue( $this->stringable( 'true' )->to_boolean() );
		$this->assertFalse( $this->stringable( 'false' )->to_boolean() );
		$this->assertTrue( $this->stringable( '1' )->to_boolean() );
		$this->assertFalse( $this->stringable( '0' )->to_boolean() );
		$this->assertTrue( $this->stringable( 'on' )->to_boolean() );
		$this->assertFalse( $this->stringable( 'off' )->to_boolean() );
		$this->assertTrue( $this->stringable( 'yes' )->to_boolean() );
		$this->assertFalse( $this->stringable( 'no' )->to_boolean() );
	}

	public function testToDate(): void {
		$current = Carbon::create( 2020, 1, 1, 16, 30, 25 );

		$this->assertEquals( $current, $this->stringable( '20-01-01 16:30:25' )->to_date() );
		$this->assertEquals( $current, $this->stringable( '1577896225' )->to_date( 'U' ) );
		$this->assertEquals( $current, $this->stringable( '20-01-01 13:30:25' )->to_date( null, 'America/Santiago' ) );

		$this->assertTrue( $this->stringable( '2020-01-01' )->to_date()->isSameDay( $current ) );
		$this->assertTrue( $this->stringable( '16:30:25' )->to_date()->isSameSecond( '16:30:25' ) );
	}

	public function testToDateThrowsException(): void {
		$this->expectException( \Carbon\Exceptions\InvalidFormatException::class );

		$this->stringable( 'not a date' )->to_date();
	}

	public function testArrayAccess(): void {
		$str = $this->stringable( 'my string' );
		$this->assertSame( 'm', $str[0] );
		$this->assertSame( 't', $str[4] );
		$this->assertTrue( isset( $str[2] ) );
		$this->assertFalse( isset( $str[10] ) );
	}
}
