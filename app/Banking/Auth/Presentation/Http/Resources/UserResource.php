<?php

namespace App\Banking\Auth\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->resource;

        return [
            'id' => $user->id,
            'public_id' => $user->public_id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'must_change_password' => (bool) $user->must_change_password,
            'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values() : [],
            'permissions' => method_exists($user, 'getAllPermissions')
                ? $user->getAllPermissions()->pluck('name')->values()
                : [],
        ];
    }
}
