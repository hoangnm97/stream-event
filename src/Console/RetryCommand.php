<?php

namespace Hoangdev\StreamEventDriven\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Interop\Queue\Message;
use Hoangdev\StreamEventDriven\Contracts\ConsumerHandlerInterface;
use Hoangdev\StreamEventDriven\Contracts\ProducerHandlerInterface;
use Hoangdev\StreamEventDriven\Models\FailedStream;
use Throwable;

class RetryCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'stream:retry
                            {handle : Handle failed producing [produce] streams or failed consuming [consume] streams}
                            {id?* : The ID of the failed stream or "all" to retry all streams}
                            {--range=* : Range of stream IDs (numeric) to be retried}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry a failed stream';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
	{
        $handleType = $this->getHandleType();
        if (!$handleType || !in_array($handleType, ['produce', 'consume'])) {
            $this->error('Invalid handle type');
            return;
        }
        foreach ($this->getStreamIds() as $id) {
            $stream = $this->laravel->make(FailedStream::class)
                ->where(
                    'handle',
                    'consume' === $handleType ? FailedStream::HANDLE_CONSUME : FailedStream::HANDLE_PRODUCE
                )->find($id);

            if (is_null($stream)) {
                $this->error("Unable to find failed stream with ID [{$id}].");
                continue;
            }
            $this->retryStream($stream);
            $this->info("The failed stream [{$id}] has been pushed back to the processor!");
            $stream->delete();
        }
    }

    public function getHandleType(): ?string
    {
        return (string)$this->argument('handle');
    }

    /**
     * Get the stream IDs to be retried.
     *
     * @return array
     *
     * @throws Throwable
     */
    protected function getStreamIds(): array
    {
        $ids = (array)$this->argument('id');

        if (count($ids) === 1 && $ids[0] === 'all') {
            return Arr::pluck($this->laravel->make(FailedStream::class)->all(), 'id');
        }

        if ($ranges = (array)$this->option('range')) {
            $ids = array_merge($ids, $this->getStreamIdsByRange($ranges));
        }

        return array_values(array_filter(array_unique($ids)));
    }

    /**
     * Get the stream IDs ranges, if applicable.
     *
     * @param array $ranges
     *
     * @return array
     */
    protected function getStreamIdsByRange(array $ranges): array
    {
        $ids = [];

        foreach ($ranges as $range) {
            if (preg_match('/^[0-9]+\-[0-9]+$/', $range)) {
                $ids = array_merge($ids, range(...explode('-', $range)));
            }
        }

        return $ids;
    }

    /**
     * Retry the stream sending.
     *
     * @param FailedStream $stream
     *
     * @return void
     */
    protected function retryStream($stream): void
    {
        $message = $this->laravel->make(Message::class);
        $message->setHeaders($stream->headers);
        $message->setBody(json_encode($stream->body));
        $message->setProperties($stream->properties);
        if ($stream->isFailedOnProduce()) {
            $handler = $this->laravel->make(ProducerHandlerInterface::class);
            $handler->sendMessage($handler->createTopic($stream->topic), $message);
        }
        if ($stream->isFailedOnConsume()) {
            $handler = $this->laravel->make(ConsumerHandlerInterface::class);
            $handler->processMessage($handler->createQueue($stream->topic), $message);
        }
    }
}
