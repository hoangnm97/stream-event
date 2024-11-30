<?php

namespace Hoangdev\StreamEventDriven\Contracts;

use Illuminate\Database\Eloquent\Model;
use Interop\Queue\{Message, Topic};
use Hoangdev\StreamEventDriven\Contracts\StreamableResourceInterface as StreamableResourceContract;
use Throwable;

interface ProducerHandlerInterface
{
    /**
     * @param string|null $topicName
     * @return Topic
     *
     * @throws Throwable
     */
    public function createTopic(?string $topicName = null): Topic;
	
	/**
	 * @param StreamablePushInterface $stream
	 * @param string $action
	 * @return Message
	 */
    public function createMessage(StreamablePushInterface $stream, string $action): Message;

    /**
     * @param Topic $topic
     * @param Message $message
     */
    public function sendMessage(Topic $topic, Message $message): void;

}
