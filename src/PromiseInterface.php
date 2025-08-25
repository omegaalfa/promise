<?php

declare(strict_types=1);

namespace Omegaalfa\Promise;

interface PromiseInterface
{
    /**
     * @param callable $onFulfilled
     * @return PromiseInterface
     */
    public function then(callable $onFulfilled): PromiseInterface;

    /**
     * @param callable $onRejected
     *
     * @return PromiseInterface
     */
    public function catch(callable $onRejected): PromiseInterface;

    /**
     * @param callable $onFinally
     *
     * @return PromiseInterface
     */
    public function finally(callable $onFinally): PromiseInterface;
}