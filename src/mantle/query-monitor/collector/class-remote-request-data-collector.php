<?php
namespace Mantle\Query_Monitor\Collector;

use QM_Data;

/**
 * Data collector for remote requests.
 *
 * @phpstan-import-type CollectedHttpEntry from Remote_Request_Collector
 */
class Remote_Request_Data_Collector extends QM_Data {
	/**
	 * Collected remote requests.
	 *
	 * @var array<int, CollectedHttpEntry>
	 */
	public array $requests = [];
}
