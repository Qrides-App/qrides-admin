<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EmailTrait;
use App\Models\EmailSmsNotification;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Support\MailSettings;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends Controller
{
    use EmailTrait;

    public function template(Request $request, $id)
    {
        abort_if(Gate::denies('email_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $module = Module::where('default_module', '1')->first();
        $moduleId = $module?->id;

        $AllEmailRecord = EmailSmsNotification::with([
            'notificationMapping' => function ($query) use ($moduleId) {
                if ($moduleId) {
                    $query->where('module', $moduleId);
                }
                $query->with('emailType');
            },
        ])->where('status', 1)->get();

        $emaildata = EmailSmsNotification::where('id', $id)->first();

        if (is_null($emaildata)) {
            abort(404, 'Email template not found.');
        }

        $scope = $this->resolveScope($request);
        $branding = GeneralSetting::whereIn('meta_key', [
            'general_name',
            'general_email',
            'general_phone',
            'general_default_phone_country',
            'general_logo',
        ])->pluck('meta_value', 'meta_key')->toArray();
        $sampleData = $this->samplePreviewData($branding);
        $templateConfig = $this->templateConfig($emaildata, $scope);
        $preview = [
            'subject' => $this->replaceTemplatePlaceholders($templateConfig['subject'], $sampleData),
            'body' => $this->replaceTemplatePlaceholders($templateConfig['body'], $sampleData),
        ];

        return view('admin.email.index', compact('emaildata', 'AllEmailRecord', 'scope', 'preview', 'sampleData', 'branding', 'templateConfig'));
    }

    public function templatecreate(Request $request, $id)
    {
        if (Gate::denies('email_update')) {
            return redirect()->back()->with('error', "You don't have permission to perform this action.");
        }

        if ($request->type == 'vendor') {

            $emaildata = EmailSmsNotification::where('id', $id)->firstOrNew();

            $emailEnabled = $request->has('vendoremailsent') ? '1' : '0';
            $body = html_entity_decode($request->input('vendorbody'));

            $payload = [

                'vendorsubject' => $request->vendorsubject,
                'vendorbody' => $body,
                'vendoremailsent' => $emailEnabled,
            ];

            if ($request->exists('vendorsmssent')) {
                $payload['vendorsmssent'] = $request->has('vendorsmssent') ? '1' : '0';
            }
            if ($request->exists('vendorpushsent')) {
                $payload['vendorpushsent'] = $request->has('vendorpushsent') ? '1' : '0';
            }
            if ($request->exists('vendorsms')) {
                $payload['vendorsms'] = $request->vendorsms;
            }
            if ($request->exists('vendorpush_notification')) {
                $payload['vendorpush_notification'] = $request->vendorpush_notification;
            }

            $emaildata->fill($payload);

            $emaildata->save();

            return redirect()->route('vendor.email-templates', $id)->with('success', 'Updated successfully!');

            // return redirect()->route('vendor.email-templates', $id);
        }
        if ($request->type == 'admin') {

            $emaildata = EmailSmsNotification::where('id', $id)->firstOrNew();

            $emailEnabled = $request->has('adminemailsent') ? '1' : '0';
            $body = html_entity_decode($request->input('adminbody'));

            $emaildata->fill([

                'adminsubject' => $request->adminsubject,
                'adminemailsent' => $emailEnabled,
                'adminbody' => $body,

            ]);

            $emaildata->save();

            return redirect()->route('admin.email-templates', $id)->with('success', 'Updated successfully!');
        }
        $emaildata = EmailSmsNotification::where('id', $id)->firstOrNew();

        $emailEnabled = $request->has('emailsent') ? '1' : '0';
        $status = 1;
        $link_text = 'abc';
        $lang = 'en';
        $lang_id = 1;
        $body = html_entity_decode($request->input('body'));

        // Update or create the record
        $payload = [
            'subject' => $request->subject,
            'body' => $body,
            'link_text' => $link_text,
            'lang' => $lang,
            'lang_id' => $lang_id,
            'emailsent' => $emailEnabled,
            'status' => $status,
        ];
        if ($request->exists('smssent')) {
            $payload['smssent'] = $request->has('smssent') ? '1' : '0';
        }
        if ($request->exists('pushsent')) {
            $payload['pushsent'] = $request->has('pushsent') ? '1' : '0';
        }
        if ($request->exists('sms')) {
            $payload['sms'] = $request->sms;
        }
        if ($request->exists('push_notification')) {
            $payload['push_notification'] = $request->push_notification;
        }

        $emaildata->fill($payload);
        $emaildata->save();

        return redirect()->route('user.email-templates', $id)->with('success', 'Updated successfully!');
    }

    public function sendTestMail(Request $request, $id)
    {
        if (Gate::denies('email_update')) {
            return redirect()->back()->with('error', "You don't have permission to perform this action.");
        }

        EmailSmsNotification::findOrFail($id);

        $validated = $request->validate([
            'recipient_email' => 'required|email|max:190',
            'subject' => 'nullable|string|max:190',
            'body' => 'nullable|string',
        ]);

        $mailConfig = MailSettings::normalize();
        if (! MailSettings::isConfigured($mailConfig)) {
            return redirect()->back()->with('error', 'Mail configuration is incomplete or disabled. Update SMTP settings first.');
        }

        $branding = GeneralSetting::whereIn('meta_key', [
            'general_name',
            'general_email',
            'general_phone',
            'general_default_phone_country',
        ])->pluck('meta_value', 'meta_key')->toArray();
        $sampleData = $this->samplePreviewData($branding);

        $subject = $this->replaceTemplatePlaceholders(
            trim((string) ($validated['subject'] ?? '')) ?: (($branding['general_name'] ?? config('app.name')).' Test Mail'),
            $sampleData
        );
        $body = $this->replaceTemplatePlaceholders($validated['body'] ?? '', $sampleData);

        if (trim(strip_tags($body)) === '') {
            return redirect()->back()->with('error', 'Template body is empty. Add content before sending a test mail.');
        }

        $result = $this->sendMail($subject, $body, $validated['recipient_email']);

        if (str_starts_with($result, 'Mail sent successfully')) {
            return redirect()->back()->with('success', 'Template test mail sent to '.$validated['recipient_email'].'.');
        }

        return redirect()->back()->with('error', $result);
    }

    private function resolveScope(Request $request): string
    {
        if ($request->routeIs('vendor.*')) {
            return 'vendor';
        }

        if ($request->routeIs('admin.*')) {
            return 'admin';
        }

        return 'user';
    }

    private function templateConfig(EmailSmsNotification $emaildata, string $scope): array
    {
        return match ($scope) {
            'vendor' => [
                'subject' => (string) ($emaildata->vendorsubject ?? ''),
                'body' => (string) ($emaildata->vendorbody ?? ''),
                'email_enabled' => (bool) $emaildata->vendoremailsent,
                'sms_enabled' => (bool) $emaildata->vendorsmssent,
                'push_enabled' => (bool) $emaildata->vendorpushsent,
                'sms' => (string) ($emaildata->vendorsms ?? ''),
                'push' => (string) ($emaildata->vendorpush_notification ?? ''),
            ],
            'admin' => [
                'subject' => (string) ($emaildata->adminsubject ?? ''),
                'body' => (string) ($emaildata->adminbody ?? ''),
                'email_enabled' => (bool) $emaildata->adminemailsent,
                'sms_enabled' => (bool) $emaildata->adminsmssent,
                'push_enabled' => (bool) $emaildata->adminpushsent,
                'sms' => (string) ($emaildata->adminsms ?? ''),
                'push' => (string) ($emaildata->adminpush_notification ?? ''),
            ],
            default => [
                'subject' => (string) ($emaildata->subject ?? ''),
                'body' => (string) ($emaildata->body ?? ''),
                'email_enabled' => (bool) $emaildata->emailsent,
                'sms_enabled' => (bool) $emaildata->smssent,
                'push_enabled' => (bool) $emaildata->pushsent,
                'sms' => (string) ($emaildata->sms ?? ''),
                'push' => (string) ($emaildata->push_notification ?? ''),
            ],
        };
    }

    private function samplePreviewData(array $branding = []): array
    {
        $appName = $branding['general_name'] ?? config('app.name');
        $supportEmail = $branding['general_email'] ?? config('mail.from.address');

        return [
            'first_name' => 'Aarav',
            'last_name' => 'Sharma',
            'email' => 'aarav@example.com',
            'phone' => '+91 9876543210',
            'phone_country' => '+91',
            'OTP' => '483920',
            'otp' => '483920',
            'website_name' => $appName,
            'support_email' => $supportEmail,
            'support_phone' => ($branding['general_default_phone_country'] ?? '+91').' '.($branding['general_phone'] ?? '9876543210'),
            'booking_id' => 'BK-1042',
            'bookingid' => 'BK-1042',
            'item_name' => 'Hyundai Creta',
            'check_in' => now()->format('d M Y'),
            'check_out' => now()->addDay()->format('d M Y'),
            'start_time' => '10:30 AM',
            'end_time' => '06:30 PM',
            'current_date' => now()->format('d M Y'),
            'currency_code' => 'INR',
            'amount' => '1,850',
            'payout_amount' => '8,500',
            'payout_date' => now()->format('d M Y'),
            'payout_bank' => 'HDFC Bank',
            'payment_method' => 'UPI',
            'payment_status' => 'Paid',
            'vendor_name' => 'Rahul Travels',
            'vendor_phone' => '+91 9810012345',
            'vendor_email' => 'vendor@example.com',
            'guests' => '2',
            'beds' => '1',
            'ticket_id' => 'TKT-2026',
            'subject' => 'Refund Request Update',
            'update_date' => now()->format('d M Y, h:i A'),
            'title' => 'Your request has been updated',
            'transaction_type' => 'Credit',
            'userName' => 'Aarav Sharma',
            'orderId' => 'ORD-2048',
            'storeName' => $appName,
        ];
    }

    private function replaceTemplatePlaceholders(?string $templateString, array $valuesArray): string
    {
        $templateString = html_entity_decode((string) $templateString);

        foreach ($valuesArray as $key => $value) {
            $quotedKey = preg_quote((string) $key, '/');
            $templateString = preg_replace('/@?{{\s*'.$quotedKey.'\s*}}/i', (string) $value, $templateString);
            $templateString = preg_replace('/{\s*'.$quotedKey.'\s*}/i', (string) $value, $templateString);
        }

        return $templateString;
    }
}
