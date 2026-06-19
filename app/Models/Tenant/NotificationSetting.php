<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Concerns\Tenant\HasSingletonRecord;
use App\Enums\Tenant\NotificationEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification channel preferences for a tenant store.
 */
class NotificationSetting extends Model
{
    use HasSingletonRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'admin_alerts_enabled',
        'channels',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'admin_alerts_enabled' => 'boolean',
            'channels' => 'array',
        ];
    }

    /**
     * Resolve delivery channels for a notification event.
     *
     * @param  NotificationEvent  $event
     * @param  bool  $adminAlert
     * @return list<string>
     */
    public function channelsFor(NotificationEvent $event, bool $adminAlert = false): array
    {
        if ($adminAlert && ! $this->admin_alerts_enabled) {
            return [];
        }

        $eventChannels = $this->channels[$event->value] ?? null;

        if ($eventChannels === false) {
            return [];
        }

        $allowed = is_array($eventChannels)
            ? $eventChannels
            : ['mail', 'database'];

        $resolved = [];

        if (in_array('mail', $allowed, true) && $this->email_enabled) {
            $resolved[] = 'mail';
        }

        if (in_array('database', $allowed, true) && $this->push_enabled) {
            $resolved[] = 'database';
        }

        if (in_array('sms', $allowed, true) && $this->sms_enabled) {
            $resolved[] = 'sms';
        }

        return $resolved;
    }
}
