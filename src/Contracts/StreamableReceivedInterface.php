<?php

namespace Hoangdev\StreamEventDriven\Contracts;

use Throwable;

interface StreamableReceivedInterface extends StreamableResourceInterface
{
	/**
	 * @param string $action
	 * @param string|null $streamBody
	 *
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function receiveStream(string $action, ?string $streamBody, ?string $priority): void;

    public function validateReceivedMessage(array $message): bool;
	
}
