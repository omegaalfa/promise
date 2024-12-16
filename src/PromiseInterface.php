<?php

declare(strict_types = 1);

namespace Omegaalfa\Promise;

interface PromiseInterface
{
	/**
	 * @param  callable|null  $onFulfilled
	 * @param  callable|null  $onRejected
	 *
	 * @return $this
	 */
	public function then(callable $onFulfilled = null, callable $onRejected = null): self;

	/**
	 * @param  callable  $onRejected
	 *
	 * @return $this
	 */
	public function catch(callable $onRejected): self;

	/**
	 * @param  callable  $onFinally
	 *
	 * @return $this
	 */
	public function finally(callable $onFinally): self;
}