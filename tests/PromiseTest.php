<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Omegaalfa\Promise\Promise;
use Omegaalfa\Promise\TaskQueue;

final class PromiseTest extends TestCase
{
    public function testPromiseResolvesValue(): void
    {
        $promise = new Promise(function ($resolve, $reject) {
            $resolve('ok');
        });

        $result = null;
        $promise->then(function ($value) use (&$result) {
            $result = $value;
        });

        TaskQueue::instance()->run();

        $this->assertSame('ok', $result);
    }

    public function testPromiseRejectsValue(): void
    {
        $promise = new Promise(function ($resolve, $reject) {
            $reject(new \RuntimeException('fail'));
        });

        $errorMessage = null;
        $promise->catch(function ($reason) use (&$errorMessage) {
            $errorMessage = $reason->getMessage();
        });

        TaskQueue::instance()->run();

        $this->assertSame('fail', $errorMessage);
    }

    public function testPromiseFinallyIsCalled(): void
    {
        $promise = new Promise(function ($resolve, $reject) {
            $resolve('done');
        });

        $finallyCalled = false;
        $promise
            ->then(fn($value) => $value)
            ->finally(function () use (&$finallyCalled) {
                $finallyCalled = true;
            });

        TaskQueue::instance()->run();

        $this->assertTrue($finallyCalled);
    }

    public function testPromiseChain(): void
    {
        $promise = new Promise(function ($resolve, $reject) {
            $resolve(2);
        });

        $result = null;
        $promise
            ->then(fn($value) => $value * 2)
            ->then(function ($value) use (&$result) {
                $result = $value + 1;
            });

        TaskQueue::instance()->run();

        $this->assertSame(5, $result);
    }

    public function testAsyncHelper(): void
    {
        $promise = new Promise(function ($resolve, $reject) {
            TaskQueue::instance()->addTimer(1, fn() => $resolve('async ok'));
        });

        $result = null;
        $promise->then(function ($value) use (&$result) {
            $result = $value;
        });

        TaskQueue::instance()->run();

        $this->assertSame('async ok', $result);
    }
}
