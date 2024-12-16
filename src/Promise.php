<?php

declare(strict_types = 1);

namespace Omegaalfa\Promise;

use Closure;
use Fiber;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WeakMap;

class Promise implements PromiseInterface
{
	/**
	 * @var WeakMap
	 */
	private static WeakMap $contextMap;

	/**
	 * @var TaskQueue|null
	 */
	private static ?TaskQueue $taskQueue = null;

	/**
	 * @param  Closure  $executor
	 */
	public function __construct(private readonly Closure $executor)
	{
		if(!isset(self::$contextMap)) {
			self::$contextMap = new WeakMap();
		}

		if(!self::$taskQueue) {
			self::$taskQueue = new TaskQueue();
		}

		self::$contextMap[$this] = new PromiseContext();

		$this->initialize();
	}


	/**
	 * @return void
	 */
	private function initialize(): void
	{
		$context = self::$contextMap[$this];
		if($context instanceof PromiseContext) {
			$resolve = function(mixed $value) use ($context): void {
				if($context->getState() !== PromiseState::Pending) {
					return;
				}

				if($value instanceof Promise) {
					$value->then(
						fn($result) => $this->settle(PromiseState::Fulfilled, $result),
						fn($error) => $this->settle(PromiseState::Rejected, $error)
					);
					return;
				}

				$this->settle(PromiseState::Fulfilled, $value);
			};

			$reject = function(Throwable $reason) use ($context): void {
				if($context->getState() !== PromiseState::Pending) {
					return;
				}

				$this->settle(PromiseState::Rejected, $reason);
			};

			try {
				$fiber = new Fiber(function() use ($resolve, $reject): void {
					try {
						($this->executor)($resolve, $reject);
					} catch(Throwable $e) {
						$reject($e);
					}
				});

				$context->setFiber($fiber);
				self::$taskQueue->enqueue($fiber);
			} catch(Throwable $e) {
				$reject($e);
			}
		}
	}

	/**
	 * @param  PromiseState  $state
	 * @param  mixed         $value
	 *
	 * @return void
	 */
	private function settle(PromiseState $state, mixed $value): void
	{
		$context = self::$contextMap[$this];
		if($context instanceof PromiseContext) {
			if($context->getState() !== PromiseState::Pending) {
				return;
			}

			$context->setState($state);

			if($state === PromiseState::Fulfilled) {
				$context->setResult($value);
				$context->executeThenCallbacks();
			} else {
				$context->setError($value);
				$context->executeCatchCallbacks();
			}

			$context->executeFinallyCallbacks();
		}
	}

	/**
	 * @return Fiber|null
	 */
	public function getFiber(): ?Fiber
	{
		$context = self::$contextMap[$this];
		if($context instanceof PromiseContext) {
			return $context->getFiber();
		}
		return null;
	}

	/**
	 * @param  callable  $callback
	 *
	 * @return void
	 */
	public function addFinallyCallback(callable $callback): void
	{
		$context = self::$contextMap[$this];
		if($context instanceof PromiseContext) {
			$context->addFinallyCallback($callback);
		}
	}

	/**
	 * @param  callable|null  $onFulfilled
	 * @param  callable|null  $onRejected
	 *
	 * @return Promise
	 */
	public function then(callable $onFulfilled = null, callable $onRejected = null): Promise
	{
		return new Promise(function($resolve, $reject) use ($onFulfilled, $onRejected): void {
			$context = self::$contextMap[$this];
			if($context instanceof PromiseContext) {
				$wrappedOnFulfilled = static function(mixed $value) use ($resolve, $reject, $onFulfilled): void {
					if(!$onFulfilled) {
						$resolve($value);
						return;
					}

					try {
						$resolve($onFulfilled($value));
					} catch(Throwable $e) {
						$reject($e);
					}
				};

				$wrappedOnRejected = static function(Throwable $reason) use ($resolve, $reject, $onRejected): void {
					if(!$onRejected) {
						$reject($reason);
						return;
					}

					try {
						$resolve($onRejected($reason));
					} catch(Throwable $e) {
						$reject($e);
					}
				};

				$context->addThenCallback($wrappedOnFulfilled);
				$context->addCatchCallback($wrappedOnRejected);

				if($context->getState() === PromiseState::Fulfilled) {
					$wrappedOnFulfilled($context->getResult());
				} elseif($context->getState() === PromiseState::Rejected) {
					$wrappedOnRejected($context->getError());
				}
			}
		});
	}

	/**
	 * @param  callable  $onRejected
	 *
	 * @return Promise
	 */
	public function catch(callable $onRejected): Promise
	{
		return $this->then(null, $onRejected);
	}

	/**
	 * @param  callable  $onFinally
	 *
	 * @return Promise
	 */
	public function finally(callable $onFinally): Promise
	{
		return $this->then(
			function(mixed $value) use ($onFinally) {
				$onFinally();
				return $value;
			},
			function(Throwable $reason) use ($onFinally) {
				$onFinally();
				throw $reason;
			}
		);
	}

	/**
	 * Resolve quando todas as promessas são resolvidas. Rejeita quando qualquer promessa é rejeitada.
	 *
	 * @param  array  $promises
	 *
	 * @return Promise
	 */
	public static function all(array $promises): Promise
	{
		return new Promise(function($resolve, $reject) use ($promises): void {
			if(empty($promises)) {
				$resolve([]);
				return;
			}

			foreach($promises as $promise) {
				if(!$promise instanceof Promise) {
					throw new InvalidArgumentException('All elements must be instances of Promise');
				}
			}

			$results = [];
			$remaining = count($promises);

			foreach($promises as $index => $promise) {
				$promise->then(
					function(mixed $value) use (&$results, &$remaining, $index, $resolve): void {
						$results[$index] = $value;
						$remaining--;

						if($remaining === 0) {
							ksort($results);
							$resolve($results);
						}
					},
					function(Throwable $reason) use ($reject): void {
						$reject($reason);
					}
				);
			}
		});
	}

	/**
	 * Resolve ou rejeita assim que a primeira promessa for resolvida ou rejeitada.
	 *
	 * @param  array  $promises
	 *
	 * @return Promise
	 */
	public static function race(array $promises): Promise
	{
		return new Promise(function($resolve, $reject) use ($promises): void {
			if(empty($promises)) {
				$reject(new RuntimeException('No promises to race'));
				return;
			}

			foreach($promises as $promise) {
				if(!$promise instanceof Promise) {
					throw new InvalidArgumentException('All elements must be instances of Promise');
				}

				$promise->then($resolve, $reject);
			}
		});
	}

	/**
	 * Resolve assim que qualquer promessa for resolvida. Rejeita apenas se todas as promessas forem rejeitadas
	 *
	 * @param  array  $promises
	 *
	 * @return Promise
	 */
	public static function any(array $promises): Promise
	{
		return new Promise(function($resolve, $reject) use ($promises): void {
			if(empty($promises)) {
				$reject(new RuntimeException('No promises provided'));
				return;
			}

			$errors = [];
			$remaining = count($promises);

			foreach($promises as $index => $promise) {
				if(!$promise instanceof Promise) {
					throw new InvalidArgumentException('All elements must be instances of Promise');
				}

				$promise->then(
					$resolve,
					function(Throwable $reason) use (&$errors, &$remaining, $index, $reject): void {
						$errors[$index] = $reason;
						$remaining--;

						if($remaining === 0) {
							$reject(new AggregateException('All promises were rejected', $errors));
						}
					}
				);
			}
		});
	}
}