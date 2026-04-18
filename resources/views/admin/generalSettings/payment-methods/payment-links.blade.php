<ul class="settings-tab-nav">
    @php
        $currentMethod = request()->route('method');
        $methodsToShow = ['paypal', 'stripe', 'razorpay', 'cash'];
    @endphp

    @foreach($methodsToShow as $methodKey)
        <li class="{{ $currentMethod === $methodKey ? 'active' : '' }}">
            <a href="{{ route('admin.payment_methods.index', $methodKey) }}">
                {{ trans('global.' . $methodKey) }}
            </a>
        </li>
    @endforeach
</ul>
