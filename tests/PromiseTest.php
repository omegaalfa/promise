<?php

use PHPUnit\Framework\TestCase;
use src\promises\Promise;

class PromiseTest extends TestCase
{
    public function testResolve()
    {
        $promise = new Promise();
        $promise->resolve('Resolved value');

        $this->assertEquals('fulfilled', $promise->getState());
        $this->assertEquals('Resolved value', $promise->getValue());
    }

    public function testReject()
    {
        $promise = new Promise();
        $promise->reject('Rejected reason');

        $this->assertEquals('rejected', $promise->getState());
        $this->assertEquals('Rejected reason', $promise->getValue());
    }

    public function testThen()
    {
        $promise = new Promise();
        $promise->resolve('Resolved value');

        $result = null;
        $promise->then(function ($value) use (&$result) {
            $result = $value . ' processed';
        });

        $this->assertEquals('Resolved value processed', $result);
    }

    public function testCatch()
    {
        $promise = new Promise();
        $promise->reject('Rejected reason');

        $result = null;
        $promise->catch(function ($reason) use (&$result) {
            $result = 'Error: ' . $reason;
        });

        $this->assertEquals('Error: Rejected reason', $result);
    }
}
