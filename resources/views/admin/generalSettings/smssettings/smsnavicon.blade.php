@php
    $providers = [
        ['key' => 'nonage', 'label' => trans('global.smssettings_title_singular'), 'route' => 'admin.smssetting', 'icon' => 'fa-commenting'],
        ['key' => 'msg91', 'label' => trans('global.msg91'), 'route' => 'admin.msg91', 'icon' => 'fa-bolt'],
        ['key' => 'twillio', 'label' => trans('global.twillio'), 'route' => 'admin.twilliosetting', 'icon' => 'fa-phone'],
        ['key' => 'sinch', 'label' => 'Sinch', 'route' => 'admin.sinchSetting', 'icon' => 'fa-rss'],
        ['key' => 'twofactor', 'label' => trans('global.2_factor'), 'route' => 'admin.twofactor', 'icon' => 'fa-shield'],
        ['key' => 'nexmo', 'label' => trans('global.nexmo'), 'route' => 'admin.nexmosetting', 'icon' => 'fa-paper-plane'],
    ];
    $activeProviderKey = $sms_provider_name->meta_value ?? null;
    $activeProvider = collect($providers)->firstWhere('key', $activeProviderKey);
@endphp

<div class="settings-page-header">
    <div>
        <span class="settings-page-header__eyebrow">{{ $pageEyebrow ?? 'Messaging stack' }}</span>
        <h1 class="settings-page-header__title">{{ $pageTitle ?? 'SMS Settings' }}</h1>
        <p class="settings-page-header__subtitle">
            {{ $pageSubtitle ?? 'Configure gateway credentials, choose the active SMS delivery provider, and control OTP auto-fill behavior for mobile login flows.' }}
        </p>
    </div>
    <div class="settings-page-header__actions">
        <span class="settings-status-pill {{ $activeProvider ? 'is-live' : 'is-muted' }}">
            {{ $activeProvider ? 'Active: ' . $activeProvider['label'] : 'No active provider' }}
        </span>
    </div>
</div>

<div class="settings-card settings-card--hero">
    <div class="settings-card__header">
        <div>
            <h3>Delivery routing</h3>
            <p>Each provider keeps its own credentials. Switching the active provider does not erase saved keys.</p>
        </div>
        <div class="settings-toggle-wrap">
            <span class="settings-toggle-wrap__label">Auto Fill OTP</span>
            <label class="settings-switch">
                <input type="checkbox" id="autofillotp" {{ ($auto_fill_otp->meta_value ?? 0) == 1 ? 'checked' : '' }}>
                <span class="settings-switch__slider"></span>
            </label>
        </div>
    </div>

    <div class="settings-callout settings-callout--soft">
        <i class="fa fa-info-circle"></i>
        <span>Keep only one provider active at a time. OTP auto-fill controls whether supported mobile clients may auto-read the verification code.</span>
    </div>

    <div class="sms-provider-tabs">
        @foreach ($providers as $provider)
            <a href="{{ route($provider['route']) }}"
                class="settings-pill-tabs__item {{ request()->routeIs($provider['route']) ? 'is-active' : '' }}">
                <i class="fa {{ $provider['icon'] }}"></i>
                <span>{{ $provider['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
