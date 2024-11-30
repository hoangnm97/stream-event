<?php

namespace Softel\StreamEventDriven\Console;

use Illuminate\Console\Command;
use Softel\StreamEventDriven\Contracts\ConsumerHandlerInterface;
use Throwable;

class ConsumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "stream:consume {topicName=''}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stream consumer';

    /**
     * Execute the console command.
     *
     * @param ConsumerHandlerInterface $handler
     *
     * @return void
     *
     * @throws Throwable
     */
    public function handle(ConsumerHandlerInterface $handler)
    {

        $topicName = $this->argument('topicName');
        $topic = $handler->createQueue($topicName);
        while (true) {
            $message = $handler->receiveMessage($topic);
            $this->info('['.date('Y-m-d H:i:s').']['.$message->getMessageId().'] Stream Received');
            try {
                $handler->processMessage($topic, $message);
                $this->info('['.date('Y-m-d H:i:s').']['.$message->getMessageId().'] Stream Processed');
            } catch (Throwable $throwable) {
                $this->info('['.date('Y-m-d H:i:s').']['.$message->getMessageId().'] Stream Failed: '
					. json_encode($throwable->getTrace())
					. 'in: ' . $throwable->getFile() . 'at: ' . $throwable->getLine()
				);
                $this->error($throwable->getMessage());
            }

            usleep(1);
        }
    }
}
