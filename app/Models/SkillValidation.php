<?php

namespace App\Models;

use Database\Factories\SkillValidationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillValidation extends Model
{
    /** @use HasFactory<SkillValidationFactory> */
    use HasFactory;

    protected $fillable = [
        'skill_id',
        'subject_user_id',
        'validator_user_id',
        'status',
        'comment',
    ];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_user_id');
    }
}
