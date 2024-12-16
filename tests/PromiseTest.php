<?php

use PHPUnit\Framework\TestCase;
use Omegaalfa\Promise\Promise;

class PromiseTest extends TestCase
{
	public function testResolve()
	{
		$promise = new Promise(function($resolve) {
			$resolve('Success');
		});

		$promise->then(function($result) {
			$this->assertEquals('Success', $result);
		});
	}

	public function testReject()
	{
		$promise = new Promise(function($resolve, $reject) {
			$reject(new Exception('Failure'));
		});

		$promise->catch(function($error) {
			$this->assertInstanceOf(Exception::class, $error);
			$this->assertEquals('Failure', $error->getMessage());
		});
	}

	public function testThen()
	{
		$promise = new Promise(function($resolve) {
			$resolve('First step');
		});

		$promise->then(function($result) {
			$this->assertEquals('First step', $result);
			return 'Second step';
		})
			->then(function($result) {
				$this->assertEquals('Second step', $result);
			});
	}
}

