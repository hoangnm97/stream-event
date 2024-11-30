<?php

namespace Softel\StreamEventDriven\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UnhandledMessage extends Model
{
    protected $fillable = [
        'topic',
		'action',
		'object',
		'body',
        'headers',
        'properties',
	];

    protected static function booted()
    {
        parent::booted();
        static::creating(
            function (self $model): void {
                $model->id = Str::uuid();
            }
        );
    }
}
