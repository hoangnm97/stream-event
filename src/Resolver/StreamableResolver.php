<?php

namespace Hoangdev\StreamEventDriven\Resolver;

use Exception;
use Illuminate\Support\Arr;
use Hoangdev\StreamEventDriven\Contracts\StreamableReceivedInterface;
use Hoangdev\StreamEventDriven\Exception\StreamUnhandledException;
use Hoangdev\StreamEventDriven\Handlers\Kafka\Traits\FailureHandler;
use Throwable;

class StreamableResolver
{
	use FailureHandler;
	
	/**
	 * @param string $object
	 *
	 * @return false|StreamableReceivedInterface|null
	 *
	 * @throws StreamUnhandledException
	 * @throws Throwable
	 */
    public function resolve(string $object): false|StreamableReceivedInterface|null
	{
		$objectIgnored = config('streamable.ignore_objects');
		if (Arr::exists($objectIgnored, $object)) {
			return false;
		}
        $resources = config('streamable.resources');
		if (!Arr::exists($resources, $object)) {
			throw new StreamUnhandledException('object not handled');
		}
        $resource = new $resources[$object]();
        throw_unless(
            $resource instanceof StreamableReceivedInterface,
            new Exception(
                "The resource [$object] must be instanceof App\StreamProcessing\Contracts\StreamableReceivedInterface."
            )
        );

        return $resource;
    }
}
