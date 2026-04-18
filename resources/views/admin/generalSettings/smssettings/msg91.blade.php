@extends('layouts.admin')

@php
    $pageEyebrow = 'SMS gateway';
    $pageTitle = 'MSG91 Configuration';
    $pageSubtitle = 'Configure MSG91 for production OTP delivery and make sure the provider template supports your runtime message placeholder.';
    $providerKey = 'msg91';
    $providerLabel = trans('global.msg91');
    $formAction = route('admin.msg91update');
    $cardTitle = 'MSG91 credentials';
    $cardSubtitle = 'Store the account auth key and DLT template ID used by your login and transactional SMS flows.';
    $callout = [
        'text' => 'Use the variable ##MESSAGE## in your DLT template. The app replaces it with the actual SMS body before sending.',
    ];
    $fields = [
        [
            'label' => trans('global.auth_key'),
            'name' => 'msg91_auth_key',
            'type' => 'password',
            'placeholder' => 'MSG91 auth key',
            'value' => $msg91_auth_key->meta_value ?? '',
        ],
        [
            'label' => trans('global.template_id'),
            'name' => 'msg91_template_id',
            'type' => 'text',
            'placeholder' => 'DLT template ID',
            'value' => $msg91_template_id->meta_value ?? '',
            'help' => 'This should match the approved template in your MSG91 account for OTP or authentication use cases.',
        ],
    ];
@endphp

@section('content')
    @include('admin.generalSettings.smssettings.provider-form')
@endsection

@section('scripts')
    @include('admin.generalSettings.smssettings.toastrmsg')
@endsection
