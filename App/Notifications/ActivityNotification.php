<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    use Queueable;

    /** @param array{title:string,message:string,url?:string,icon?:string,by?:string,subject_type?:string,subject_id?:string|int} $payload */
    public function __construct(private array $payload) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->payload['title'] ?? 'Activity',
            'message' => $this->payload['message'] ?? '',
            'url' => $this->payload['url'] ?? null,
            'icon' => $this->payload['icon'] ?? 'notifications',
            'by' => $this->payload['by'] ?? null,
            'subject_type' => $this->payload['subject_type'] ?? null,
            'subject_id' => $this->payload['subject_id'] ?? null,
        ];
    }
}
