<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Storage for documents that must never be publicly reachable: Aadhaar, PAN,
 * land records, bank statements, dealer licences.
 *
 * Files live on the `private` disk, outside the webroot. The only way to read
 * one is a signed URL that expires in minutes and is checked against a policy
 * on the way through — see DocumentController.
 */
class DocumentVault
{
    public const DISK = 'private';

    public const LINK_TTL_MINUTES = 5;

    /** @return string the stored path, to be saved on the owning row */
    public function store(UploadedFile $file, string $folder): string
    {
        $name = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin');

        return $file->storeAs(trim($folder, '/'), $name, self::DISK);
    }

    public function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path);
    }

    public function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }

    /** A link that stops working in minutes, so it cannot be forwarded usefully. */
    public function temporaryUrl(string $routeName, array $parameters): string
    {
        return URL::temporarySignedRoute($routeName, now()->addMinutes(self::LINK_TTL_MINUTES), $parameters);
    }

    /** Downloads are logged: who opened whose documents, and when. */
    public function logAccess(string $path, ?int $userId, string $context): void
    {
        Log::channel(config('kj.documents.audit_channel', 'stack'))->info('Private document accessed', [
            'path' => $path,
            'user_id' => $userId,
            'context' => $context,
            'at' => now()->toIso8601String(),
        ]);
    }

    /**
     * KYC identifiers are stored masked; the full value is never persisted.
     * "ABCDE1234F" becomes "XXXXXX234F".
     */
    public static function mask(?string $value, int $visible = 4): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = strtoupper(preg_replace('/\s+/', '', $value));
        $length = strlen($value);

        if ($length <= $visible) {
            return str_repeat('X', $length);
        }

        return str_repeat('X', $length - $visible).substr($value, -$visible);
    }
}
