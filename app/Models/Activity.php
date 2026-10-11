<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Dziennik zmian panelu: kto, kiedy, co zrobił z licencją lub klientem. */
class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'license_id', 'customer_id', 'description'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function log(string $description, ?License $license = null, ?Customer $customer = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'license_id' => $license?->id,
            'customer_id' => $customer?->id ?? $license?->customer_id,
            'description' => $description,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
