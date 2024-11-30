<?php

namespace Softel\StreamEventDriven\Handlers\Kafka\Traits;

use Illuminate\Database\Eloquent\Model;
use Interop\Queue\Message;
use Interop\Queue\Topic;
use Softel\StreamEventDriven\Models\FailedStream;
use Softel\StreamEventDriven\Models\UnhandledMessage;
use Throwable;

trait FailureHandler
{
    /**
     * @param int $handleType
     * @param Topic $topic
     * @param Message $message
     * @param Throwable $exception
     *
     * @return FailedStream|Model
     */
    protected function handleFailedMessage(int $handleType, Topic $topic, Message $message, Throwable $exception): Model|FailedStream
	{
        return FailedStream::create(
            [
                'body' => $message->getBody(),
                'properties' => $message->getProperties(),
                'headers' => $message->getHeaders(),
                'topic' => $topic->getTopicName(),
                'exception' => $exception->getTraceAsString(),
                'handle'=>$handleType,
            ]
        );
    }
	
	protected function unhandledMessage(string $topicName, Message $message)
	{
		$properties = $message->getProperties();
		return UnhandledMessage::create(
			[
				'body' => $message->getbody(),
				'action' => $properties['action'],
				'object' => $properties['object'],
				'properties' => json_encode($message->getProperties()),
				'headers' => json_encode($message->getHeaders()),
				'topic' => $topicName,
			]
		);
	}
}
