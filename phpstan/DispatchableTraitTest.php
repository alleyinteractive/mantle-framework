<?php

use Mantle\Contracts\Queue\Job;

class DispatchableTraitTest implements Job
{
	use Mantle\Queue\Dispatchable;

	public function handle(): void
	{
		// Job handling logic here.
	}
}
