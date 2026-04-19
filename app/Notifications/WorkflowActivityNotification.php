<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notificación de dominio Workflow (bandeja `notifications` + datos para el front).
 * Sincrónica (sin cola) para que la bandeja refleje el cambio al instante.
 */
class WorkflowActivityNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $kind,
        public string $title,
        public string $body,
        public array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'meta' => $this->meta,
        ];
    }
}
