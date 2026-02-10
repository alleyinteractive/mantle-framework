<?php

namespace Testing;

use Mantle\Testing\Exceptions\UnexpectedDeprecatedNoticeException;
use Mantle\Testing\Exceptions\UnexpectedIncorrectUsageException;
use PHPUnit\Framework\TestCase;

class Psr4ClassesTest extends TestCase {
	public function test_ensure_classes_are_loaded(): void {
		$this->assertTrue( class_exists( UnexpectedDeprecatedNoticeException::class ) );
		$this->assertTrue( class_exists( UnexpectedIncorrectUsageException::class ) );
	}
}
