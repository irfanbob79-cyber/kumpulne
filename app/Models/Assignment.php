<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'classroom_id',
        'title',
        'slug',
        'description',
        'allowed_extensions',
        'max_file_size_mb',
        'deadline',
        'is_active',
    ];

    protected $casts = [
        'allowed_extensions' => 'array',
        'max_file_size_mb' => 'integer',
        'deadline' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isExpired(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }
}
