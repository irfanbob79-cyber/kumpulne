<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_code',
        'drive_account_id',
        'assignment_id',
        'classroom_id',
        'student_name',
        'upload_type',
        'file_count',
        'total_size_bytes',
        'gdrive_target_id',
        'gdrive_web_view_link',
        'is_late',
        'ip_address',
        'submitted_at',
    ];

    protected $casts = [
        'is_late' => 'boolean',
        'submitted_at' => 'datetime',
        'file_count' => 'integer',
        'total_size_bytes' => 'integer',
    ];

    public function driveAccount(): BelongsTo
    {
        return $this->belongsTo(DriveAccount::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubmissionItem::class);
    }
}
