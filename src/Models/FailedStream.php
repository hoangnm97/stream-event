<?php

namespace Hoangdev\StreamEventDriven\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FailedStream extends Model
{
    use SoftDeletes;

    public const STREAM_VIA_KAFKA = 'kafka';

    public const HANDLE_PRODUCE = 0;
    public const HANDLE_CONSUME = 1;

    public $timestamps = false;

    protected $casts = [
        'body' => 'array',
        'failed_at' => 'datetime',
        'headers' => 'array',
        'properties' => 'array',
    ];

    protected $fillable = [
        'platform',
        'body',
        'properties',
        'headers',
        'topic',
        'exception',
        'handle',
    ];

    protected static function booted()
    {
        parent::booted();
        static::creating(
            function (self $model): void {
                $model->failed_at = $model->freshTimestamp();
            }
        );
    }

    public function usesTimestamps(): false
	{
        return false;
    }

    public function isFailedOnProduce(): bool
    {
        return self::HANDLE_PRODUCE === $this->handle;
    }

    public function isFailedOnConsume(): bool
    {
        return self::HANDLE_CONSUME === $this->handle;
    }
}
