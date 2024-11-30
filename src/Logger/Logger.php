<?php

namespace Softel\StreamEventDriven\Logger;

use Illuminate\Contracts\Support\{Arrayable, Jsonable};
use Illuminate\Support\Stringable;
use Monolog\Handler\StreamHandler;

class Logger extends \Monolog\Logger
{
	public function __construct()
	{
		$name = 'STREAM_PROCESSING_LOGGER';
		$path = 'logs/stream/' . date('Y-m-d') . '.log';
		$handlers = [new StreamHandler(storage_path($path))];
		parent::__construct($name, $handlers);
	}
	
	/**
	 * Format the parameters for the logger.
	 *
	 * @param mixed $message
	 * @return string|null
	 */
	public function formatMessage(mixed $message): ?string
	{
		if (is_array($message)) {
			$message = new Stringable(var_export($message, true));
			return $message->replaceMatches('/\s+/', ' ');
		} elseif ($message instanceof Jsonable) {
			return $message->toJson();
		} elseif ($message instanceof Arrayable) {
			$message = new Stringable(var_export($message->toArray(), true));
			return $message->replaceMatches('/\s+/', ' ');
		}
		
		return $message;
	}
}
