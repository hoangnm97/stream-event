<?php

namespace Hoangdev\StreamEventDriven\Events;

use Illuminate\Queue\SerializesModels;
use Hoangdev\StreamEventDriven\Contracts\StreamableResourceInterface as StreamableResourceContract;

class StreamCreated
{
    use SerializesModels;

    public StreamableResourceContract $stream;
    public string $action;

    /**
     * SynchronizationCreated constructor.
     *
     * @param StreamableResourceContract $stream
     * @param string $action
     */
    public function __construct(StreamableResourceContract $stream, string $action)
    {
        $this->stream = $stream;
        $this->action = $action;
    }
}
