<?php

namespace App\Policies;

use App\Models\AccessKey;
use App\Models\Server;
use App\Models\User;

class AccessKeyPolicy
{
    /**
     * Seller can view keys on servers assigned to them.
     * Admin can view any key.
     */
    public function viewAny(User $user, Server $server): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->servers()->where('servers.id', $server->id)->exists();
    }

    /**
     * Seller can only create keys on their assigned servers.
     * Admin can create on any server.
     */
    public function create(User $user, Server $server): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->servers()->where('servers.id', $server->id)->exists();
    }

    /**
     * Seller can update only their own keys; admin can update any.
     */
    public function update(User $user, AccessKey $accessKey): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $accessKey->created_by === $user->id
            && $user->servers()->where('servers.id', $accessKey->server_id)->exists();
    }

    /**
     * Seller can delete only their own keys; admin can delete any.
     */
    public function delete(User $user, AccessKey $accessKey): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $accessKey->created_by === $user->id
            && $user->servers()->where('servers.id', $accessKey->server_id)->exists();
    }
}
