<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Support\SettingsSchema;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = SettingsSchema::definitions();

        foreach ($definitions as $group => $items) {
            foreach ($items as $key => $definition) {
                $isSecret = ! empty($definition['secret']);
                $default = $definition['default'] ?? null;

                AppSetting::firstOrCreate(
                    ['group' => $group, 'key' => $key],
                    [
                        'value' => $default,
                        'type' => $definition['type'] ?? null,
                        'cast_type' => $definition['type'] ?? null,
                        'is_secret' => $isSecret,
                        'updated_by' => null,
                    ]
                );
            }
        }
    }
}
