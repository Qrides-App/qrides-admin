<?php

namespace App\Http\Controllers\Traits;

use App\Jobs\SendAllNotificationsJob;
use App\Models\AppUser;
use App\Models\EmailSmsNotification;
use App\Models\GeneralSetting;

trait NotificationTrait
{
    use EmailTrait, MiscellaneousTrait, PushNotificationTrait, SMSTrait;

    public function sendAllNotifications($valuesArray, $user_id, $template_id, $data = ['key' => 'value'], $vendor_id = 0)
    {
        // Just dispatch the job
        SendAllNotificationsJob::dispatch($valuesArray, $user_id, $template_id, $data, $vendor_id);

        // $this->_sendAllNotificationsNow($valuesArray, $user_id, $template_id, $data, $vendor_id);
    }

    public function _sendAllNotificationsNow($valuesArray, $user_id, $template_id, $data = ['key' => 'value'], $vendor_id = 0)
    {

        $settings = GeneralSetting::whereIn('meta_key', [
            'push_notification_status',
            'general_name',
            'general_email',
        ])->get()->pluck('meta_value', 'meta_key')->toArray();

        $valuesArray['AppName'] = $settings['general_name'] ?? config('app.name');
        $valuesArray['website_name'] = $settings['general_name'] ?? config('app.name');
        $adminemail = $settings['general_email'] ?? config('mail.from.address');

        $user = AppUser::with('metadata')->find($user_id);
        $vendorData = null;
        if ($vendor_id > 0) {
            $vendorData = AppUser::with('metadata')->find($vendor_id);
        }
        $template = EmailSmsNotification::find($template_id);
        if (! $template) {
            \Log::warning('Notification template missing, skipping notification dispatch.', [
                'template_id' => $template_id,
                'user_id' => $user_id,
                'vendor_id' => $vendor_id,
            ]);
            return;
        }
        if ((int) $template->status === 0) {
            return;
        }
        $subject = $this->replaceTemplatePlaceholders($template->subject, $valuesArray);
        $adminsubject = $subject;
        $vendorsubject = $subject;
        $testing = '';

        $message = $this->replaceTemplatePlaceholders($template->body, $valuesArray).$testing;
        $smsMessage = $this->replaceTemplatePlaceholders($template->sms, $valuesArray).$testing;
        $pushMessage = $this->replaceTemplatePlaceholders($template->push_notification, $valuesArray).$testing;

        $adminmessage = $this->replaceTemplatePlaceholders($template->adminbody, $valuesArray).$testing;
        $adminsmsMessage = $this->replaceTemplatePlaceholders($template->adminsms, $valuesArray).$testing;
        $adminpushMessage = $this->replaceTemplatePlaceholders($template->adminpush_notification, $valuesArray).$testing;

        $vendormessage = $this->replaceTemplatePlaceholders($template->vendorbody, $valuesArray).$testing;
        $vendorsmsMessage = $this->replaceTemplatePlaceholders($template->vendorsms, $valuesArray).$testing;
        $vendorpushMessage = $this->replaceTemplatePlaceholders($template->vendorpush_notification, $valuesArray).$testing;

        if ($template->adminsubject != 'null') {
            $adminsubject = $this->replaceTemplatePlaceholders($template->adminsubject, $valuesArray).$testing;
        }
        if ($template->vendorsubject != 'null') {
            $vendorsubject = $this->replaceTemplatePlaceholders($template->vendorsubject, $valuesArray).$testing;
        }

        if ($template->smssent == 1) {

            if (isset($valuesArray['temp_phone']) && ! empty($valuesArray['temp_phone'])) {
                $phone = str_replace('+', '', $valuesArray['temp_phone']);
                $this->sendSMS($subject, $smsMessage, $valuesArray['temp_phone']);
            } elseif ($user) {
                $phone = str_replace('+', '', $user->phone_country).$user->phone;
                $this->sendSMS($subject, $smsMessage, $phone);
            }

        }
        if ($template->emailsent == 1) {
            if (isset($valuesArray['temp_email']) && ! empty($valuesArray['temp_email'])) {
                $this->sendMail($subject, $message, $valuesArray['temp_email']);
            } elseif ($user) {
                $this->sendMail($subject, $message, $user->email);
            }
        }
        if ($template->pushsent == 1 && $user) {
            $playerId = optional($user->metadata->firstWhere('meta_key', 'player_id'))->meta_value;
            $deviceToken = $user->fcm ?: $playerId;
            if (! empty($deviceToken)) {
                $this->sendFcmMessage($deviceToken, $subject, $pushMessage, $data);
            }
        }

        // For Admin
        if ($template->adminsmssent == 1) {

            $adminphone = $this->getGeneralSettingValue('general_phone');
            $this->sendSMS($adminsubject, $adminsmsMessage, $adminphone);
        }
        if ($template->adminemailsent == 1) {
            $this->sendMail($adminsubject, $adminmessage, $adminemail);
        }

        // For vendor
        if ($vendorData) {
            if ($template->vendorsmssent == 1) {
                $vendorphone = str_replace('+', '', $vendorData->phone_country).$vendorData->phone;
                $this->sendSMS($vendorsubject, $vendorsmsMessage, $vendorphone);
            }
            if ($template->vendoremailsent == 1) {
                $vendoremail = $vendorData->email;
                $this->sendMail($vendorsubject, $vendormessage, $vendoremail);
            }

            if ($template->vendorpushsent == 1) {
                $vendorNotification = 1;
                $playerId = optional($vendorData->metadata->firstWhere('meta_key', 'player_id'))->meta_value;
                $deviceToken = $vendorData->fcm ?: $playerId;
                if (! empty($deviceToken)) {
                    $this->sendFcmMessage($deviceToken, $vendorsubject, $vendorpushMessage, $data, $vendorNotification);
                }
            }
        }

    }

    private function replaceTemplatePlaceholders($templateString, $valuesArray)
    {
        foreach ($valuesArray as $key => $value) {
            $templateString = str_replace('{{'.$key.'}}', $value, $templateString);
        }

        return $templateString;
    }
}
