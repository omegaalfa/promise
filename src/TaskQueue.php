<?php

declare(strict_types = 1);

namespace Omegaalfa\Promise;

use Fiber;


class TaskQueue
{
	/**
	 * @var array
	 */
	private array $tasks = [];

	/**
	 * @param  Fiber  $fiber
	 *
	 * @return void
	 */
	public function enqueue(Fiber $fiber): void
	{
		$this->tasks[] = $fiber;
		$this->run();
	}

	/**
	 * @return void
	 */
	public function run(): void
	{
		while(!empty($this->tasks)) {
			$fiber = array_shift($this->tasks);

			if(!$fiber->isStarted()) {
				$fiber->start();
			} elseif(!$fiber->isTerminated()) {
				$fiber->resume();
			}
		}
	}
}