<?php

declare(strict_types=1);

namespace Omegaalfa\Promise;

class Promise implements PromiseInterface
{
    /**
     * @var string
     */
    private string $state = 'pending'; // pending|fulfilled|rejected
    /**
     * @var mixed|null
     */
    private mixed $value = null;
    /**
     * @var mixed|null
     */
    private mixed $reason = null;

    /** @var callable[] */
    private array $fulfilledCallbacks = [];

    /** @var callable[] */
    private array $rejectedCallbacks = [];

    /**
     * @param callable $executor
     */
    public function __construct(callable $executor)
    {
        try {
            $executor(
                $this->resolve(...),
                $this->reject(...)
            );
        } catch (\Throwable $e) {
            $this->reject($e);
        }
    }

    /**
     * @param mixed $reason
     * @return void
     */
    private function reject(mixed $reason): void
    {
        if ($this->state !== 'pending') {
            return;
        }
        $this->state = 'rejected';
        $this->reason = $reason;

        foreach ($this->rejectedCallbacks as $cb) {
            TaskQueue::instance()->defer(fn() => $cb($reason));
        }
        $this->rejectedCallbacks = [];
    }

    /**
     * @param callable $onFinally
     * @return PromiseInterface
     */
    public function finally(callable $onFinally): PromiseInterface
    {
        return new Promise(function ($resolve, $reject) use ($onFinally) {
            $onFulfilled = static function ($value) use ($onFinally, $resolve, $reject) {
                try {
                    $onFinally();
                    $resolve($value);
                } catch (\Throwable $e) {
                    $reject($e);
                }
            };

            $onRejected = static function ($reason) use ($onFinally, $resolve, $reject) {
                try {
                    $onFinally();
                    $reject($reason);
                } catch (\Throwable $e) {
                    $reject($e);
                }
            };

            if ($this->state === 'fulfilled') {
                TaskQueue::instance()->defer(fn() => $onFulfilled($this->value));
            } elseif ($this->state === 'rejected') {
                TaskQueue::instance()->defer(fn() => $onRejected($this->reason));
            } else {
                $this->fulfilledCallbacks[] = $onFulfilled;
                $this->rejectedCallbacks[] = $onRejected;
            }
        });
    }

    /**
     * @param mixed $value
     * @return void
     */
    private function resolve(mixed $value): void
    {
        if ($this->state !== 'pending') {
            return;
        }
        // Assimilação de thenables/promises: segue a semântica A+.
        if ($value instanceof PromiseInterface) {
            $value->then($this->resolve(...))->catch($this->reject(...));
            return;
        }
        $this->state = 'fulfilled';
        $this->value = $value;

        foreach ($this->fulfilledCallbacks as $cb) {
            TaskQueue::instance()->defer(fn() => $cb($value));
        }
        $this->fulfilledCallbacks = [];
    }

    /**
     * @param callable $onRejected
     * @return PromiseInterface
     */
    public function catch(callable $onRejected): PromiseInterface
    {
        return new Promise(function ($resolve, $reject) use ($onRejected) {
            $handleFulfilled = static function ($value) use ($resolve) {
                $resolve($value);
            };

            $handleRejected = static function ($reason) use ($onRejected, $resolve, $reject) {
                try {
                    $result = $onRejected($reason);
                    if ($result instanceof PromiseInterface) {
                        $result->then($resolve)->catch($reject);
                    } else {
                        $resolve($result);
                    }
                } catch (\Throwable $e) {
                    $reject($e);
                }
            };

            if ($this->state === 'fulfilled') {
                // <<< ESTE RAMO FALTAVA
                TaskQueue::instance()->defer(fn() => $handleFulfilled($this->value));
            } elseif ($this->state === 'rejected') {
                TaskQueue::instance()->defer(fn() => $handleRejected($this->reason));
            } else { // pending
                $this->fulfilledCallbacks[] = $handleFulfilled;
                $this->rejectedCallbacks[] = $handleRejected;
            }
        });
    }

    /**
     * @param callable $onFulfilled
     * @return Promise
     */
    public function then(callable $onFulfilled): PromiseInterface
    {
        return new Promise(function ($resolve, $reject) use ($onFulfilled) {
            $handleFulfilled = static function ($value) use ($onFulfilled, $resolve, $reject) {
                try {
                    $result = $onFulfilled($value);
                    if ($result instanceof PromiseInterface) {
                        $result->then($resolve)->catch($reject);
                    } else {
                        $resolve($result);
                    }
                } catch (\Throwable $e) {
                    $reject($e);
                }
            };

            $handleRejected = static function ($reason) use ($reject) {
                // Propaga a rejeição para a nova promise
                $reject($reason);
            };

            if ($this->state === 'fulfilled') {
                TaskQueue::instance()->defer(fn() => $handleFulfilled($this->value));
            } elseif ($this->state === 'rejected') {
                TaskQueue::instance()->defer(fn() => $handleRejected($this->reason));
            } else { // pending
                $this->fulfilledCallbacks[] = $handleFulfilled;
                $this->rejectedCallbacks[] = $handleRejected;
            }
        });
    }
}