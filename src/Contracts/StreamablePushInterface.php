<?php

namespace Softel\StreamEventDriven\Contracts;

use Throwable;

interface StreamablePushInterface extends StreamableResourceInterface
{
	/**
	 * @param string $action
	 *
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function pushStream(string $action): void;

    public function buildMessage(): string;
	
	public function getObjectName(): string;
 
    public function getSuffixTopic(): string;
}
