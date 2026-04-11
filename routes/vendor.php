<?php

use Illuminate\Support\Facades\Route;

// Vendor Modules New Modules
Route::get('/vendor/dashboard', function () {
    if (session('status')) {
        return redirect()->route('vendor.dashboard')->with('status', session('status'));
    }

    return redirect()->route('vendor.dashboard');
});

// Vendor Auth Routes
Route::prefix('vendor')->namespace('VendorAuth')->name('vendor.')->group(function () {
    Route::redirect('/', '/vendor/login');
    Route::get('/register', 'VendorLoginController@registerForm')->name('register');
    Route::post('/register/request', 'VendorLoginController@register')->name('register.request');
    Route::post('/otpVerificationVendor', 'VendorLoginController@otpVerification')->name('otpVerificationVendor');
    Route::post('/resendOtpVendor', 'VendorLoginController@resendOtp')->name('resendOtpVendor');
    Route::get('/hostRequestForm', 'VendorLoginController@hostRequestForm')->name('hostRequestForm');
    Route::post('/putHostRequest', 'VendorLoginController@putHostRequest')->name('putHostRequest');
    Route::get('/login', 'VendorLoginController@loginForm')->name('login');
    Route::post('/login', 'VendorLoginController@login');
    Route::get('/logout', 'VendorLoginController@logout')->name('logout');
    Route::post('/forgotPassword', 'VendorLoginController@forgotPassword')->name('forgotPassword');
    Route::post('/verifyResetToken', 'VendorLoginController@verifyResetToken')->name('verifyResetToken');
    Route::post('/resendTokenForgotPassword', 'VendorLoginController@resendTokenForgotPassword')->name('resendTokenForgotPassword');
    Route::post('/resetPassword', 'VendorLoginController@resetPassword')->name('resetPassword');

});
// Google Auth Routes - outside of vendor prefix
Route::get('/login/google', 'VendorAuth\GoogleAuthController@redirectToGoogle')->name('login.google');
Route::get('/login/google/call-back', 'VendorAuth\GoogleAuthController@handleGoogleCallback')->name('login.google.callback');

// Vendor Dashboard and Other Routes
Route::middleware(['auth:appUser', 'checkHostStatus'])->prefix('vendor')->namespace('Vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', 'VendorHomeController@dashboard')->name('dashboard');
    Route::get('/profile', 'ProfileController@index')->name('profile');
    Route::put('/profile/update/{appUser}', 'ProfileController@updateProfile')->name('profile.update');
    Route::post('/profile/checkEmail', 'ProfileController@checkEmail')->name('profile.checkEmail');
    Route::post('/profile/changeEmail', 'ProfileController@changeEmail')->name('profile.changeEmail');
    Route::post('/profile/ResendTokenEmailChange', 'ProfileController@ResendTokenEmailChange')->name('profile.ResendTokenEmailChange');
    Route::post('/profile/checkMobileNumber', 'ProfileController@checkMobileNumber')->name('profile.checkMobileNumber');
    Route::post('/profile/changeMobileNumber', 'ProfileController@changeMobileNumber')->name('profile.changeMobileNumber');
    Route::post('/profile/ResendToken', 'ProfileController@ResendToken')->name('profile.ResendToken');
    Route::post('/profile/updatePassword', 'ProfileController@updatePassword')->name('profile.updatePassword');
    Route::get('/bankaccount', 'BankAccountController@index')->name('bankaccount');
    Route::match(['POST', 'PUT'], '/bankaccount/{id?}', 'BankAccountController@storeOrUpdate')->name('bankaccount.storeOrUpdate');
    Route::resource('orders', 'OrderController', ['except' => ['destroy']]);
    Route::POST('orders/confirm', 'OrderController@confirmOrder')->name('confirm.order');
    Route::POST('orders/cancel', 'OrderController@cancelOrder')->name('cancel.order');
    Route::get('cancellation-reasons', 'OrderController@getCancellationReasons')->name('cancellationReasons');
    Route::get('finance', 'FinanceController@index')->name('finance');
    Route::get('payouts', 'PayoutController@index')->name('payouts');
    Route::post('/request/payout', 'PayoutController@requestPayout')->name('request.payout');
    Route::get('chat', 'ChatController@chatPage')->name('chatPage');
});

