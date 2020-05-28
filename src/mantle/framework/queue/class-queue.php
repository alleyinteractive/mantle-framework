<?php
namespace Mantle\Framework\Queue;

class Queue {
	public const CRON_HOOK = 'mantle_queue';
	public const QUEUE_OPTION = '_mantle_queue';

	public function all() {
		return (array) \get_option( static::QUEUE_OPTION, [] );
	}
}
