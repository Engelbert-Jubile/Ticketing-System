<?php

namespace App\Support;

final class NotificationText
{
    public static function display(?string $text): ?string
    {
        if ($text === null) return null;

        if (preg_match('/^Ticket berisi (Task & Project|Task|Project|Work Item)$/', $text, $matches)) {
            return __('notifications.contains_title', ['type' => $matches[1]]);
        }
        if (preg_match('/^Ticket "(.*)" memuat (Task & Project|Task|Project|Work Item)\.$/s', $text, $matches)) {
            return __('notifications.contains_message', ['title' => $matches[1], 'type' => $matches[2]]);
        }
        if (preg_match('/^Ticket "(.*)" diarahkan menjadi (Task|Project)\.$/s', $text, $matches)) {
            return __('notifications.routed_message', ['title' => $matches[1], 'type' => $matches[2]]);
        }

        // Translate system-generated templates, preserving the captured user title.
        // Existing notification records do not need to be rewritten when locale changes.
        $patterns = [
            '/^Anda ditugaskan pada (ticket|task): (.*)$/s' => ':type assigned to you: :title',
            '/^Anda ditetapkan pada (project): (.*)$/s' => ':type assigned to you: :title',
            '/^Pengingat (ticket|task|project): (.*)$/s' => ':type reminder: :title',
            '/^(Ticket|Task|Project) "(.*)" dibatalkan\.$/s' => ':type ":title" was cancelled.',
            '/^(Ticket|Task|Project) "(.*)" telah dibuat\.$/s' => ':type ":title" was created.',
            '/^(Ticket|Task|Project) assigned to you: (.*)$/s' => ':type assigned to you: :title',
            '/^(Ticket|Task|Project) reminder: (.*)$/s' => ':type reminder: :title',
            '/^(Ticket|Task|Project) "(.*)" was cancelled\.$/s' => ':type ":title" was cancelled.',
            '/^(Ticket|Task|Project) "(.*)" was created\.$/s' => ':type ":title" was created.',
        ];
        foreach ($patterns as $pattern => $key) {
            if (preg_match($pattern, $text, $matches)) {
                return __($key, ['type' => ucfirst($matches[1]), 'title' => $matches[2]]);
            }
        }
        if (preg_match('/^(?:Anda disebut di ticket |You were mentioned in ticket )(.*)$/s', $text, $matches)) {
            return __('You were mentioned in ticket :number', ['number' => $matches[1]]);
        }

        return __($text);
    }
}
