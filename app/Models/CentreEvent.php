<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentreEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'event_date',
        'event_time',
        'venue',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'event_time' => 'datetime:H:i',
        ];
    }
}
