<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum OnboardingStep: string
{
    case BusinessInfo = 'business_info';
    case StoreSetup = 'store_setup';
    case Branding = 'branding';
    case Email = 'email';
    case Notifications = 'notifications';
    case Invoice = 'invoice';
    case Complete = 'complete';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::BusinessInfo,
            self::StoreSetup,
            self::Branding,
            self::Email,
            self::Notifications,
            self::Invoice,
            self::Complete,
        ];
    }

    public function next(): ?self
    {
        $ordered = self::ordered();
        $index = array_search($this, $ordered, true);

        if ($index === false || $index === count($ordered) - 1) {
            return null;
        }

        return $ordered[$index + 1];
    }
}
