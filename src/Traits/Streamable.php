<?php

namespace Hoangdev\StreamEventDriven\Traits;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Hoangdev\StreamEventDriven\Contracts\StreamableResourceInterface;
use Hoangdev\StreamEventDriven\Events\StreamCreated;
use Hoangdev\StreamEventDriven\Exception\StreamException;
use Throwable;

trait Streamable
{
	/**
	 * @throws Throwable
	 */
	public static function bootStreamable(): void
	{
		static::created(
			static fn(StreamableResourceInterface $instance) => $instance->pushStream(
				StreamableResourceInterface::ACTION_CREATE
			)
		);
		static::deleted(
			static fn(StreamableResourceInterface $instance) => $instance->pushStream(
				StreamableResourceInterface::ACTION_DELETE
			)
		);
		static::updated(
			static fn(StreamableResourceInterface $instance) => $instance->pushStream(
				StreamableResourceInterface::ACTION_UPDATE
			)
		);
	}
	
	public function isPushable(): bool
	{
		return true;
	}
	
	public function isReceivable(): bool
	{
		return true;
	}
	
	
	public function pushStream(string $action): void
	{
		if ($this->isPushable()) {
			event(new StreamCreated($this, $action));
		}
	}
	
	public function receiveStream(string $action, ?string $streamBody): void
	{
		if (!$this->isReceivable()) {
			Log::info(self::class . ' does not support to receive stream', Arr::wrap(json_decode($streamBody)));
		}
		
		if (!$streamBody) {
			throw new StreamException(self::class . ' received an empty stream body');
		}
		
		$attributes = json_decode($streamBody, true);
		
		$id = Arr::pull($attributes, $this->getKeyName());
		$resource = $this->findOrNew($id);
		switch ($action) {
			case StreamableResourceInterface::ACTION_CREATE:
			case StreamableResourceInterface::ACTION_UPDATE:
				$resource->exists || $resource->forceFill([$resource->getKeyName() => $id]);
				$resource->fill($attributes)->save();
				break;
			case StreamableResourceInterface::ACTION_DELETE:
				$resource->exists || $resource->delete();
				break;
			default:
				throw new StreamException(self::class . ' has wrong stream action.');
		}
	}
}
