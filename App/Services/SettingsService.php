<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\SettingsAuditLog;
use App\Models\User;
use App\Repositories\AppSettingRepository;
use App\Support\SettingsSchema;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(private AppSettingRepository $repository) {}

    public function getGroup(string $group): array
    {
        return $this->getGroupWithMeta($group)['values'];
    }

    public function getGroupWithMeta(string $group): array
    {
        return Cache::remember($this->cacheKey($group), self::CACHE_TTL_SECONDS, function () use ($group) {
            $schema = SettingsSchema::group($group);
            if (! $this->repository->tableAvailable()) {
                return [
                    'values' => $this->defaultsFromSchema($schema),
                    'meta' => ['secrets' => []],
                ];
            }

            $stored = $this->repository->getGroup($group)->keyBy('key');

            $values = [];
            $meta = [
                'secrets' => [],
            ];

            foreach ($schema as $key => $definition) {
                $setting = $stored->get($key);
                $value = $setting ? $setting->value : ($definition['default'] ?? null);
                $value = $this->castValue($value, $definition['type'] ?? 'string');

                if (! empty($definition['secret'])) {
                    $meta['secrets'][$key] = $setting && $setting->value !== null;
                    $values[$key] = '';
                } else {
                    $values[$key] = $value;
                }
            }

            return [
                'values' => $values,
                'meta' => $meta,
            ];
        });
    }

    public function get(string $group, string $key, $default = null)
    {
        $schema = SettingsSchema::group($group);
        if (! isset($schema[$key])) {
            return $default;
        }

        $data = $this->getGroupWithMeta($group);
        $value = $data['values'][$key] ?? null;

        if ($value === '' && isset($data['meta']['secrets'][$key])) {
            return $default;
        }

        return $value ?? $default;
    }

    public function getRaw(string $group, string $key, $default = null)
    {
        $schema = SettingsSchema::group($group);
        if (! isset($schema[$key])) {
            return $default;
        }

        if (! $this->repository->tableAvailable()) {
            return $schema[$key]['default'] ?? $default;
        }

        $setting = $this->repository->getByGroupKey($group, $key);

        if ($setting) {
            return $this->castValue($setting->value, $schema[$key]['type'] ?? 'string');
        }

        return $schema[$key]['default'] ?? $default;
    }

    public function updateGroup(string $group, array $payload, ?User $actor, ?Request $request = null): array
    {
        $schema = SettingsSchema::group($group);
        if (empty($schema)) {
            return ['changes' => []];
        }

        if (! $this->repository->tableAvailable()) {
            return ['changes' => []];
        }

        $rules = $this->validationRules($schema);
        $validated = validator($payload, $rules)->validate();

        $existing = $this->repository->getGroup($group)->keyBy('key');
        $changes = [];

        DB::transaction(function () use ($schema, $validated, $existing, &$changes, $group, $actor, $request) {
            foreach ($schema as $key => $definition) {
                if (! array_key_exists($key, $validated)) {
                    continue;
                }

                $isSecret = ! empty($definition['secret']);
                $clearFlag = $definition['clear_flag'] ?? null;
                $shouldClear = $clearFlag && ! empty($validated[$clearFlag]);

                $incoming = $validated[$key];
                if ($isSecret && ! $shouldClear && ($incoming === null || $incoming === '')) {
                    continue;
                }

                if ($shouldClear) {
                    $incoming = null;
                }

                $incoming = $this->castValue($incoming, $definition['type'] ?? 'string');
                $setting = $existing->get($key);
                $previous = $setting ? $setting->value : ($definition['default'] ?? null);
                $previous = $this->castValue($previous, $definition['type'] ?? 'string');

                if (! $this->valuesDiffer($previous, $incoming)) {
                    continue;
                }

                // Persist with updateOrCreate so the row always exists and uses group+key as identifiers
                $setting = AppSetting::updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    [
                        'type' => $definition['type'] ?? null,
                        'cast_type' => $definition['type'] ?? null,
                        'is_secret' => $isSecret,
                        'updated_by' => $actor?->id,
                        'value' => $incoming,
                    ]
                );

                $changes[$key] = [
                    'old' => $previous,
                    'new' => $incoming,
                    'secret' => $isSecret,
                ];
            }

            if (! empty($changes)) {
                foreach ($changes as $key => $diff) {
                    $this->logChange($group, $key, $diff, $actor, $request);
                }
            }
        });

        $this->clearCache($group);

        return ['changes' => $changes];
    }

    public function clearCache(string $group): void
    {
        Cache::forget($this->cacheKey($group));
    }

    private function validationRules(array $schema): array
    {
        $rules = [];

        foreach ($schema as $key => $definition) {
            $rules[$key] = $definition['rules'] ?? ['nullable'];

            if (! empty($definition['item_rules'])) {
                $rules[$key.'.*'] = $definition['item_rules'];
            }

            if (! empty($definition['clear_flag'])) {
                $rules[$definition['clear_flag']] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    private function defaultsFromSchema(array $schema): array
    {
        $values = [];
        foreach ($schema as $key => $definition) {
            $values[$key] = $definition['default'] ?? null;
        }

        return $values;
    }

    private function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
            case 'integer':
                return is_numeric($value) ? (int) $value : null;
            case 'array':
                if (! is_array($value)) {
                    return [];
                }
                $trimmed = array_map(function ($item) {
                    return is_string($item) ? trim($item) : $item;
                }, $value);
                $filtered = array_filter($trimmed, fn ($item) => $item !== null && $item !== '');
                $unique = array_unique($filtered);

                return array_values($unique);
            case 'date':
                if ($value instanceof Carbon) {
                    return $value->toDateString();
                }
                if (is_string($value) && trim($value) === '') {
                    return null;
                }
                try {
                    return Carbon::parse($value)->toDateString();
                } catch (\Throwable) {
                    return null;
                }
            default:
                return is_string($value) ? trim($value) : (string) $value;
        }
    }

    private function valuesDiffer($a, $b): bool
    {
        return json_encode($a) !== json_encode($b);
    }

    private function logChange(string $group, string $key, array $diff, ?User $actor, ?Request $request): void
    {
        $old = $diff['secret'] ? ($diff['old'] === null ? null : '[redacted]') : $diff['old'];
        $new = $diff['secret'] ? ($diff['new'] === null ? null : '[redacted]') : $diff['new'];

        SettingsAuditLog::create([
            'user_id' => $actor?->id,
            'action' => 'update',
            'group' => $group,
            'key' => $key,
            'old_value' => $old !== null ? json_encode($old, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'new_value' => $new !== null ? json_encode($new, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    private function cacheKey(string $group): string
    {
        return 'settings.group.'.$group;
    }
}
