<div class="driver-header">
    <div class="title">{{ ($appUser ?? $driver ?? null)?->first_name }} {{ ($appUser ?? $driver ?? null)?->last_name }}</div>
    <div class="actions" style="gap: 5px;">
        @php
            $current = $appUser ?? $driver ?? null;
            $navItems = [
                [
                    'href' => route('admin.driver.profile', $current->id),
                    'label' => trans('user.overview'),
                    'class' => 'btn-green',
                    'icon' => '👤',
                    'active' => request()->routeIs('admin.driver.profile'),
                ],
                [
                    'href' => route('admin.driver.financial', $current->id),
                    'label' => trans('user.financial'),
                    'class' => 'btn-green',
                    'icon' => '💰',
                    'active' => request()->routeIs('admin.driver.financial'),
                ],
                [
                    'href' => route('admin.payouts.index', ['from' => '', 'to' => '', 'customer' => '', 'vendor' => $current->id]),
                    'label' => trans('user.payout'),
                    'class' => 'btn-gray',
                    'icon' => '💸',
                    'active' => request()->routeIs('admin.payouts.index') && (string) request()->query('vendor') === (string) $current->id,
                ],
                [
                    'href' => route('admin.bookings.index', ['from' => '', 'to' => '', 'customer' => '', 'host' => $current->id, 'status' => '', 'btn' => '']),
                    'label' => trans('user.bookings'),
                    'class' => 'btn-green',
                    'icon' => '📅',
                    'active' => request()->routeIs('admin.bookings.index') && (string) request()->query('host') === (string) $current->id,
                ],
                [
                    'href' => route('admin.driver.hire', $current->id),
                    'label' => 'QR Hire',
                    'class' => 'btn-orange',
                    'icon' => '🔳',
                    'active' => request()->routeIs('admin.driver.hire'),
                ],
                [
                    'href' => route('admin.driver.account', $current->id),
                    'label' => trans('user.account'),
                    'class' => 'btn-red',
                    'icon' => '⚙️',
                    'active' => request()->routeIs('admin.driver.account'),
                ],
                [
                    'href' => route('admin.driver.document', $current->id),
                    'label' => trans('user.driver_document'),
                    'class' => 'btn-orange',
                    'icon' => '📄',
                    'active' => request()->routeIs('admin.driver.document'),
                ],
                [
                    'href' => route('admin.driver.stripe', $current->id),
                    'label' => trans('user.payment_method'),
                    'class' => 'btn-black',
                    'icon' => '',
                    'active' => request()->routeIs('admin.driver.stripe', 'admin.driver.paypal', 'admin.driver.upi', 'admin.driver.bank'),
                ],
                [
                    'href' => route('admin.driver.vehicle', $current->id),
                    'label' => trans('user.vehicle'),
                    'class' => 'btn-gray',
                    'icon' => '🚗',
                    'active' => request()->routeIs('admin.driver.vehicle'),
                ],
            ];
        @endphp

        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}" class="btn {{ $item['class'] }} {{ $item['active'] ? 'active' : '' }}">
                @if (!empty($item['icon'])) {{ $item['icon'] }} @endif
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</div>
