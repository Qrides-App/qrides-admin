@php
    $links = [
        ['route' => 'admin.settings', 'label' => trans('global.general_title'), 'icon' => 'fa-sliders', 'patterns' => ['admin.settings']],
        ['route' => 'admin.project_setup', 'label' => trans('global.project_setup'), 'icon' => 'fa-cubes'],
        [
            'route' => 'admin.smssetting',
            'label' => trans('global.smssettings_title'),
            'icon' => 'fa-commenting',
            'patterns' => ['admin.smssetting', 'admin.msg91', 'admin.twilliosetting', 'admin.sinchSetting', 'admin.twofactor', 'admin.nexmosetting'],
        ],
        ['route' => 'admin.settings.app.show', 'label' => trans('global.app_settings'), 'icon' => 'fa-mobile', 'patterns' => ['admin.settings.app.*']],
        ['route' => 'admin.email', 'label' => trans('global.emailSettings_title'), 'icon' => 'fa-envelope', 'patterns' => ['admin.email']],
        ['route' => 'admin.currencySetting', 'label' => 'Currency Settings', 'icon' => 'fa-money', 'patterns' => ['admin.currencySetting']],
        ['route' => 'admin.fees', 'label' => 'Financial Settings', 'icon' => 'fa-calculator', 'patterns' => ['admin.fees']],
        ['route' => 'admin.pushnotification', 'label' => trans('global.push_notification_setting'), 'icon' => 'fa-bell', 'patterns' => ['admin.pushnotification']],
        ['route' => 'admin.api-informations', 'label' => trans('global.apiCredentials_title'), 'icon' => 'fa-plug', 'patterns' => ['admin.api-informations']],
        ['route' => 'admin.payment_methods.index', 'label' => trans('global.paymentMethods_title'), 'icon' => 'fa-credit-card', 'params' => ['method' => 'paypal'], 'patterns' => ['admin.payment_methods.*']],
    ];
@endphp

<div class="settings-nav">
    <div class="settings-nav__eyebrow">Workspace</div>
    <ul class="settings-nav__list">
        @foreach ($links as $link)
            @php
                $patterns = $link['patterns'] ?? [$link['route']];
                $isActive = collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));
                $params = $link['params'] ?? [];
            @endphp
            <li class="settings-nav__item {{ $isActive ? 'is-active' : '' }}">
                <a href="{{ route($link['route'], $params) }}">
                    <i class="fa {{ $link['icon'] }}"></i>
                    <span>{{ $link['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
