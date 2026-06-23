<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Models\Central\CentralUser;
use App\Models\Central\Otp;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Manages One-Time Passwords (OTPs) for the central application.
 */
class OtpService
{
    /**
     * Generate a new OTP for a user.
     *
     * @param  CentralUser  $user
     * @param  string  $purpose
     * @param  int  $ttlMinutes
     * @return Otp
     */
    public function generate(CentralUser $user, string $purpose, int $ttlMinutes = 15): Otp
    {
        $this->invalidate($user, $purpose);

        return $user->otps()->create([
            'otp' => (string) random_int(100000, 999999),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    /**
     * Verify an OTP for a user.
     *
     * @param  CentralUser  $user
     * @param  string  $otp
     * @param  string  $purpose
     * @return bool
     */
    public function verify(CentralUser $user, string $otp, string $purpose): bool
    {
        $otpRecord = $user->otps()
            ->where('purpose', $purpose)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();

        if ($otpRecord === null) {
            return false;
        }

        $otpRecord->update(['used_at' => now()]);

        return true;
    }

    /**
     * Invalidate all existing OTPs for a user and purpose.
     *
     * @param  CentralUser  $user
     * @param  string  $purpose
     * @return void
     */
    public function invalidate(CentralUser $user, string $purpose): void
    {
        $user->otps()->where('purpose', $purpose)->update(['used_at' => now()]);
    }
}
