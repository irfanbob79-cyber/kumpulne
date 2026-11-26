<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'original_filename',
        'renamed_filename',
        'relative_path',
        'file_size_bytes',
        'mime_type',
        'gdrive_file_id',
        'gdrive_web_view_link',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
