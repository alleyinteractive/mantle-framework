<?php
/**
 * Action enum file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Expectation;

/**
 * Actions for the expectation container to assert against.
 */
enum Action: string {
		case ADDED = 'added';
		case APPLIED = 'applied';
}
