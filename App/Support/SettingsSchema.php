<?php

namespace App\Support;

class SettingsSchema
{
    public static function definitions(): array
    {
        return [
            'general' => [
                'app_name' => [
                    'type' => 'string',
                    'default' => config('app.name'),
                    'rules' => ['required', 'string', 'max:80'],
                ],
                'app_logo_path' => [
                    'type' => 'string',
                    'default' => null,
                    'rules' => ['nullable', 'string', 'max:255'],
                ],
                'timezone' => [
                    'type' => 'string',
                    'default' => config('app.timezone'),
                    'rules' => ['required', 'string', 'max:60'],
                ],
                'date_format' => [
                    'type' => 'string',
                    'default' => 'd/m/Y',
                    'rules' => ['required', 'string', 'max:20'],
                ],
                'locale' => [
                    'type' => 'string',
                    'default' => config('app.locale', 'en'),
                    'rules' => ['required', 'string', 'max:10'],
                ],
                'default_page_size' => [
                    'type' => 'integer',
                    'default' => 10,
                    'rules' => ['required', 'integer', 'min:5', 'max:200'],
                ],
                'maintenance_enabled' => [
                    'type' => 'boolean',
                    'default' => false,
                    'rules' => ['boolean'],
                ],
                'maintenance_message' => [
                    'type' => 'string',
                    'default' => null,
                    'rules' => ['nullable', 'string', 'max:180'],
                ],
                'announcement_enabled' => [
                    'type' => 'boolean',
                    'default' => false,
                    'rules' => ['boolean'],
                ],
                'announcement_title' => [
                    'type' => 'string',
                    'default' => null,
                    'rules' => ['nullable', 'string', 'max:120'],
                ],
                'announcement_body' => [
                    'type' => 'string',
                    'default' => null,
                    'rules' => ['nullable', 'string', 'max:500'],
                ],
                'announcement_starts_at' => [
                    'type' => 'date',
                    'default' => null,
                    'rules' => ['nullable', 'date'],
                ],
                'announcement_ends_at' => [
                    'type' => 'date',
                    'default' => null,
                    'rules' => ['nullable', 'date', 'after_or_equal:announcement_starts_at'],
                ],
            ],
            'security' => [
                'enforce_ip_restrictions' => [
                    'type' => 'boolean',
                    'default' => false,
                    'rules' => ['boolean'],
                ],
                'allow_superadmin_ip_bypass' => [
                    'type' => 'boolean',
                    'default' => true,
                    'rules' => ['boolean'],
                ],
                'session_timeout_minutes' => [
                    'type' => 'integer',
                    'default' => (int) config('session.idle_timeout', 30),
                    'rules' => ['required', 'integer', 'min:1', 'max:1440'],
                ],
                'max_login_attempts' => [
                    'type' => 'integer',
                    'default' => 5,
                    'rules' => ['required', 'integer', 'min:1', 'max:20'],
                ],
                'lockout_minutes' => [
                    'type' => 'integer',
                    'default' => 15,
                    'rules' => ['required', 'integer', 'min:1', 'max:240'],
                ],
                'password_min_length' => [
                    'type' => 'integer',
                    'default' => 8,
                    'rules' => ['required', 'integer', 'min:6', 'max:64'],
                ],
                'password_require_uppercase' => [
                    'type' => 'boolean',
                    'default' => false,
                    'rules' => ['boolean'],
                ],
                'password_require_number' => [
                    'type' => 'boolean',
                    'default' => true,
                    'rules' => ['boolean'],
                ],
                'password_require_symbol' => [
                    'type' => 'boolean',
                    'default' => false,
                    'rules' => ['boolean'],
                ],
                'require_2fa' => [
                    'type' => 'boolean',
                    'default' => false,
                    'rules' => ['boolean'],
                ],
                'allow_impersonation' => [
                    'type' => 'boolean',
                    'default' => false,
                    'rules' => ['boolean'],
                ],
                'ip_allowlist' => [
                    'type' => 'array',
                    'default' => [],
                    'rules' => ['nullable', 'array'],
                    'item_rules' => ['string', 'max:64'],
                ],
                'ip_blocklist' => [
                    'type' => 'array',
                    'default' => [],
                    'rules' => ['nullable', 'array'],
                    'item_rules' => ['string', 'max:64'],
                ],
                'allowed_email_domains' => [
                    'type' => 'array',
                    'default' => ['kftd.co.id'],
                    'rules' => ['nullable', 'array'],
                    'item_rules' => ['string', 'max:120'],
                ],
            ],
            'notifications' => [
                'smtp_host' => [
                    'type' => 'string',
                    'default' => null,
                    'rules' => ['nullable', 'string', 'max:190'],
                ],
                'smtp_port' => [
                    'type' => 'integer',
                    'default' => 587,
                    'rules' => ['nullable', 'integer', 'min:1', 'max:65535'],
                ],
                'smtp_username' => [
                    'type' => 'string',
                    'default' => null,
                    'rules' => ['nullable', 'string', 'max:190'],
                ],
                'smtp_password' => [
                    'type' => 'string',
                    'default' => null,
                    'rules' => ['nullable', 'string', 'max:190'],
                    'secret' => true,
                    'clear_flag' => 'smtp_password_clear',
                ],
                'smtp_encryption' => [
                    'type' => 'string',
                    'default' => 'tls',
                    'rules' => ['nullable', 'string', 'in:none,tls,ssl'],
                ],
                'notify_ticket_created' => [
                    'type' => 'boolean',
                    'default' => true,
                    'rules' => ['boolean'],
                ],
                'notify_ticket_assigned' => [
                    'type' => 'boolean',
                    'default' => true,
                    'rules' => ['boolean'],
                ],
                'notify_ticket_status_changed' => [
                    'type' => 'boolean',
                    'default' => true,
                    'rules' => ['boolean'],
                ],
            ],
            'defaults' => [
                'ticket_default_status' => [
                    'type' => 'string',
                    'default' => 'new',
                    'rules' => ['required', 'string', 'max:40'],
                ],
                'task_default_status' => [
                    'type' => 'string',
                    'default' => 'new',
                    'rules' => ['required', 'string', 'max:40'],
                ],
                'project_default_status' => [
                    'type' => 'string',
                    'default' => 'new',
                    'rules' => ['required', 'string', 'max:40'],
                ],
                'default_priority' => [
                    'type' => 'string',
                    'default' => 'medium',
                    'rules' => ['required', 'string', 'max:20'],
                ],
                'default_sla_hours' => [
                    'type' => 'integer',
                    'default' => 24,
                    'rules' => ['required', 'integer', 'min:1', 'max:720'],
                ],
                'ticket_numbering_format' => [
                    'type' => 'string',
                    'default' => 'TIC-{YYYY}-{####}',
                    'rules' => ['required', 'string', 'max:60'],
                ],
                'task_numbering_format' => [
                    'type' => 'string',
                    'default' => 'TSK-{YYYY}-{####}',
                    'rules' => ['required', 'string', 'max:60'],
                ],
                'project_numbering_format' => [
                    'type' => 'string',
                    'default' => 'PRJ-{YYYY}-{####}',
                    'rules' => ['required', 'string', 'max:60'],
                ],
                'auto_assign_enabled' => [
                    'type' => 'boolean',
                    'default' => false,
                    'rules' => ['boolean'],
                ],
                'auto_assign_role' => [
                    'type' => 'string',
                    'default' => null,
                    'rules' => ['nullable', 'string', 'max:40'],
                ],
                'auto_assign_strategy' => [
                    'type' => 'string',
                    'default' => 'round_robin',
                    'rules' => ['required', 'string', 'in:round_robin,least_load'],
                ],
            ],
        ];
    }

    public static function group(string $group): array
    {
        $definitions = self::definitions();

        return $definitions[$group] ?? [];
    }
}
