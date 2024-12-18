<?php

namespace Hoangdev\StreamEventDriven\Exception;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class StreamException extends Exception
{
	public function __construct(
		string $message = "",
		protected $data = null,
		int $code = 0,
		?Throwable $previous = null,
	) {
		$this->message = "[StreamException] " . $message;
		parent::__construct($message, $code, $previous);
	}
	
	public function render(): JsonResponse
	{
		return response()->json(
			[
				'message' => $this->message ?: $this->getMessage(),
				'code' => $this->getCode(),
				'data' => $this->data
			],
			400
		);
	}
}