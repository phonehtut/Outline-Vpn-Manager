<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $server_id
 * @property int $created_by
 * @property string $outline_key_id
 * @property string $name
 * @property string|null $access_url
 * @property int|null $data_limit_bytes
 * @property int|null $usage_bytes
 * @property Carbon|null $last_active_at
 * @property int|null $peak_device_count
 * @property Carbon|null $peak_device_at
 * @property bool|null $detailed_metrics_supported
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['server_id', 'created_by', 'outline_key_id', 'name', 'access_url', 'data_limit_bytes', 'expires_at', 'usage_bytes', 'last_active_at', 'peak_device_count', 'peak_device_at', 'detailed_metrics_supported'])]
class AccessKey extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'data_limit_bytes' => 'integer',
            'usage_bytes' => 'integer',
            'last_active_at' => 'datetime',
            'peak_device_count' => 'integer',
            'peak_device_at' => 'datetime',
            'detailed_metrics_supported' => 'boolean',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExpiringSoon(): bool
    {
        return $this->expires_at !== null
            && ! $this->isExpired()
            && $this->expires_at->isBefore(now()->addDays(3));
    }

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
