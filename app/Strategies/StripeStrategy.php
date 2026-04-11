<?php

namespace App\Strategies;

use App\Http\Controllers\Traits\MiscellaneousTrait;
use App\Http\Controllers\Traits\PaymentStatusUpdaterTrait;
use App\Http\Controllers\Traits\UserWalletTrait;
use App\Http\Controllers\Traits\VendorWalletTrait;
use App\Models\AppUser;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripeStrategy implements PaymentStrategy
{
    use MiscellaneousTrait, PaymentStatusUpdaterTrait,UserWalletTrait, VendorWalletTrait;

    public function __construct()
    {
        $stripeMode = $this->getGeneralSettingValue('stripe_options');
        $stripeSecretKey = $stripeMode === 'test'
        ? $this->getGeneralSettingValue('test_stripe_secret_key')
        : $this->getGeneralSettingValue('live_stripe_secret_key');
        Stripe::setApiKey($stripeSecretKey);
    }

    public function rechargeWallet($userID, $Amount, $currency, $request)
    {

        $token = $request->input('stripeToken');
        $userToken = $request->input('userToken');
        $userData = AppUser::find($userID);

        $customerName = $userData->first_name.''.$userData->last_name;
        $billingAddress = [
            'line1' => '123 Main St',
            'postal_code' => '90001',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'country' => 'US',
        ];

        $customer = Customer::create([
            'name' => $customerName,
            'address' => $billingAddress,
            'email' => $userData->email,
            'source' => $token,
        ]);

        // Set the charge description
        $chargeDescription = 'Payment for Wallet recharge: '.$userID;

        // Create the charge
        $charge = Charge::create([
            'amount' => $Amount * 100, // Amount in cents
            'currency' => $currency,
            'customer' => $customer->id,
            'description' => $chargeDescription,
        ]);

        if ($charge->status === 'succeeded') {

            $transactionData = new \stdClass;
            $transactionData->response_data = json_encode($charge);
            $transactionData->gateway_name = 'stripe';
            $transactionData->payment_status = 'completed';
            $transactionData->transaction_id = $charge->id;
            $type = 'credit';

            if ($userData->user_type == 'user') {
                $this->addToWallet($userID, $Amount, $type, $chargeDescription, $currency);
            } else {
                $this->addToVendorWallet($userID, $Amount, null, null, $chargeDescription);
            }

            return redirect()->route('wallet_recharge_success', ['userToken' => $userToken]);
        } else {
            return redirect()->route('wallet_recharge_fail', ['userToken' => $userToken]);
        }
        try {
        } catch (ApiErrorException $e) {
            return redirect()->route('wallet_recharge_fail', ['bookingId' => $userToken]);
        }
    }

    public function process($bookingId, $bookingData, $request)
    {

        $token = $request->input('stripeToken');
        $orderId = $request->input('order_id');
        $userData = AppUser::find($bookingData->userid);
        // Retrieve the customer name and billing address from the request
        $customerName = $userData->first_name.' '.$userData->last_name;
        $billingAddress = [
            'line1' => '123 Main St',
            'postal_code' => '90001',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'country' => 'US',
        ];

        $customer = Customer::create([
            'name' => $customerName,
            'address' => $billingAddress,
            'email' => $userData->email,
            'source' => $token,
        ]);

        // Set the charge description
        $chargeDescription = 'Payment for booking: '.$bookingId;

        // Create the charge
        $charge = Charge::create([
            'amount' => $bookingData->amount_to_pay * 100, // Amount in cents
            'currency' => $bookingData->currency_code,
            'customer' => $customer->id,
            'description' => $chargeDescription,
        ]);

        if ($charge->status === 'succeeded') {
            $transactionData = new \stdClass;
            $transactionData->response_data = json_encode($charge);
            $transactionData->gateway_name = 'stripe';
            $transactionData->payment_status = 'completed';
            $transactionData->transaction_id = $charge->id;

            $this->updateBookingStatus($bookingId, $transactionData);

            return redirect()->route('payment_success', ['bookingId' => $bookingId]);
        } else {
            return redirect()->route('payment_fail', ['bookingId' => $bookingId]);
        }
        try {
        } catch (ApiErrorException $e) {
            return redirect()->route('payment_fail', ['bookingId' => $bookingId]);
        }
    }

    public function cancel($bookingId, $bookingData)
    {
        return '/payment_methods?booking='.$bookingId;
    }

    public function return($bookingId, $requestData)
    {
        // Stripe doesn't require a separate return handling
    }

    public function callback($bookingId, $requestData)
    {
        // Stripe doesn't require a separate callback handling
    }

    public function refund($bookingId, $bookingData)
    {
        // Implement the refund logic for Stripe
    }
}
