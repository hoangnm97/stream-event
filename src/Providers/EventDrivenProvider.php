<?php

namespace Hoangdev\StreamEventDriven\Providers;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use JetBrains\PhpStorm\NoReturn;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Hoangdev\StreamEventDriven\Console\ConsumeCommand;
use Hoangdev\StreamEventDriven\Console\ListFailedCommand;
use Hoangdev\StreamEventDriven\Console\RetryCommand;
use Hoangdev\StreamEventDriven\Console\RetryUnhandledCommand;
use Hoangdev\StreamEventDriven\Contracts\ConsumerHandlerInterface;
use Hoangdev\StreamEventDriven\Contracts\ProducerHandlerInterface;
use Hoangdev\StreamEventDriven\Handlers\Kafka\ConsumerHandler;
use Hoangdev\StreamEventDriven\Handlers\Kafka\ProducerHandler;
use Hoangdev\StreamEventDriven\Listeners\StreamListener;
use Illuminate\Contracts\Foundation\Application;
use Interop\Queue\Message;

class EventDrivenProvider extends ServiceProvider
{
	protected array $listen = [
		//
	];
	
	protected array $subscribe = [
		StreamListener::class,
	];
	
	protected array $commands = [
		ConsumeCommand::class,
		ListFailedCommand::class,
		RetryCommand::class,
		RetryUnhandledCommand::class
	];
	
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	#[NoReturn] public function register(): void
	{
		$this->publishConfig();
		$this->publishMigrations();
		$platform = config('streamable.stream_platform', 'kafka');
		if (!$platform) {
			Log::error('Cannot find the config for streamable package. Please check the configuration.');
			return;
		}
		
		$this->registerConnection(config('streamable.platforms.' . $platform . '.connection'));
		$this->registerMessage();
		$this->registerHandlers();
		$this->registerCommands();
	}
	
	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function boot(): void
	{
		$events = $this->app->get('events');
		$this->bootEvents($events);
		$this->bootSubscribers($events);
	}
	
	private function registerConnection($config): void
	{
		$this->app->singleton(
			RdKafkaConnectionFactory::class,
			fn(Application $application) => new RdKafkaConnectionFactory($config)
		);
	}
	
	private function registerMessage(): void
	{
		$this->app->bind(Message::class, fn(Application $app) => $app->make(RdKafkaMessage::class));
	}
	
	private function registerHandlers(): void
	{
		$this->app->bind(ProducerHandlerInterface::class, fn(Application $app) => $app->make(ProducerHandler::class));
		$this->app->bind(ConsumerHandlerInterface::class, fn(Application $app) => $app->make(ConsumerHandler::class));
	}
	
	private function registerCommands(): void
	{
		$this->commands($this->commands);
	}
	
	private function bootEvents($events): void
	{
		foreach ($this->listen as $event => $listeners) {
			foreach ($listeners as $listener) {
				$events->listen($event, $listener);
			}
		}
	}
	
	private function bootSubscribers($events): void
	{
		foreach ($this->subscribe as $subscriber) {
			$events->subscribe($subscriber);
		}
	}
	
	private function publishConfig(): void
	{
		$this->publishes([
			__DIR__ . '/../Configs/streamable/resources.php' => config_path('streamable/resources.php'),
		], );
        $this->publishes([
            __DIR__ . '/../Configs/streamable/platforms.php' => config_path('streamable/platforms.php'),
        ], );
		$this->publishes([
			__DIR__ . '/../Configs/streamable/ignore_objects.php' => config_path('streamable/ignore_objects.php'),
		], );
        $this->publishes([
            __DIR__ . '/../Configs/streamable.php' => config_path('streamable.php'),
        ], );
	}
	
	private function publishMigrations(): void
	{
		$this->publishes([__DIR__ . '/../Migrations/' => database_path('migrations')], 'migrations');
	}
}
