<?php

namespace Softel\StreamEventDriven\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Softel\StreamEventDriven\Contracts\{StreamableResourceInterface};
use Softel\StreamEventDriven\Contracts\ProducerHandlerInterface;
use Throwable;

class StreamJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    
    protected StreamableResourceInterface $stream;
    protected string $action;
    
    public function __construct(StreamableResourceInterface $stream, string $action)
    {
        $this->queue = 'stream_queue';
        $this->stream = $stream;
        $this->action = $action;
    }
    
    /**
     * @param ProducerHandlerInterface $handler
     *
     * @throws Throwable
     */
    public function handle(ProducerHandlerInterface $handler): void
    {
        try {
            $topic = empty($this->stream->getSuffixTopic()) ? null : config(
                    'streamable.platforms.kafka.sync_topic'
                ) . '_' . $this->stream->getSuffixTopic();
        } catch (Exception $exception) {
            // ignore exception
            $topic = null;
        }
        
        $handler->sendMessage(
            $handler->createTopic($topic),
            $handler->createMessage($this->stream, $this->action)
        );
    }
    
    /**
     * The job failed to process.
     *
     * @param Throwable $exception
     *
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Sync ' . $this->queue . ' [' . get_class($this->stream) . '] failed: ' . $exception->getMessage());
    }
}
