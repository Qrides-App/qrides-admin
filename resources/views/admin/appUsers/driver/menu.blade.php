<div class="driver-header">
            <div class="title">{{ ($appUser ?? $driver ?? null)?->first_name }} {{ ($appUser ?? $driver ?? null)?->last_name }}</div>
            <div class="actions" style="gap: 5px;">
       @php

$current = $appUser ?? $driver ?? null;
$navItems = [
    [
        'url' => 'admin/driver/profile/' . $current->id,
        'label' => trans('user.overview'),
        'class' => 'btn-green',
        'icon' => '👤', // Profile/Overview
    ],
    [
        'url' => 'admin/driver/financial/' . $current->id,
        'label' => trans('user.financial'),
        'class' => 'btn-green',
        'icon' => '💰', // Financial info
    ],
      [
        'url' => 'admin/payouts/?from=&to=&customer=&vendor=' . $current->id,
        'label' => trans('user.payout'),
        'class' => 'btn-gray',
        'icon' => '💸', // Payouts
        'target' => '_blank',
    ],
    [
        'url' => 'admin/bookings?from=&to=&customer=&host=' . $current->id . '&status=&btn=',
        'label' => trans('user.bookings'),
        'class' => 'btn-green',
        'icon' => '📅', // Bookings
        'target' => '_blank',
    ],
    [
        'url' => 'admin/driver/hire/' . $current->id,
        'label' => 'QR Hire',
        'class' => 'btn-orange',
        'icon' => '🔳', // QR hire
    ],
    [
        'url' => 'admin/driver/account/' . $current->id,
        'label' => trans('user.account'),
        'class' => 'btn-red',
        'icon' => '⚙️', // Account settings
    ],
    [
        'url' => 'admin/driver/document/' . $current->id,
        'label' => trans('user.driver_document'),
        'class' => 'btn-orange',
        'icon' => '📄', // Documents
    ],
    [
        'url' => 'admin/driver/stripe/' . $current->id,
        'label' => trans('user.payment_method'),
        'class' => 'btn-black',
        'icon' => '',
    ],
    [
        'url' => 'admin/driver/vehicle/' . $current->id,
        'label' => trans('user.vehicle'),
        'class' => 'btn-gray',
        'icon' => '🚗', // Vehicle
    ]
];
@endphp


@foreach ($navItems as $item)
    <a href="{{ url($item['url']) }}" 
       class="btn {{ $item['class'] }} {{ request()->is($item['url']) ? 'active' : '' }}"
       @if (!empty($item['target'])) target="{{ $item['target'] }}" @endif>
        @if (!empty($item['icon'])) {{ $item['icon'] }} @endif
        {{ $item['label'] }}
    </a>
@endforeach

            </div>
        </div>
