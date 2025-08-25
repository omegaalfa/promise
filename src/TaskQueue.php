<?php

declare(strict_types=1);

namespace Omegaalfa\Promise;

use Fiber;
use Throwable;


class TaskQueue
{
    /**
     * @var array<int, Fiber<int, mixed, mixed, mixed>>
     */
    protected array $callables = [];

    /**
     * @var array<int, string>
     */
    protected array $errors = [];

    protected static ?self $singleton = null;

    protected static bool $registered = false;

    private function __construct() {}
    private function __clone() {}

    /**
     * @return TaskQueue
     */
    public static function instance(): TaskQueue
    {
        if (!self::$registered) {
            register_shutdown_function(static fn() => self::instance()->run());
            self::$registered = true;
        }

        return self::$singleton ??= new self();
    }

    /**
     * @param callable $callable
     *
     * @return int
     */
    public function defer(callable $callable): int
    {
        $fiber = new Fiber($callable);
        $fiberId = spl_object_id($fiber);
        $this->callables[$fiberId] = $fiber;

        return $fiberId;
    }


    /**
     * @param float|int $intervalSeconds
     * @param callable $callback
     * @param int|null $number
     *
     * @return int
     */
    public function repeat(float|int $intervalSeconds, callable $callback, int|null $number = null): int
    {
        return $this->defer(function () use ($number, $intervalSeconds, $callback) {
            try {
                if (is_int($number) && $number > 0) {
                    for ($i = 0; $i < $number; ++$i) {
                        $this->sleep($intervalSeconds);
                        $callback();
                    }
                    return;
                }
                for (; ;) {
                    $this->sleep($intervalSeconds);
                    $callback();
                }
            } catch (Throwable $exception) {
                $this->errors[] = $exception->getMessage();
            }
        });
    }


    /**
     * @param float|int $seconds
     *
     * @return void
     * @throws Throwable
     */
    public function sleep(float|int $seconds): void
    {
        $stop = microtime(true) + $seconds;
        while (microtime(true) < $stop) {
            $this->next();
            usleep(1000);
        }
    }

    /**
     * @param mixed|null $value
     *
     * @return mixed
     * @throws Throwable
     */
    public function next(mixed $value = null): mixed
    {
        return Fiber::suspend($value);
    }

    /**
     * @param float|int $seconds
     * @param callable $callback
     *
     * @return int
     */
    public function addTimer(float|int $seconds, callable $callback): int
    {
        return $this->defer(function () use ($seconds, $callback) {
            $this->sleep($seconds);
            return $callback();
        });
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function cancel(int $id): void
    {
        if (isset($this->callables[$id])) {
            unset($this->callables[$id]);
        }
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        while (!empty($this->callables)) {
            $this->execCallables();
        }
    }

    /**
     * @return void
     */
    private function execCallables(): void
    {
        foreach ($this->callables as $id => $fiber) {
            try {
                $this->call($id, $fiber);
            } catch (Throwable $exception) {
                $this->errors[$id] = $exception->getMessage();
            }
        }
    }

    /**
     * @param int $id
     * @param Fiber<int, mixed, mixed, mixed> $fiber
     *
     * @return mixed
     * @throws Throwable
     */
    protected function call(int $id, Fiber $fiber): mixed
    {
        if (!$fiber->isStarted()) {
            return $fiber->start($id);
        }

        if (!$fiber->isTerminated()) {
            try {
                return $fiber->resume();
            } catch (Throwable $exception) {
                $this->errors[$id] = $exception->getMessage();
            }
        }

        unset($this->callables[$id]);

        return $fiber->getReturn();
    }
}