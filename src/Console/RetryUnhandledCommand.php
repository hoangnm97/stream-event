<?php

namespace Hoangdev\StreamEventDriven\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Interop\Queue\Message;
use Hoangdev\StreamEventDriven\Contracts\ConsumerHandlerInterface;
use Hoangdev\StreamEventDriven\Models\UnhandledMessage;

class RetryUnhandledCommand extends Command
{
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = "stream:retry_unhandled {topicName=''} {action=''} {object=''} {offset=''}";
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Retry a unhandled stream';
	
	/**
	 * Execute the console command.
	 *
	 * @return int
	 * @throws BindingResolutionException
	 */
	public function handle(): int
	{
		$topicName = $this->argument('topicName');
		$action = $this->argument('action');
		$object = $this->argument('object');
		$limit = $this->argument('limit');
		$offset = $this->argument('offset');
		if (empty($action) || empty($object) || empty($topicName)) {
			$this->error(
				'Command is not has required argument, needed to have topicName, action, object, limit and offset as order'
			);
			return 0;
		}
		$this->info(
			'Started command with argument topicName: ' . $topicName . ' ,action: ' . $action . ' ,object: ' . $object . ', limit: ' . $limit
			. ' and offset: ' . $offset
		);
		
		$unhandledMessages = $this->laravel->make(UnhandledMessage::class)
			->where([
				'topic'=> $topicName,
				'object'=> $object,
				'action'=> $action,
			]);
		if (!empty($limit)) {
			$unhandledMessages = $unhandledMessages->limit($limit);
		}
		
		if (!empty($offset)) {
			$unhandledMessages = $unhandledMessages->offset($offset);
		}
		$unhandledMessages = $unhandledMessages->get();
		$handler = $this->laravel->make(ConsumerHandlerInterface::class);
		
		foreach ($unhandledMessages as $unhandledMessage) {
			$this->info('Started to handle message: ' . $unhandledMessage->body);
			
			$message = $this->laravel->make(Message::class);
			$message->setHeaders(json_decode($unhandledMessage->headers, true));
			$message->setBody($unhandledMessage->body);
			$message->setProperties(json_decode($unhandledMessage->properties, true));
			$unhandledMessage->delete();
			try {
				$handler->processMessage($handler->createQueue($topicName), $message);
			} catch (Exception $exception) {
				$this->error('Failed to handle message: ' . $unhandledMessage->toJson());
				$this->error('Error:' . $exception->getMessage());
				continue;
			}
		}
		return 0;
	}
	
}
