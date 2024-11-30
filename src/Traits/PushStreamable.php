<?php

namespace Softel\StreamEventDriven\Traits;

use Softel\StreamEventDriven\Contracts\StreamableResourceInterface;
use Softel\StreamEventDriven\Events\StreamCreated;
use Throwable;

/**
 * @method static deleted(\Closure $param)
 */
trait PushStreamable
{
    /**
     * @throws Throwable
     */
    public static function bootPushStreamable(): void
    {
        static::created(
            static fn(StreamableResourceInterface $instance) => $instance->pushStream(
                StreamableResourceInterface::ACTION_CREATE
            )
        );
        static::deleted(
            static fn(StreamableResourceInterface $instance) => $instance->pushStream(
                StreamableResourceInterface::ACTION_DELETE
            )
        );
        static::updated(
            static fn(StreamableResourceInterface $instance) => $instance->pushStream(
                StreamableResourceInterface::ACTION_UPDATE
            )
        );
    }
    
    public function pushStream(string $action): void
    {
        event(new StreamCreated($this, $action));
    }
    
    
    public function buildMessage(): string
    {
        return $this->toJson();
    }
    
    public function getSuffixTopic(): string
    {
        return "";
    }
    
}
