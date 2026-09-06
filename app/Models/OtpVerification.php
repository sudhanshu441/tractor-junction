<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications';

    protected $fillable = [
        'mobile', 'otp_hash', 'purpose', 'attempts', 'expires_at', 'verified_at', 'ip',
    ];

    protected $hidden = ['otp_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function scopeUsable(Builder $query, string $mobile, string $purpose): Builder
    {
        return $query->where('mobile', $mobile)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
