<?php

namespace App\Repositories;

use App\Models\AppSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AppSettingRepository
{
    public function tableAvailable(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    public function getGroup(string $group): Collection
    {
        if (! $this->tableAvailable()) {
            return collect();
        }

        return AppSetting::query()
            ->where('group', $group)
            ->get();
    }

    public function getByGroupKey(string $group, string $key): ?AppSetting
    {
        if (! $this->tableAvailable()) {
            return null;
        }

        return AppSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();
    }
}
