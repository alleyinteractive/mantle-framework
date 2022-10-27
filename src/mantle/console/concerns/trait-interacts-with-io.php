<?php
namespace Mantle\Console\Concerns;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait Interacts_With_IO {
	protected InputInterface $input;
	protected OutputInterface $output;

	public function set_input( InputInterface $input ) {
		$this->input = $input;
	}

	public function get_input(): InputInterface {
		return $this->input;
	}

	public function set_output( OutputInterface $output ) {
		$this->output = $output;
	}

	public function get_output(): OutputInterface {
		return $this->output;
	}
}
