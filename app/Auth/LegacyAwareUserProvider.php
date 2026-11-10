<?php

namespace App\Auth;

use App\Models\User;
use App\Services\LegacyWordPressPassword;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

final class LegacyAwareUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?AuthenticatableContract
    {
        $user = parent::retrieveByCredentials($credentials);

        if ($user) {
            return $user;
        }

        $identifier = $credentials['email'] ?? $credentials['phone'] ?? null;
        if (! is_string($identifier) || trim($identifier) === '') {
            return null;
        }

        return User::query()
            ->where('legacy_login', trim($identifier))
            ->where('is_active', true)
            ->first();
    }

    public function validateCredentials(AuthenticatableContract $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? null;
        $stored = $user->getAuthPassword();
        $legacyPasswords = app(LegacyWordPressPassword::class);

        if ($user instanceof User
            && is_string($plain)
            && is_string($stored)
            && $legacyPasswords->isLegacyHash($stored)) {
            if (! $legacyPasswords->verify($plain, $stored)) {
                return false;
            }

            // The User model's hashed cast upgrades the verified password.
            $user->forceFill(['password' => $plain])->save();

            return true;
        }

        return parent::validateCredentials($user, $credentials);
    }
}
