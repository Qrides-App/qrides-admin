<?php

use App\Http\Controllers\Api\Auth\TokenController;
use App\Http\Controllers\Api\V1\Admin\RideRequestController;
use App\Http\Controllers\Api\V1\Admin\SliderApiController;
use App\Http\Controllers\Front\PaymentFrontController;
use App\Strategies\PaypalStrategy;
use Illuminate\Support\Facades\Route;

// paypal
Route::post('/paypal/ipn', [PaymentFrontController::class, 'handlePaypalIPN'])
    ->name('paypal.ipn');
Route::post('/paypal/webhook', [PaypalStrategy::class, 'handleWebhook'])
    ->name('paypal.webhook');
// Payment webhooks (public)
Route::post('/webhooks/razorpay', [\App\Http\Controllers\Webhook\PaymentWebhookController::class, 'razorpay'])
    ->name('webhooks.razorpay');
Route::post('/webhooks/cashfree', [\App\Http\Controllers\Webhook\PaymentWebhookController::class, 'cashfree'])
    ->name('webhooks.cashfree');

Route::post('/generateToken', [TokenController::class, 'issueSanctumToken'])
    ->name('token.generate');
// 'auth:sanctum'

Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:sanctum']], function () {
    // Slider
    Route::post('/ride-requests', [RideRequestController::class, 'createRide']);
    Route::get('/ride-requests', [RideRequestController::class, 'getRides']);
    Route::patch('/ride-requests/{id}/status', [RideRequestController::class, 'updateRideStatus']);
    Route::post('/sliders', [SliderApiController::class, 'sliders']);

    // App Users
    Route::post('/userRegister', 'AppUsersApiController@userRegister')->name('userRegister');
    Route::post('/otpVerification', 'AppUsersApiController@otpVerification');
    Route::post('/userLogin', 'AppUsersApiController@userLogin');
    Route::post('/userLogout', 'AppUsersApiController@userLogout');
    Route::post('/puthostRequest', 'AppUsersApiController@puthostRequest');
    Route::post('/gethostStatus', 'AppUsersApiController@gethostStatus');
    Route::post('/socialLogin', 'AppUsersApiController@socialLogin');
    Route::post('/userEmailLogin', 'AppUsersApiController@userEmailLogin')->name('userEmailLogin');
    Route::post('/forgotPassword', 'AppUsersApiController@forgotPassword');
    Route::post('/verifyResetToken', 'AppUsersApiController@verifyResetToken');
    Route::post('/ResendTokenEmailChange', 'AppUsersApiController@ResendTokenEmailChange');
    Route::post('/sendMobileLoginOtp', 'AppUsersApiController@sendMobileLoginOtp');
    Route::post('/userMobileLogin', 'AppUsersApiController@userMobileLogin');

    Route::post('/sendOnlyEmailLoginOtp', 'AppUsersApiController@sendOnlyEmailLoginOtp');
    Route::post('/userOnlyEmailLogin', 'AppUsersApiController@userOnlyEmailLogin');

    Route::post('/resetPassword', 'AppUsersApiController@resetPassword');
    Route::post('/emailcheck', 'AppUsersApiController@emailcheck');
    Route::post('/mobilecheck', 'AppUsersApiController@mobilecheck');
    Route::post('/ResendOtp', 'AppUsersApiController@ResendOtp');
    Route::post('/ResendToken', 'AppUsersApiController@ResendToken');
    Route::post('/updatePassword', 'AppUsersApiController@updatePassword');
    Route::post('/getUserWallet', 'AppUsersApiController@getUserWallet');
    Route::post('/getUserWalletTransactions', 'AppUsersApiController@getUserWalletTransactions');

    Route::post('/getVendorWallet', 'AppUsersApiController@getVendorWallet');
    Route::post('/getVendorWalletTransactions', 'AppUsersApiController@getVendorWalletTransactions');
    Route::post('/insertPayout', 'AppUsersApiController@insertPayout');
    Route::post('/getPayoutTransactions', 'AppUsersApiController@getPayoutTransactions');

    // payoutMethod
    Route::post('/update-payout-method', 'PayoutMethodApiController@updatePayoutMethod');
    Route::post('/get-payout-methods', 'PayoutMethodApiController@getPayoutMethods');
    Route::get('/get-payout-types', 'PayoutMethodApiController@getPayoutTypes');

    Route::post('/getDriverEarings', 'DriverFinanceApiController@getDriverEarings');

    Route::post('/deleteAccount', 'AppUsersApiController@deleteAccount');
    Route::post('/addEditVerificationDocuments', 'AppUsersApiController@addEditVerificationDocuments');
    Route::post('/getVerificationDocuments', 'AppUsersApiController@getVerificationDocuments');

    // UserProfile
    Route::post('/getUserProfile', 'UserProfileController@getUserProfile');
    Route::post('/getUseritems', 'UserProfileController@getUseritems');
    Route::post('/getVendorItemReviews', 'UserProfileController@getVendorItemReviews');

    // Cities
    Route::get('/yourLocations', 'CitiesApiController@yourLocations');
    Route::post('/searchCities', 'CitiesApiController@searchCities');
    // Item Type
    Route::get('/getAllCategories', 'ItemTypeApiController@getAllCategories');
    Route::post('/getItemsByItemType', 'ItemTypeApiController@getItemsByItemType');
    Route::post('/editItem', 'ItemsApiController@editItem');
    Route::post('/myItems', 'ItemsApiController@myItems');
    Route::post('/addEditItemImage', 'ItemsApiController@addEditItemImage');
    Route::post('/addEditItemImages', 'ItemsApiController@addEditItemImages');
    // Item Rules
    Route::get('/getItemRules', 'RentalItemRuleApiController@getItemRules');
    // Item Rules
    Route::get('/getMakes', 'MakeApiController@getMakes');
    Route::get('/getMakesModel', 'MakeApiController@getMakesModel');

    // Cancellations rasons
    Route::get('/getCancelReasons', 'CancellationReasonController@getCancelReasons');
    Route::get('/getCancellationPolicies', 'BookingApiController@getCancellationPolicies');

    // Review
    Route::post('/getItemReviews', 'ReviewApiController@getItemReviews');
    Route::post('/giveReviewByUser', 'ReviewApiController@giveReviewByUser');
    Route::post('/giveReviewByHost', 'ReviewApiController@giveReviewByHost');

    // Booking
    Route::post('/getItemPrices', 'BookingApiController@getItemPrices');
    Route::post('/bookItem', 'BookingApiController@bookItem'); // Need to change
    Route::post('/bookingRecord', 'BookingApiController@bookingRecord');
    Route::post('/vendorbookingRecord', 'BookingApiController@vendorBookingRecord');
    Route::post('/confirmBookingByHost', 'BookingApiController@confirmBookingByHost');
    Route::get('/getMaskedCallNumber', 'AppUsersApiController@getMaskedCallNumber');
    Route::post('/voice-masking/proxy', 'ExotelController@maskedCall');
    Route::post('/sendRideChatPush', 'AppUsersApiController@sendRideChatPush');
    Route::get('/getSupportTickets', 'AppUsersApiController@getSupportTickets');
    Route::post('/createSupportTicket', 'AppUsersApiController@createSupportTicket');
    Route::get('/getSupportTicketThread', 'AppUsersApiController@getSupportTicketThread');
    Route::post('/replySupportTicket', 'AppUsersApiController@replySupportTicket');
    Route::post('/updateSupportTicketStatus', 'AppUsersApiController@updateSupportTicketStatus');
    Route::get('/getDriverForHire', 'AppUsersApiController@getDriverForHire');
    Route::post('/bookHireByQr', 'AppUsersApiController@bookHireByQr');
    Route::get('/getRechargePlans', 'AppUsersApiController@getRechargePlans');
    Route::get('/getDriverRechargeStatus', 'AppUsersApiController@getDriverRechargeStatus');
    Route::post('/rechargeWallet', 'AppUsersApiController@rechargeWallet');
    Route::post('/startRechargePayment', 'AppUsersApiController@startRechargePayment');
    Route::post('/confirmRechargePayment', 'AppUsersApiController@confirmRechargePayment');
    Route::post('/exotel/masked-call', 'ExotelController@maskedCall');
    Route::post('/exotel/callback', 'ExotelController@callback')
        ->name('exotel.callback')
        ->withoutMiddleware('auth:sanctum');

    Route::post('/updateBookingStatusByDriver', 'BookingApiController@updateBookingStatusByDriver');
    Route::post('/updatePaymentStatusByDriver', 'BookingApiController@updatePaymentStatusByDriver');
    Route::post('/updatePaymentStatusByUser', 'BookingApiController@updatePaymentStatusByUser');
    Route::post('/updateBookingStatusByUser', 'BookingApiController@updateBookingStatusByUser');

    Route::post('/editProfile', 'MyAccountController@editProfile');
    Route::post('/uploadProfileImage', 'MyAccountController@uploadProfileImage');
    Route::post('/checkMobileNumber', 'MyAccountController@checkMobileNumber');
    Route::post('/changeMobileNumber', 'MyAccountController@changeMobileNumber');
    Route::post('/checkEmail', 'MyAccountController@checkEmail');
    Route::post('/changeEmail', 'MyAccountController@changeEmail');
    Route::post('/getDriverDashboardStats', 'MyAccountController@getDriverDashboardStats');

    // Static Pages
    Route::post('static-pages/media', 'StaticPagesApiController@storeMedia')->name('static-pages.storeMedia');
    Route::apiResource('static-pages', 'StaticPagesApiController');
    Route::get('StaticPage', 'StaticPagesApiController@StaticPage');

    // All Packages
    Route::post('all-packages/media', 'AllPackagesApiController@storeMedia')->name('all-packages.storeMedia');
    Route::apiResource('all-packages', 'AllPackagesApiController', ['except' => ['destroy']]);

    // General Setting
    Route::get('getgeneralSettings', 'GeneralSettingApiController@getgeneralSettings')->name('getSettings');

    // Payout
    Route::post('/getTotalPayoutAmount', 'PayoutApiController@getTotalPayoutAmount');

    Route::post('fcmUpdate', 'AppUsersApiController@fcmUpdate');

    // email sms push notification
    Route::post('emailSmsNotification', 'AppUsersApiController@emailSmsNotification');
    // currency
    Route::post('/getCurrencyDetails', 'CurrencyApiController@index')->name('getCurrencyDetails');
    Route::get('/updateRates', 'CurrencyApiController@updateRates')->name('updateRates');

    // SOS
    Route::get('/sos', 'SOSController@index')->name('sos.index');
});
