<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $api_url
 * @property string|null $cert_sha256
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'api_url', 'cert_sha256'])]
class Server extends Model
{
    use HasFactory;

    /** @return BelongsToMany<User, $this> */
    public function sellers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'server_seller');
    }

    /** @return HasMany<AccessKey, $this> */
    public function accessKeys(): HasMany
    {
        return $this->hasMany(AccessKey::class);
    }
}
