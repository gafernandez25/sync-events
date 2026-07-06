<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'provider',
        'external_base_plan_id',
        'external_plan_id',
        'title',
        'starts_at',
        'ends_at',
        'min_price',
        'max_price',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'min_price' => 'float',
            'max_price' => 'float',
        ];
    }
}
