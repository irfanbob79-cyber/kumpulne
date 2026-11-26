<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriveAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_name',
        'google_email',
        'client_id',
        'client_secret',
        'refresh_token',
        'root_folder_id',
        'storage_used_bytes',
        'storage_limit_bytes',
        'priority',
        'is_active',
        'is_full',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_full' => 'boolean',
        'storage_used_bytes' => 'integer',
        'storage_limit_bytes' => 'integer',
        'priority' => 'integer',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
