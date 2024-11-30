<?php


return [
	'stream_platform' => env('STREAM_PLATFORM'),
	'platforms' => require_once('streamable/platforms.php'),
	'resources' => require_once('streamable/resources.php'),
];
