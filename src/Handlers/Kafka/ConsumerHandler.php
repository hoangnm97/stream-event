<?php

namespace Softel\StreamEventDriven\Handlers\Kafka;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Exception;
use Interop\Queue\{Consumer, Context, Message, Topic};
use Softel\StreamEventDriven\Contracts\ConsumerHandlerInterface;
use Softel\StreamEventDriven\Exception\StreamException;
use Softel\StreamEventDriven\Exception\StreamUnhandledException;
use Softel\StreamEventDriven\Handlers\Kafka\Traits\FailureHandler;
use Softel\StreamEventDriven\Logger\Logger;
use Softel\StreamEventDriven\Models\FailedStream;
use Softel\StreamEventDriven\Resolver\StreamableResolver;
use Throwable;

class ConsumerHandler implements ConsumerHandlerInterface
{
    use FailureHandler;

    private RdKafkaConnectionFactory $connection;
    private StreamableResolver $streamableResolver;
    private Logger $logger;
    private ?Context $context = null;
    private ?Consumer $consumer = null;

    private ?string $topicName;

    /**
     * ConsumerHandler constructor.
     *
     * @param RdKafkaConnectionFactory $connection
     * @param StreamableResolver $streamableResolver
     * @param Logger $logger
     *
     * @throws Throwable
     */
    public function __construct(
        RdKafkaConnectionFactory $connection,
        StreamableResolver $streamableResolver,
        Logger $logger
    ) {
        $this->connection = $connection;
        $this->streamableResolver = $streamableResolver;
        $this->logger = $logger;
        try {
            $this->context = $this->connection->createContext();
        } catch (Throwable $throwable) {
            $this->logger->error('Initialize KAFKA consumer handler failed: '.$throwable->getMessage());
            throw $throwable;
        }
    }

    public function createQueue(string $topicName): Topic
    {
        // Validate topic name
        $topicName = throw_unless($topicName, new Exception('Invalid topic consumer'));

        return $this->context->createQueue($topicName);
    }

    public function receiveMessage(Topic $queue): ?Message
    {
        $consumer = $this->context->createConsumer($queue);
		$message = $consumer->receive();
        $context = ['message_id' => $message->getMessageId()];
        $this->logger->info('Kafka consumer receives message', $context);
        $consumer->acknowledge($message);
        $this->logger->info('Kafka consumer acknowledges message', $context);

        return $message;
    }

    public function processMessage(Topic $queue, Message $message): bool
	{
        $context = ['message_id' => $message->getMessageId()];
		try {
            $this->logger->info('Kafka consumer processes message', $context);

            $object = $message->getProperty('object');
            $action = $message->getProperty('action');
            $body = $message->getBody();
			$priority = $message->getHeader('priority');

            $this->logger->info("Kafka message object: ".$object, $context);
            $this->logger->info("Kafka message action: ".$action, $context);
            $this->logger->info("Kafka message priority: ".$priority, $context);
            $this->logger->info("Kafka message body: ".$body, $context);
            $resolver = $this->streamableResolver->resolve($object);
			if (!$resolver) {
				$this->logger->info('Ignore object '. $object, $context);
				return false;
			}
			$resolver->receiveStream($action, $body, $priority);
			$this->logger->info('Kafka consumer processed message', $context);
        } catch (StreamException $throwable) {
            $this->logger->error('Error "'.$throwable->getMessage().'" occurs while processing message', $context);
            $this->handleFailedMessage(FailedStream::HANDLE_CONSUME, $queue, $message, $throwable);
            throw $throwable;
        } catch (StreamUnhandledException $exception) {
			$this->logger->warning('Error "'.$exception->getMessage(), $context);
			$this->unhandledMessage($queue->getTopicName(), $message);
			throw $exception;
		}
		return true;
		
	}
}
