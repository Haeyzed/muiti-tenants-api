<?php

declare(strict_types=1);

namespace App\Notifications\Central;

use App\Models\Central\Tenant;
use App\Models\Tenant\TenantUser;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends store-owner login credentials after tenant provisioning.
 */
class TenantOwnerCredentialsNotification extends Notification
{

    public function __construct(
        public Tenant $tenant,
        public TenantUser $user,
        public string $plainPassword,
        public string $loginUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your '.$this->tenant->name.' store account is ready')
            ->greeting('Hello '.$this->user->name.',')
            ->line('Your store has been provisioned on the platform.')
            ->line('Store: '.$this->tenant->name)
            ->line('Login email: '.$this->user->email)
            ->line('Temporary password: '.$this->plainPassword)
            ->action('Sign in to your store', $this->loginUrl)
            ->line('Please change your password after your first login.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tenant_owner_credentials',
            'tenant_id' => $this->tenant->id,
            'user_email' => $this->user->email,
        ];
    }
}
