<?php

namespace Softel\StreamEventDriven\Listeners;

use Illuminate\Events\Dispatcher;
use Softel\StreamEventDriven\Events\StreamCreated;
use Softel\StreamEventDriven\Jobs\StreamJob;

class StreamListener
{
	public function onCreated(StreamCreated $event): void
	{
		dispatch(new StreamJob($event->stream, $event->action));
	}
	
	public function onPushed(StreamPusherCreated $event): void
	{
		dispatch(new StreamJob($event->stream, $event->action));
	}
	
	/**
	 * Register the listeners for the subscriber.
	 *
	 * @param Dispatcher $events
	 */
	public function subscribe(Dispatcher $events): void
	{
		$events->listen(
			StreamCreated::class,
			'Softel\StreamEventDriven\Listeners\StreamListener@onCreated'
		);
		
	}
}
