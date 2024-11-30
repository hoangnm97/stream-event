<?php

namespace Hoangdev\StreamEventDriven\Traits;

use Illuminate\Support\Arr;
use Hoangdev\StreamEventDriven\Contracts\StreamableResourceInterface;
use Hoangdev\StreamEventDriven\Exception\StreamException;
use Hoangdev\StreamEventDriven\Models\FailedStream;
use Throwable;

trait ReceivedStreamable
{

	public function receiveStream(string $action, ?string $streamBody, ?string $priority): void
	{

		if (!$streamBody) {
			throw new StreamException(self::class . ' received an empty stream body');
		}
		
		$attributes = json_decode($streamBody, true);
		
		$id = Arr::pull($attributes, $this->getKeyName());
		$resource = $this->findOrNew($id);
		switch ($action) {
			case StreamableResourceInterface::ACTION_CREATE:
				if (!$resource && !$this->withTrashed()->find($id)) {
					$this->create($attributes);
				}
				break;
			case StreamableResourceInterface::ACTION_UPDATE:
				$resource = $this->findOrNew($id);
				if ($resource->updated_at->timestamp < $priority) {
					$resource->forceFill([$resource->getKeyName() => $id]);
					$resource->fill($attributes)->save();
				}
				break;
			case StreamableResourceInterface::ACTION_DELETE:
				if ($resource) {
					$resource->delete();
				}
				break;
			default:
				throw new StreamException(self::class . ' has wrong stream action.');
		}
	}
	
	
}
