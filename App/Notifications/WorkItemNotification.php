<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class WorkItemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array{
     *   type_label:string,
     *   title:string,
     *   url?:string,
     *   icon?:string,
     *   db_title:string,
     *   db_message:string,
     *   subject_type:string,
     *   subject_id:int|string,
     *   mail_subject:string,
     *   mail_intro:string,
     *   mail_remark?:string|null,
     *   actor?:string|null,
     *   details:array<string,string|null>,
     *   related?:array<string,string|null>
     * } $payload
     */
    public function __construct(
        private string $event,
        private array $payload,
        private bool $sendMail = true
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($this->sendMail && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->payload['mail_subject'])
            ->greeting('Halo '.($notifiable->display_name ?? $notifiable->name ?? 'Rekan'))
            ->line($this->payload['mail_intro']);

        $details = Arr::get($this->payload, 'details', []);
        if (! empty($details)) {
            $mail->line('Ringkasan:');
            foreach ($details as $label => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $mail->line('• '.$label.': '.$value);
            }
        }

        $related = Arr::get($this->payload, 'related', []);
        if (! empty($related)) {
            $mail->line('Terkait:');
            foreach ($related as $label => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $mail->line('• '.$label.': '.$value);
            }
        }

        if (! empty($this->payload['url'])) {
            $mail->action('Lihat '.$this->payload['type_label'], $this->payload['url']);
        }

        if (! empty($this->payload['mail_remark'])) {
            $mail->line($this->payload['mail_remark']);
        }

        $actor = $this->payload['actor'] ?? null;
        if ($actor) {
            $mail->line('Pengirim: '.$actor);
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->payload['db_title'],
            'message' => $this->payload['db_message'],
            'url' => $this->payload['url'] ?? null,
            'icon' => $this->payload['icon'] ?? 'notifications',
            'by' => $this->payload['actor'] ?? null,
            'subject_type' => $this->payload['subject_type'],
            'subject_id' => $this->payload['subject_id'],
            'event' => $this->event,
        ];
    }
}
