<?php

namespace App\Console\Commands;

use App\Http\Controllers\Traits\PushNotificationTrait;
use App\Models\AppUser;
use App\Models\AppUserMeta;
use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDriverRechargeNotifications extends Command
{
    use PushNotificationTrait;

    protected $signature = 'drivers:send-recharge-reminders';

    protected $description = 'Send automated recharge expiry reminders to drivers';

    public function handle(): int
    {
        $now = Carbon::now();
        $siteName = trim((string) (GeneralSetting::getMetaValue('general_name') ?: 'QRIDES'));

        $drivers = AppUser::where('user_type', 'driver')
            ->where('status', 1)
            ->where('host_status', 1)
            ->get();

        $sentCount = 0;

        foreach ($drivers as $driver) {
            [$metaKey, $metaValue, $title, $message, $data] = $this->buildNotificationPayload($driver, $now, $siteName);

            if (! $metaKey || ! $metaValue || ! $title || ! $message) {
                continue;
            }

            $alreadySent = AppUserMeta::where('user_id', $driver->id)
                ->where('meta_key', $metaKey)
                ->where('meta_value', $metaValue)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $deviceToken = trim((string) ($driver->fcm ?: ''));
            if ($deviceToken === '') {
                continue;
            }

            $sent = $this->sendFcmMessage($deviceToken, $title, $message, $data, 0, 'driver');
            if (! $sent) {
                continue;
            }

            AppUserMeta::updateOrCreate(
                [
                    'user_id' => $driver->id,
                    'meta_key' => $metaKey,
                ],
                [
                    'meta_value' => $metaValue,
                ]
            );

            $sentCount++;
        }

        $this->info("Driver recharge reminder run completed. Notifications sent: {$sentCount}");

        return self::SUCCESS;
    }

    private function buildNotificationPayload(AppUser $driver, Carbon $now, string $siteName): array
    {
        $driverName = trim((string) (($driver->first_name ?? '').' '.($driver->last_name ?? ''))) ?: 'Captain';
        $validUntilRaw = $driver->recharge_valid_until ? Carbon::parse($driver->recharge_valid_until) : null;

        if (! ((bool) $driver->recharge_active) || ! $validUntilRaw) {
            return [
                'driver_recharge_missing_notice',
                $now->toDateString(),
                'Recharge required',
                "{$driverName}, your {$siteName} recharge is inactive. Recharge now to continue receiving rides.",
                [
                    'error_key' => 'recharge_required',
                    'type' => 'recharge_missing',
                ],
            ];
        }

        if ($validUntilRaw->lt($now)) {
            if ((bool) $driver->recharge_active) {
                $driver->recharge_active = false;
                $driver->save();
            }

            return [
                'driver_recharge_expired_notice',
                $validUntilRaw->toDateTimeString(),
                'Recharge expired',
                "{$driverName}, your recharge expired on ".$validUntilRaw->format('d M Y, h:i A').". Recharge now to access rides again.",
                [
                    'error_key' => 'recharge_required',
                    'type' => 'recharge_expired',
                    'recharge_valid_until' => $validUntilRaw->toDateTimeString(),
                ],
            ];
        }

        $hoursRemaining = $now->diffInHours($validUntilRaw, false);

        if ($hoursRemaining <= 3) {
            return [
                'driver_recharge_expiring_3h_notice',
                $validUntilRaw->toDateTimeString(),
                'Recharge expiring soon',
                "{$driverName}, your recharge expires within 3 hours. Renew now to avoid ride interruption.",
                [
                    'error_key' => 'recharge_expiring',
                    'type' => 'recharge_expiring_3h',
                    'recharge_valid_until' => $validUntilRaw->toDateTimeString(),
                ],
            ];
        }

        if ($hoursRemaining <= 24) {
            return [
                'driver_recharge_expiring_24h_notice',
                $validUntilRaw->toDateTimeString(),
                'Recharge expires tomorrow',
                "{$driverName}, your recharge expires on ".$validUntilRaw->format('d M Y, h:i A').". Renew now to keep receiving rides.",
                [
                    'error_key' => 'recharge_expiring',
                    'type' => 'recharge_expiring_24h',
                    'recharge_valid_until' => $validUntilRaw->toDateTimeString(),
                ],
            ];
        }

        return [null, null, null, null, []];
    }
}
