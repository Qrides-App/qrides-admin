@extends('layouts.admin')

@php
    $pageEyebrow = 'SMS gateway';
    $pageTitle = 'Nonage Configuration';
    $pageSubtitle = 'Use the legacy Nonage / MessageWizard gateway for OTP delivery when your tenant still depends on the original provider credentials.';
    $providerKey = 'nonage';
    $providerLabel = trans('global.smssettings_title_singular');
    $formAction = route('admin.smsupdate');
    $cardTitle = 'Nonage credentials';
    $cardSubtitle = 'Store the account key, secret, and approved sender number used for outbound authentication messages.';
    $callout = [
        'text' => 'Nonage remains useful for older mobile builds that already expect the MessageWizard flow. Rotate keys here if OTP delivery starts failing.',
    ];
    $fields = [
        [
            'label' => trans('global.key'),
            'name' => 'messagewizard_key',
            'type' => 'password',
            'placeholder' => 'Gateway key',
            'value' => $messagewizard_key->meta_value ?? '',
        ],
        [
            'label' => trans('global.secrete'),
            'name' => 'messagewizard_secret',
            'type' => 'password',
            'placeholder' => 'Gateway secret',
            'value' => $messagewizard_secret->meta_value ?? '',
        ],
        [
            'label' => trans('global.sender_number'),
            'name' => 'messagewizard_sender_number',
            'type' => 'text',
            'placeholder' => 'Approved sender number',
            'value' => $messagewizard_sender_number->meta_value ?? '',
            'full' => true,
            'help' => 'Use the sender number approved for the same SMS account. Mismatched sender IDs are a common OTP delivery failure.',
        ],
    ];
@endphp

@section('content')
    @include('admin.generalSettings.smssettings.provider-form')
@endsection

@section('scripts')
    @include('admin.generalSettings.smssettings.toastrmsg')
@endsection
