<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    public const STATUSES = [
        'active' => 'Aktywna',
        'expiring' => 'Wygasa wkrótce',
        'expired' => 'Wygasła',
        'replaced' => 'Zastąpiona nowym kluczem',
        'revoked' => 'Unieważniona',
    ];

    protected $fillable = [
        'customer_id', 'domain', 'edition', 'issued_on', 'expires_on', 'note',
        'license_key', 'key_hash', 'issued_by',
    ];

    protected $hidden = ['license_key'];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_on' => 'date',
            'revoked_at' => 'datetime',
            'last_check_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function status(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'revoked',
            $this->replaced_by_id !== null => 'replaced',
            $this->expires_on !== null && $this->expires_on->lt(today()) => 'expired',
            $this->expires_on !== null && $this->expires_on->lte(today()->addDays((int) config('licensing.expiring_days'))) => 'expiring',
            default => 'active',
        };
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status()];
    }

    public function editionLabel(): string
    {
        return config("licensing.editions.{$this->edition}", $this->edition);
    }

    /** Czy klucz jeszcze „żyje” — można go przedłużyć/unieważnić. */
    public function isCurrent(): bool
    {
        return $this->revoked_at === null && $this->replaced_by_id === null;
    }

    public function scopeInStatus(Builder $query, string $status): Builder
    {
        $soon = today()->addDays((int) config('licensing.expiring_days'));

        return match ($status) {
            'revoked' => $query->whereNotNull('revoked_at'),
            'replaced' => $query->whereNull('revoked_at')->whereNotNull('replaced_by_id'),
            'expired' => $query->whereNull('revoked_at')->whereNull('replaced_by_id')->whereDate('expires_on', '<', today()),
            'expiring' => $query->whereNull('revoked_at')->whereNull('replaced_by_id')
                ->whereDate('expires_on', '>=', today())->whereDate('expires_on', '<=', $soon),
            'active' => $query->whereNull('revoked_at')->whereNull('replaced_by_id')
                ->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>', $soon)),
            default => $query,
        };
    }
}
