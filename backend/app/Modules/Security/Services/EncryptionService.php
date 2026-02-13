<?php

namespace App\Modules\Security\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    /**
     * Encrypt sensitive data (API tokens, secrets).
     */
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt sensitive data.
     */
    public function decrypt(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            Log::error('Decryption failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to decrypt sensitive data.');
        }
    }

    /**
     * Hash a value for comparison (e.g., webhook secrets).
     */
    public function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Verify a hash.
     */
    public function verifyHash(string $value, string $hash): bool
    {
        return hash_equals($hash, hash('sha256', $value));
    }

    /**
     * Mask sensitive data for display.
     */
    public function mask(string $value, int $visibleChars = 4): string
    {
        if (strlen($value) <= $visibleChars) {
            return str_repeat('*', strlen($value));
        }

        return str_repeat('*', strlen($value) - $visibleChars) . substr($value, -$visibleChars);
    }
}
