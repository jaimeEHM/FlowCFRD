<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}

