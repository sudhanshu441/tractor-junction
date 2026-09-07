<?php

namespace App\Domain\Notification;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * One entry point for every outbound message.
 *
 * Templates live in the database so operations can reword an SMS without a
 * deploy; this class renders the placeholders and fans out to the enabled
 * channels. A gateway failure is logged, never thrown — a notification must
 * not break the business action that triggered it.
 */
class NotificationDispatcher
{
    public function __construct(private readonly SmsManager $sms) {}

    /**
     * @param  array<string, string|int|null>  $variables
     */
    public function send(string $eventKey, ?User $user, array $variables = []): void
    {
        if (! $user) {
            return;
        }

        $template = $this->template($eventKey);

        if (! $template || ! $template->is_active) {
            return;
        }

        $channels = (array) $template->channels;

        if (in_array('sms', $channels, true) && $user->mobile) {
            $this->sendSms($template, $user, $variables);
        }

        if (in_array('database', $channels, true)) {
            $this->storeInApp($template, $user, $variables);
        }
    }

    /** Notify several people of the same event (a moderation queue, say). */
    public function sendMany(string $eventKey, iterable $users, array $variables = []): void
    {
        foreach ($users as $user) {
            $this->send($eventKey, $user, $variables);
        }
    }

    private function sendSms(NotificationTemplate $template, User $user, array $variables): void
    {
        $body = $this->render($template->sms_body, $variables);

        if ($body === '') {
            return;
        }

        try {
            $this->sms->send($user->mobile, $body, null, $user->id);
        } catch (\Throwable $e) {
            Log::error('Notification SMS failed', ['event' => $template->event_key, 'error' => $e->getMessage()]);
        }
    }

    private function storeInApp(NotificationTemplate $template, User $user, array $variables): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $template->event_key,
            'data' => json_encode([
                'title' => $this->render($template->email_subject ?: $template->name, $variables),
                'body' => $this->render($template->sms_body ?: '', $variables),
                'variables' => $variables,
            ]),
        ]);

        NotificationLog::create([
            'notification_template_id' => $template->id,
            'user_id' => $user->id,
            'channel' => 'database',
            'recipient' => (string) $user->id,
            'payload' => $this->render($template->sms_body ?: $template->name, $variables),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /** Replaces {placeholders}; an unknown one is emptied rather than left raw. */
    private function render(?string $body, array $variables): string
    {
        if (! $body) {
            return '';
        }

        $rendered = preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($variables) {
            return (string) ($variables[$m[1]] ?? '');
        }, $body);

        return trim(preg_replace('/\s+/', ' ', (string) $rendered));
    }

    private function template(string $eventKey): ?NotificationTemplate
    {
        $templates = Cache::remember('notification.templates', now()->addHour(),
            fn () => NotificationTemplate::all()->keyBy('event_key')->map(fn ($t) => $t->only([
                'id', 'event_key', 'name', 'channels', 'sms_body', 'email_subject', 'email_body', 'is_active',
            ]))->all());

        $row = $templates[$eventKey] ?? null;

        if (! $row) {
            return null;
        }

        // Hydrated from a cached array rather than mass-assigned, so the primary
        // key survives and notification_logs can reference the template.
        $template = new NotificationTemplate;
        $template->forceFill($row);
        $template->exists = true;

        return $template;
    }
}
