<?php

namespace Mantle\Tests\Support\Concerns;

#[\Attribute( \Attribute::TARGET_METHOD )]
class TestValidatorAttribute implements \Mantle\Types\Validator {
	public function __construct(
		private readonly bool $should_pass = true
	) {}

	public function validate(): bool {
		return $this->should_pass;
	}
}
