<?php

declare(strict_types = 1);

namespace Omegaalfa\Promise;

use RuntimeException;

class AggregateException extends RuntimeException
{
	/**
	 * @param  string  $message
	 * @param  array   $errors
	 */
	public function __construct(string $message, private readonly array $errors)
	{
		parent::__construct($message);
	}

	/**
	 * @return array
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}
}