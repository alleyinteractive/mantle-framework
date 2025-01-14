<?php

namespace AI_Logger {
	interface Handler_Interface {
		/**
		 * Clear the stored log, not applicable.
		 */
		public function clear();
	}
}

namespace AI_Logger\Handler {

	use Monolog\Handler\AbstractProcessingHandler;
	use Monolog\Logger;

	class Post_Handler extends AbstractProcessingHandler implements \AI_Logger\Handler_Interface {

		public function __construct( $level = Logger::DEBUG, bool $bubble = true ) {}

		public function clear() {}

		protected function write( array $record ): void {}
	}
}
