<html>
<head>
    <title>Razorpay Wallet Recharge</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var options = {
                "key": "{{ $apiKey }}",
                "amount": "{{ $orderDetails['amount'] }}",
                "currency": "{{ $orderDetails['currency'] }}",
                "description": "Driver Recharge",
                "order_id": "{{ $orderDetails['id'] }}",
                "handler": function(response) {
                    var returnUrl = "{{ route('wallet_recharge_return') }}?method=razorpay&wallet_recharge=1&token={{ urlencode($userToken) }}";
                    @if (!empty($planId))
                    returnUrl += "&plan_id={{ urlencode((string) $planId) }}";
                    @endif
                    @if (!empty($durationDays))
                    returnUrl += "&duration_days={{ urlencode((string) $durationDays) }}";
                    @endif
                    returnUrl += "&razorpay_payment_id=" + encodeURIComponent(response.razorpay_payment_id);
                    returnUrl += "&razorpay_order_id=" + encodeURIComponent(response.razorpay_order_id);
                    returnUrl += "&razorpay_signature=" + encodeURIComponent(response.razorpay_signature);
                    window.location.href = returnUrl;
                },
                "modal": {
                    "ondismiss": function() {
                        window.location.href = "{{ route('wallet_recharge_fail', ['userToken' => $userToken]) }}";
                    }
                }
            };

            var rzp1 = new Razorpay(options);
            rzp1.open();
        });
    </script>
</body>
</html>
