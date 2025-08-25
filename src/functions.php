<?php

declare(strict_types=1);

use Omegaalfa\Promise\Promise;
use Omegaalfa\Promise\TaskQueue;

function async(callable $closure): Promise
{
    return new Promise(function ($resolve, $reject) use ($closure) {
        TaskQueue::instance()->defer(function () use ($resolve, $reject, $closure) {
            try {
                $closure($resolve, $reject);
            } catch (\Throwable $e) {
                $reject($e);
            }
        });
    });
}
