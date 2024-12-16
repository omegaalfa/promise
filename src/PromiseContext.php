<?php

declare(strict_types = 1);

namespace Omegaalfa\Promise;

use Fiber;
use Throwable;

class PromiseContext
{
	/**
	 * @var Fiber|null
	 */
	private ?Fiber $fiber = null;

	/**
	 * @var mixed|null
	 */
	private mixed $result = null;

	/**
	 * @var Throwable|null
	 */
	private ?Throwable $error = null;

	/**
	 * @var PromiseState
	 */
	private PromiseState $state = PromiseState::Pending;

	/**
	 * @var array
	 */
	private array $thenCallbacks = [];

	/**
	 * @var array
	 */
	private array $catchCallbacks = [];

	/**
	 * @var array
	 */
	private array $finallyCallbacks = [];

	/**
	 * @return Fiber|null
	 */
	public function getFiber(): ?Fiber
	{
		return $this->fiber;
	}

	/**
	 * @param  Fiber  $fiber
	 *
	 * @return void
	 */
	public function setFiber(Fiber $fiber): void
	{
		$this->fiber = $fiber;
	}

	/**
	 * @return PromiseState
	 */
	public function getState(): PromiseState
	{
		return $this->state;
	}

	/**
	 * @param  PromiseState  $state
	 *
	 * @return void
	 */
	public function setState(PromiseState $state): void
	{
		$this->state = $state;
	}

	/**
	 * @return mixed
	 */
	public function getResult(): mixed
	{
		return $this->result;
	}

	/**
	 * @param  mixed  $result
	 *
	 * @return void
	 */
	public function setResult(mixed $result): void
	{
		$this->result = $result;
	}

	/**
	 * @return Throwable|null
	 */
	public function getError(): ?Throwable
	{
		return $this->error;
	}

	/**
	 * @param  Throwable  $error
	 *
	 * @return void
	 */
	public function setError(Throwable $error): void
	{
		$this->error = $error;
	}

	/**
	 * @param  callable  $callback
	 *
	 * @return void
	 */
	public function addThenCallback(callable $callback): void
	{
		$this->thenCallbacks[] = $callback;
	}

	/**
	 * @param  callable  $callback
	 *
	 * @return void
	 */
	public function addCatchCallback(callable $callback): void
	{
		$this->catchCallbacks[] = $callback;
	}

	/**
	 * @param  callable  $callback
	 *
	 * @return void
	 */
	public function addFinallyCallback(callable $callback): void
	{
		$this->finallyCallbacks[] = $callback;
	}

	/**
	 * @return void
	 */
	public function executeThenCallbacks(): void
	{
		foreach($this->thenCallbacks as $callback) {
			$callback($this->result);
		}
	}

	/**
	 * @return void
	 */
	public function executeCatchCallbacks(): void
	{
		foreach($this->catchCallbacks as $callback) {
			$callback($this->error);
		}
	}

	/**
	 * @return void
	 */
	public function executeFinallyCallbacks(): void
	{
		foreach($this->finallyCallbacks as $callback) {
			$callback();
		}
	}
}