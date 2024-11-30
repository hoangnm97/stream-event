<?php

namespace Hoangdev\StreamEventDriven\Listeners;

use Illuminate\Events\Dispatcher;
use Hoangdev\StreamEventDriven\Events\StreamCreated;
use Hoangdev\StreamEventDriven\Jobs\StreamJob;

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
			'Hoangdev\StreamEventDriven\Listeners\StreamListener@onCreated'
		);
		
	}
}