Route::middleware(['auth:appUser', 'checkHostStatus'])->prefix('vendor')->name('vendor.')->namespace('Vendor\Common')->group(function () {
    Route::get('vehicles/trash', 'ItemController@trashListings')->name('vehicles.trash');
    Route::post('common/restore/{id}', 'ItemController@restore')->name('common.trash.restore');
    Route::post('common/permanent-delete/{id}', 'ItemController@permanentDelete')->name('common.trash.permanentDelete');
    Route::post('common/permanent-delete-all', 'ItemController@permanentDeleteAll')->name('common.trash.permanentDeleteAll');
    Route::delete('/delete-rows', 'ItemController@deleteRows')->name('delete.rows');
    Route::delete('/trash-delete-rows', 'ItemController@trashDeleteRows')->name('trash-delete.rows');
    Route::post('destroy', 'ItemController@massDestroy')->name('massDestroy');
    Route::POST('vehicles/itemUnpublishedByVendor', 'ItemController@itemUnpublishedByVendor')->name('vehicles.itemUnpublishedByVendor');
    Route::resource('vehicles', 'ItemController');
    Route::get('vehicles/base/{id}', 'Vehicles\VehicleController@base')->name('vehicles.base');
    Route::POST('vehicles/baseUpdate', 'Vehicles\VehicleController@baseUpdate')->name('vehicles.base-Update');
    Route::get('vehicles/description/{id}', 'Vehicles\VehicleController@description')->name('vehicles.description');
    Route::POST('vehicles/descriptionUpdate', 'Vehicles\VehicleController@updateTitleDescription')->name('vehicles.description-Update');
    Route::get('vehicles/location/{id}', 'Vehicles\VehicleController@location')->name('vehicles.location');
    Route::POST('vehicles/locationUpdate', 'Vehicles\VehicleController@locationUpdate')->name('location-Update');
    /****  features */
    Route::get('vehicles/features/{id}', 'Vehicles\VehicleController@features')->name('vehicles.features');
    Route::POST('vehicles/featuresUpdate', 'Vehicles\VehicleController@featuresUpdate')->name('features-Update');
    /****  Photos */
    Route::get('vehicles/photos/{id}', 'Vehicles\VehicleController@photos')->name('vehicles.photos');
    Route::POST('photosUpdate', 'Vehicles\VehicleController@photosUpdate')->name('photos-Update');
    Route::post('vendor/item/media', 'Vehicles\VehicleController@storeMedia')->name('storeMedia');

    /****  price */
    Route::get('vehicles/pricing/{id}', 'Vehicles\VehicleController@pricing')->name('vehicles.pricing');
    Route::POST('vehicles/prices-Update', 'Vehicles\VehicleController@pricesUpdate')->name('vehicles.prices-Update');

    /****  cancellation */
    Route::get('vehicles/cancellation-policies/{id}', 'Vehicles\VehicleController@CancellationPolicies')->name('vehicles.cancellation-policies');
    Route::POST('vehicles/vehicles/cancellation-policies-Update', 'Vehicles\VehicleController@cancellationPoliciesUpdate')->name('cancellation-policies-Update');

    /****  Calender */
    Route::get('vehicles/calendar/{id}', 'Vehicles\VehicleController@calendar')->name('vehicles.calendar');
    Route::post('vehicles.Calendar-Update', 'Vehicles\VehicleController@CalandarUpdate')->name('vehicles.Calendar-Update');
    Route::get('vehicles.calendar/{id}', 'Vehicles\VehicleController@calendarMonth')->name('vehicles.calendar.index');
    Route::POST('vehicles/get-vehicletype', 'Vehicles\VehicleController@getVehicleType')->name('vehicles.get-vehicletype');
    Route::POST('vehicles/get-vehiclemake', 'Vehicles\VehicleController@getVehicleMake')->name('vehicles.get-vehiclemake');

    Route::get('searchVendorItem', 'ItemController@searchVendorItem')->name('searchVendorItem');
    Route::get('searchBookingUser', 'ItemController@searchBookingUser')->name('searchBookingUser');
    Route::get('itemtypeSearch', 'ItemController@typeSearch')->name('itemtypeSearch');
    Route::get('/item-incomplete-steps', 'ItemController@getIncompleteSteps')->name('item-incomplete-steps');
});
