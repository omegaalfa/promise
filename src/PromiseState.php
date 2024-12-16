<?php

declare(strict_types = 1);

namespace Omegaalfa\Promise;

enum PromiseState: string
{
	case Pending = 'pending';
	case Fulfilled = 'fulfilled';
	case Rejected = 'rejected';
}