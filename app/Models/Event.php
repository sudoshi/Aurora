<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'time',
        'duration',
        'location',
        'category',
        'description',
        'team',
        'patients',
        'related_items'
    ];

    protected $casts = [
        'team' => 'array',
        'patients' => 'array',
        'related_items' => 'array',
    ];
}
