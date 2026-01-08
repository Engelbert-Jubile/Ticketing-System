<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'cast_type',
        'is_secret',
        'updated_by',
    ];

    protected $casts = [
        'is_secret' => 'boolean',
    ];

    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        $isSecret = (bool) $this->is_secret;
        if (! $isSecret && array_key_exists('is_encrypted', $this->attributes ?? [])) {
            $isSecret = (bool) $this->attributes['is_encrypted'];
        }

        $raw = $isSecret ? Crypt::decryptString($value) : $value;
        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    public function setValueAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['value'] = null;
            return;
        }

        try {
            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $encoded = json_encode((string) $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $this->attributes['value'] = $this->is_secret
            ? Crypt::encryptString($encoded)
            : $encoded;
    }
}
