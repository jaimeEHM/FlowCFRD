<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PmoMacroVisibilityRule extends Model
{
    protected $fillable = [
        'item_key',
        'allowed_roles',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'allowed_roles' => 'array',
        ];
    }
}
