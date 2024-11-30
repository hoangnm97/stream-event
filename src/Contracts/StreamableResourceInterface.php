<?php

namespace Softel\StreamEventDriven\Contracts;

use Throwable;

interface StreamableResourceInterface
{
	const ACTION_CREATE = 'create';
	const ACTION_UPDATE = 'update';
	const ACTION_DELETE = 'delete';
	
}
