<?php

namespace Hoangdev\StreamEventDriven\Contracts;

use Interop\Queue\{Message, Topic};
use Throwable;

interface ConsumerHandlerInterface
{

    /**
     * @param string|null $topicName
     *
     * @return Topic
     *
     * @throws Throwable
     */
    public function createQueue(string $topicName): Topic;

    /**
     * @param Topic $queue
     *
     * @return Message|null
     *
     * @throws Throwable
     */
    public function receiveMessage(Topic $queue): ?Message;

    /**
     * @param Topic $queue
     * @param Message $message
     *
     * @return boolean
     *
     * @throws Throwable
     */
    public function processMessage(Topic $queue, Message $message): bool;
}
