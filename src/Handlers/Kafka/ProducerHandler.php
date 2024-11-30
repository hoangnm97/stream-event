<?php

namespace Hoangdev\StreamEventDriven\Handlers\Kafka;

use Carbon\Carbon;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Exception;
use Interop\Queue\{Context, Message, Topic};
use Ramsey\Uuid\Uuid;
use Hoangdev\StreamEventDriven\Exception\StreamException;
use Hoangdev\StreamEventDriven\Models\FailedStream;
use Hoangdev\StreamEventDriven\Contracts\{StreamablePushInterface};
use Hoangdev\StreamEventDriven\Contracts\ProducerHandlerInterface;
use Hoangdev\StreamEventDriven\Handlers\Kafka\Traits\FailureHandler;
use Hoangdev\StreamEventDriven\Logger\Logger;
use Throwable;

class ProducerHandler implements ProducerHandlerInterface
{
	use FailureHandler;
	
	private RdKafkaConnectionFactory $connection;
	private Logger $logger;
	private ?Context $context = null;
	
	/**
	 * KafkaProducer constructor.
	 *
	 * @param RdKafkaConnectionFactory $connection
	 * @param Logger $logger
	 *
	 * @throws Throwable
	 */
	public function __construct(RdKafkaConnectionFactory $connection, Logger $logger)
	{
		$this->connection = $connection;
		$this->logger = $logger;
		try {
			$this->context = $this->connection->createContext();
		} catch (Throwable $throwable) {
			$this->logger->error('Errors while init kafka producer: ' . $throwable->getMessage());
			throw $throwable;
		}
	}
	
	public function createTopic(?string $topicName = null): Topic
	{
		if (empty($topicName)) {
			$topicName = config('streamable.platforms.kafka.sync_topic');
		}
		// Validate topic name
		$topicName = throw_unless($topicName, new Exception('Invalid topic producer'));
		
		return $this->context->createTopic($topicName);
	}
	
	/**
	 * @param StreamablePushInterface|null $stream
	 * @param string $action
	 * @return Message
	 * @throws StreamException
	 */
	public function createMessage(StreamablePushInterface|null $stream, string $action): Message
	{
		if (!($stream instanceof StreamablePushInterface)) {
			$error = 'The streaming object does not implement ' . StreamablePushInterface::class;
			$this->logger->error($error);
			throw new StreamException($error);
		}
		
		if (blank($action)) {
			$error = 'Blank action';
			$this->logger->error($error);
			throw new StreamException($error);
		}
        $message = $stream->buildMessage();
		
		if (blank($message) || !is_array(json_decode($message, true))) {
            $error = 'Message wrong format: [Message=' . $message . ']';
            $this->logger->error($error);
            throw new StreamException($error);
        }
		return $this->context->createMessage(
			$message,
			[
				'object' => $stream->getObjectName(),
				'action' => $action
			],
			[
				'message_id' => Uuid::uuid4()->toString(),
				'priority' => Carbon::now()->timestamp
			]
		);
	}
	
	public function sendMessage(Topic $topic, Message $message): void
	{
		$context = [
			'topic' => $topic->getTopicName(),
			'object' => $message->getProperty('object'),
			'action' => $message->getProperty('action'),
			'message' => $message->getBody()
		];
		try {
			$this->context->createProducer()->send($topic, $message);
			$this->logger->info('Message sent', $context);
		} catch (Throwable $throwable) {
			$this->logger->error('Error "' . $throwable->getMessage() . '" occurs while sending message');
			$this->handleFailedMessage(FailedStream::HANDLE_CONSUME, $topic, $message, $throwable);
		}
	}
}
