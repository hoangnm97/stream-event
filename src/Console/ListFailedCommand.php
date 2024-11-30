<?php

namespace Hoangdev\StreamEventDriven\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Hoangdev\StreamEventDriven\Models\FailedStream;

class ListFailedCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stream:failed';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'List all of the failed stream';
	
	/**
	 * The table headers for the command.
	 *
	 * @var array
	 */
	protected array $headers = ['ID', 'Platform', 'Topic', 'Object', 'Action', 'Failed At'];
	
	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function handle(): void
	{
		if (count($streams = $this->getFailedStreams()) === 0) {
			$this->info('No failed streams!');
		}
		
		$this->displayFailedStreams($streams);
	}
	
	/**
	 * Compile the failed jobs into a displayable format.
	 *
	 * @return array
	 * @throws BindingResolutionException
	 */
	protected function getFailedStreams(): array
	{
		$failedStreams = $this->laravel->make(FailedStream::class)->all();
		
		return $failedStreams->map(fn($failed) => $this->parseFailedStream($failed->toArray()))->filter()->all();
	}
	
	/**
	 * Parse the failed job row.
	 *
	 * @param array $failed
	 * @return array
	 */
	protected function parseFailedStream(array $failed): array
	{
		$row = array_values(Arr::except($failed, ['headers', 'body', 'exception', 'handle', 'deleted_at']));
		array_splice($row, 2, 1);
		array_splice($row, 3, 0, $failed['properties']['object']);
		array_splice($row, 4, 0, $failed['properties']['action']);
		return $row;
	}
	
	
	/**
	 * Display the failed jobs in the console.
	 *
	 * @param array $jobs
	 * @return void
	 */
	protected function displayFailedStreams(array $jobs): void
	{
		$this->table($this->headers, $jobs);
	}
}
