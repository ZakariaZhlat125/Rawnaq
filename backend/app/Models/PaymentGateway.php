<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    protected $fillable = [
        'code',
        'driver',
        'name_ar',
        'name_en',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(PaymentGatewayField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function name(): string
    {
        return app()->getLocale() === 'en' && $this->name_en ? $this->name_en : $this->name_ar;
    }

    /** Field values keyed by field key, decrypted. */
    public function credentials(): array
    {
        return $this->fields->mapWithKeys(fn (PaymentGatewayField $field) => [$field->key => $field->value])->all();
    }

    /** Keys of required fields that have no value yet. */
    public function missingFields(): array
    {
        return $this->fields
            ->filter(fn (PaymentGatewayField $field) => $field->is_required && ! filled($field->value))
            ->pluck('key')
            ->values()
            ->all();
    }

    /** Attempts still waiting for the gateway; their callbacks need this row. */
    public function hasPendingAttempts(): bool
    {
        return PaymentAttempt::where('gateway', $this->code)
            ->whereIn('status', [PaymentAttempt::STATUS_INITIATED, PaymentAttempt::STATUS_REDIRECTED])
            ->exists();
    }
}
