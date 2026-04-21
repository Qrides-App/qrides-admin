<?php

use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Controllers\PublicDiskMediaController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/privacy-policy', 'App\Http\Controllers\Front\ExternalPagesController@privacyPolicy')->name('privacy-policy');
Route::get('/payment_methods', 'App\Http\Controllers\Front\PaymentFrontController@showPaymentPage')->name('payment_methods');
Route::post('/payment', 'App\Http\Controllers\Front\PaymentFrontController@handlePayment')->name('payment');

Route::get('/invalid-order', function () {
    $error = session('error');

    return view('Front/invalid-order', ['error' => $error]);  // Assuming you have a view named "invalid-order"
});

Route::get('/payment/return', 'App\Http\Controllers\Front\PaymentFrontController@handleReturn')->name('handleReturn');
Route::match(['get', 'post', 'put'], '/payment/callback', 'App\Http\Controllers\Front\PaymentFrontController@handleCallback')
    ->name('handleCallback');

Route::get('/payment/cancel', 'App\Http\Controllers\Front\PaymentFrontController@handleCancel')->name('handleCancel');

Route::get('/payment_success', 'App\Http\Controllers\Front\PaymentFrontController@paymentSuccess')->name('payment_success');
Route::get('/payment_payduniya', 'App\Http\Controllers\Front\PaymentFrontController@payment_payduniya')->name('payment_payduniya');
Route::get('/payment_fail', 'App\Http\Controllers\Front\PaymentFrontController@paymentfail')->name('payment_fail');
Route::get('/testing', 'App\Http\Controllers\Front\PaymentFrontController@testing')->name('testing');
Route::get('/ride-tracking/{token}', [App\Http\Controllers\Front\RideTrackingController::class, 'show'])->name('ride-tracking.show');
Route::get('/ride-tracking/{token}/snapshot', [App\Http\Controllers\Front\RideTrackingController::class, 'snapshot'])->name('ride-tracking.snapshot');
Route::get('/scan-to-hire', function () {
    $driverId = trim((string) request()->query('driver_id', ''));

    abort_if($driverId === '', 404);

    $driver = \App\Models\AppUser::select('id', 'first_name', 'last_name', 'host_status', 'status')
        ->where('id', $driverId)
        ->where('user_type', 'driver')
        ->first();

    abort_if(! $driver, 404);

    $isApproved = (string) $driver->host_status === '1' && (string) $driver->status === '1';

    return view('Front.scan-to-hire', [
        'driverId' => $driverId,
        'driver' => $driver,
        'isApproved' => $isApproved,
        'appLink' => 'qrides://hire?driver_id='.$driverId,
    ]);
})->name('scan-to-hire');
Route::get('/media/public/{path}', [PublicDiskMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.public');
Route::get('/storage/{path}', [PublicDiskMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.public.legacy');

// email route
Route::middleware('auth')->group(function () {
    Route::get('user/email-templates/{id}', 'App\Http\Controllers\Admin\EmailController@template')->name('user.email-templates');
    Route::get('vendor/email-templates/{id}', 'App\Http\Controllers\Admin\EmailController@template')->name('vendor.email-templates');
    Route::post('vendor/email-templates/create/{id}', 'App\Http\Controllers\Admin\EmailController@templatecreate')->name('vendor.email-template.create');
    Route::post('user/email-template/create/{id}', 'App\Http\Controllers\Admin\EmailController@templatecreate')->name('user.email-template.create');
});
// end

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('password/reset', 'App\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
    Route::post('password/email', 'App\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
    Route::get('password/reset/{token}', 'App\Http\Controllers\Auth\ResetPasswordController@showResetForm')->name('password.reset');
    Route::post('password/reset', 'App\Http\Controllers\Auth\ResetPasswordController@reset')->name('password.update');
});

Route::match(['get', 'post'], 'logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
    Route::post('password/confirm', [ConfirmPasswordController::class, 'confirm']);
    Route::get('email/verify', function () {
        return view('auth.verify');
    })->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('email/verification-notification', [VerificationController::class, 'resend'])->name('verification.resend');
});

Route::redirect('/', '/login');
Route::get('/home', function () {
    if (session('status')) {
        return redirect()->route('admin.home')->with('status', session('status'));
    }

    return redirect()->route('admin.home');
});

/*********** Front End ****/

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin\Common', 'middleware' => ['auth']], function () {
    /****  Trash */
    Route::post('common/restore/{id}', 'ItemController@restore')->name('common.trash.restore');
    Route::post('common/permanent-delete/{id}', 'ItemController@permanentDelete')->name('common.trash.permanentDelete');
    Route::post('common/permanent-delete-all', 'ItemController@permanentDeleteAll')->name('common.trash.permanentDeleteAll');
    Route::get('vehicles/trash', 'ItemController@trashListings')->name('vehicles.trash');
    Route::delete('/delete-rows', 'ItemController@deleteRows')->name('delete.rows');
    Route::delete('/trash-delete-rows', 'ItemController@trashDeleteRows')->name('trash-delete.rows');
    Route::post('destroy', 'ItemController@massDestroy')->name('massDestroy');
    Route::get('searchItem', 'ItemController@searchItem')->name('searchItem');
    Route::post('update-item-status', 'ItemController@updateStatus')->name('update-item-status');
    Route::post('update-item-featured', 'ItemController@updateFeatured')->name('update-item-featured');
    Route::post('update-item-verified', 'ItemController@updateVerified')->name('update-item-verified');
    Route::get('/incomplete-steps', 'ItemController@getIncompleteSteps')->name('incomplete-steps');
    Route::post('get-item-documents', 'ItemController@getItemDocuments')->name('get-item-documents');
    Route::post('update-item-document-status', 'ItemController@updateItemDocumentStatus')->name('update-item-document-status');
    Route::resource('vehicles', 'ItemController');

    /****  Location */
    Route::resource('vehicle-location', 'LocationController');
    Route::delete('location/deleteAll', 'LocationController@deleteAll')->name('item-location.deleteAll');
    Route::post('vehicle-location/media', 'LocationController@storeMedia')->name('vehicle-location.storeMedia');
    Route::post('vehicle-location/ckmedia', 'LocationController@storeCKEditorImages')->name('vehicle-location.storeCKEditorImages');
    Route::post('update-vehicle-location-status', 'LocationController@updateStatus')->name('update-vehicle-location-status');
    Route::get('vehicles/location/{id}', 'addSteps\CommonVehicleLocationController@location')->name('vehicles.location');
    Route::POST('vehicles/locationUpdate', 'addSteps\CommonVehicleLocationController@locationUpdate')->name('location-Update');

    /****  Type */
    Route::delete('item-types/deleteAll', 'ItemTypeController@bulkDelete')->name('item-types.deleteAll');
    Route::post('vehicle-type/media', 'ItemTypeController@storeMedia')->name('vehicle-type.storeMedia');
    Route::post('vehicle-type/ckmedia', 'ItemTypeController@storeCKEditorImages')->name('vehicle-type.storeCKEditorImages');
    Route::resource('vehicle-type', 'ItemTypeController');
    Route::post('update-vehicle-type-status', 'ItemTypeController@updateStatus')->name('update-vehicle-type-status');

    /****  Features */
    Route::delete('features', 'ItemFeaturesController@featuresDelete')->name('features.deleteAll');
    Route::post('update-features-status', 'ItemFeaturesController@updateStatus')->name('update-features-status');
    Route::post('vehicle-features/media', 'ItemFeaturesController@storeMedia')->name('vehicle-features.storeMedia');
    Route::post('vehicle-features/ckmedia', 'ItemFeaturesController@storeCKEditorImages')->name('vehicle-features.storeCKEditorImages');
    Route::resource('vehicle-features', 'ItemFeaturesController');
    Route::post('update-vehicle-features-status', 'ItemFeaturesController@updateStatus')->name('update-vehicle-features-status');

    /****  Category (Make) */
    Route::delete('vehicle-makes', 'ItemMakeController@vehicleDelete')->name('vehicle-makes.deleteAll');
    Route::post('vehicle-makes/media', 'ItemMakeController@storeMedia')->name('vehicle-makes.storeMedia');
    Route::post('vehicle-makes/ckmedia', 'ItemMakeController@storeCKEditorImages')->name('vehicle-makes.storeCKEditorImages');
    Route::resource('vehicle-makes', 'ItemMakeController');
    Route::post('update-vehicle-makes-status', 'ItemMakeController@updateStatus')->name('update-vehicle-makes-status');

    // vehicle-model
    Route::delete('vehicle-model', 'ItemSubCategoryController@vehicleModelDelete')->name('vehicle-model.deleteAll');
    Route::post('vehicle-model/media', 'ItemSubCategoryController@storeMedia')->name('vehicle-model.storeMedia');
    Route::post('vehicle-model/ckmedia', 'ItemSubCategoryController@storeCKEditorImages')->name('vehicle-model.storeCKEditorImages');
    Route::resource('vehicle-model', 'ItemSubCategoryController');
    Route::post('update-vehicle-model-status', 'ItemSubCategoryController@updateStatus')->name('update-vehicle-model-status');

    /****  Features */
    Route::get('vehicles/features/{id}', 'addSteps\CommonFeaturesController@features')->name('vehicles.features');
    Route::POST('vehicles/featuresUpdate', 'addSteps\CommonFeaturesController@featuresUpdate')->name('features-Update');

    /****  Photos */
    Route::get('vehicles/photos/{id}', 'addSteps\CommonPhotosController@photos')->name('vehicles.photos');
    Route::POST('photosUpdate', 'addSteps\CommonPhotosController@photosUpdate')->name('photos-Update');
    Route::post('item/media', 'addSteps\CommonPhotosController@storeMedia')->name('storeMedia');

    /** Description */
    Route::get('vehicles/description/{id}', 'addSteps\TitleDescriptionController@titleDescription')->name('vehicles.description');
    Route::POST('vehicles/descriptionUpdate', 'addSteps\TitleDescriptionController@updateTitleDescription')->name('vehicles.description-Update');
});

/****  Vehicle Module */

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin\Vehicles', 'middleware' => ['auth']], function () {
    Route::get('vehicles/base/{id}', 'VehicleBaseController@base')->name('vehicles.base');
    Route::POST('vehicles/baseUpdate', 'VehicleBaseController@baseUpdate')->name('vehicles.base-Update');
    Route::POST('vehicles/get-vehicletype', 'VehicleBaseController@getVehicleType')->name('vehicles.get-vehicletype');
    Route::POST('vehicles/get-vehiclemake', 'VehicleBaseController@getVehicleMake')->name('vehicles.get-vehiclemake');
    Route::get('vehicles/pricing/{id}', 'VehiclePricingController@pricing')->name('vehicles.pricing');
    Route::POST('vehicles/prices-Update', 'VehiclePricingController@pricesUpdate')->name('vehicles.prices-Update');
    Route::resource('vehicle-odometer', 'vehicalOdometer\VehicleOdometerController', ['except' => ['destroy']]);
    Route::post('update-odometer-status', 'vehicalOdometer\VehicleOdometerController@updateOdometerStatus')->name('update-odometer-status');
    Route::get('vehicle-odometer-delete/{id}', 'vehicalOdometer\VehicleOdometerController@delete')->name('vehicle-odometer.delete');
    Route::post('vehicle-odometer-delete/delete-all', 'vehicalOdometer\VehicleOdometerController@deleteAll')->name('vehicle-odometer.deleteAll');

    // payout method
    Route::resource('payout-method', 'PayoutMethod\PayoutMethodController');
    Route::delete('payout-method/delete-all', 'PayoutMethod\PayoutMethodController@deleteAll')->name('payout-method.deleteAll');
    Route::post('update-payout-method', 'PayoutMethod\PayoutMethodController@updatePayoutMethodStatus')->name('update-payout-method');
});

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin', 'middleware' => ['auth']], function () {
    Route::prefix('translations')->group(function () {
        Route::get('/edit/{locale}/{page?}', 'TranslationController@editTranslations')->name('translations.edit');
        Route::get('/languages', 'TranslationController@listLanguages')->name('translations.languages');
        Route::post('/save-temporary/{locale}', 'TranslationController@saveTemporaryTranslations')->name('saveTemporaryTranslations');

    });

    /****   Booking */
    Route::delete('bookings/delete-all', 'BookingController@bookingDeleteAll')->name('bookings.deleteAll');
    Route::delete('bookings/deleteTrash-all', 'BookingController@bookngTrashAll')->name('bookings.deleteTrashAll');
    Route::get('bookings/trash', 'BookingController@index')->name('bookings.trash');
    Route::post('bookings/restore/{id}', 'BookingController@restoreTrash')->name('bookings.restore');
    Route::post('bookings/permanent-delete/{id}', 'BookingController@permanentDelete')->name('bookings.permanentDelete');
    Route::post('bookings/permanent-delete-all', 'BookingController@deleteAllPermanent')->name('bookings.permanentDeleteAll');
    Route::resource('bookings', 'BookingController');
    Route::get('hire-bookings', '\App\Http\Controllers\Admin\HireBookingController@index')->name('hire-bookings.index');
    Route::get('customerItem', 'BookingController@customerItem')->name('customerItem');
    Route::get('overview/{booking}', 'Driver\AppDriverController@driverProfileView')->name('overview');
    Route::get('item/{booking}', 'BookingController@items')->name('item');
    Route::get('orders/{booking}', 'BookingController@orders')->name('orders');
    Route::get('booking/{booking}', 'BookingController@bookings')->name('booking');
    Route::get('payout/{booking}', 'BookingController@payouts')->name('payout');
    Route::get('wallet/{booking}', 'BookingController@wallet')->name('wallet');
    Route::get('bankAccount/{booking}', 'BookingController@bankAccount')->name('bankAccount');

    /****   Home */
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/delete-installer', 'HomeController@deleteInstaller')->name('deleteInstaller');
    /****   Permissions */
    Route::delete('permissions/destroy', 'PermissionsController@massDestroy')->name('permissions.massDestroy');
    Route::resource('permissions', 'PermissionsController');

    /****  Roles */
    Route::delete('roles/destroy', 'RolesController@massDestroy')->name('roles.massDestroy');
    Route::resource('roles', 'RolesController');

    /****  Users */
    Route::delete('users/destroy', [UsersController::class, 'massDestroy'])->name('users.massDestroy');
    Route::resource('users', UsersController::class);
    Route::get('user/getDailyUserRegistrations', [UsersController::class, 'getDailyUserRegistrations'])->name('users.getDailyUserRegistrations');

    /****  Modules */
    Route::delete('module/destroy', 'ModuleController@massDestroy')->name('module.massDestroy');
    Route::post('module/media', 'ModuleController@storeMedia')->name('module.storeMedia');
    Route::resource('module', 'ModuleController');
    Route::post('update-module-status', 'ModuleController@updateStatus')->name('update-module-status');

    /****  Slider */
    Route::delete('sliders/destroy', 'SliderController@massDestroy')->name('sliders.massDestroy');
    Route::post('sliders/media', 'SliderController@storeMedia')->name('sliders.storeMedia');
    Route::post('sliders/ckmedia', 'SliderController@storeCKEditorImages')->name('sliders.storeCKEditorImages');
    Route::resource('sliders', 'SliderController');

    // App Users

    Route::get('app-users/trashed', 'AppUsersController@appUserTrashed')->name('app-users.trash');
    Route::post('app-users/restore/{id}', 'AppUsersController@restoreTrash')->name('app-users.restore');
    Route::post('app-users/permanent-delete/{id}', 'AppUsersController@permanentDelete')->name('app-users.permanentDelete');
    Route::post('app-users/permanent-delete-all', 'AppUsersController@permanentDeleteAll')->name('app-users.permanentDeleteAll');
    Route::delete('app-users/delete-all', 'AppUsersController@deleteAll')->name('app-users.deleteAll');
    Route::delete('app-users/deleteTrash-all', 'AppUsersController@deleteTrashAll')->name('app-users.deleteTrashAll');
    Route::post('app-users/media', 'AppUsersController@storeMedia')->name('app-users.storeMedia');
    Route::post('app-users/ckmedia', 'AppUsersController@storeCKEditorImages')->name('app-users.storeCKEditorImages');
    Route::resource('app-users', 'AppUsersController');
    Route::get('searchcustomer', 'AppUsersController@customerSearch')->name('searchcustomer');
    Route::get('typeSearch', 'AppUsersController@typeSearch')->name('typeSearch');
    Route::get('searchHost', 'AppUsersController@hostSearch')->name('searchHost');
    Route::get('searchUser', 'AppUsersController@userSearch')->name('searchUser');
    Route::get('customer/overview/{host_id}', 'AppUsersController@overview')
        ->name('admin.customer.overview');
    Route::post('update-appuser-status', 'AppUsersController@updateStatus')->name('update-appuser-status');
    Route::post('update-appuser-host-status', 'AppUsersController@updateHostStatus')->name('update-appuser-host-status');
    Route::post('update-appuser-document-status', 'AppUsersController@updateHostStatus')->name('update-appuser-document-status');
    Route::post('get-appuser-host-status-detail', 'AppUsersController@getHostStatusDetail')->name('get-appuser-host-status-detail');
    Route::post('update-appuser-identify', 'AppUsersController@updateIdentify')->name('update-appuser-identify');
    Route::post('update-appuser-phoneverify', 'AppUsersController@updatePhoneverify')->name('update-appuser-phoneverify');
    Route::post('update-appuser-emailverify', 'AppUsersController@updateEmailverify')->name('update-appuser-emailverify');
    Route::post('get-verification-documents', 'AppUsersController@getVerificationDocuments')->name('get-verification-documents');
    Route::post('update-verification-document-status', 'AppUsersController@updateVerificationDocumentStatus')->name('update-verification-document-status');

    Route::get('driver/stripe/{id}', 'AppUsersController@payoutMethodStripe')->name('driver.stripe');

    Route::get('driver/paypal/{id}', 'AppUsersController@payoutMethodPaypal')->name('driver.paypal');

    Route::get('driver/upi/{id}', 'AppUsersController@payoutMethodUpi')->name('driver.upi');

    Route::get('driver/bank/{id}', 'AppUsersController@payoutMethodBank')->name('driver.bank');

    Route::post('driver/update/bank/account/{id}', 'AppUsersController@updatePayoutMethod')->name('driver.update.bank.account');

    Route::get('app-users/account/{rider_id}', 'AppUsersController@riderAccountView')
        ->name('rider.account');
    Route::post('app-users/account/updateProfile/{rider_id}', 'AppUsersController@updateProfile')
        ->name('rider.account.update');

    /*** Driver Profile */
    Route::resource('drivers', 'Driver\AppDriverController')->only(['index', 'create', 'store']);
    Route::get('driver/profile/{driver_id}', 'Driver\AppDriverController@driverProfileView')
        ->name('driver.profile');
    Route::get('driver/financial/{driver_id}', 'Driver\AppDriverController@driverFinanceView')
        ->name('driver.financial');
    Route::get('driver/account/{driver_id}', 'Driver\AppDriverController@driverAccountView')
        ->name('driver.account');
    Route::get('driver/hire/{driver_id}', 'Driver\AppDriverController@driverHireView')
        ->name('driver.hire');
    Route::get('driver/vehicle/{driver_id}', 'Driver\AppDriverController@driverVehicleView')
        ->name('driver.vehicle');
    Route::get('driver/document/{driver_id}', 'Driver\AppDriverController@driverDocumentView')
        ->name('driver.document');
    Route::get('driver/payouts/{driver_id}', 'Driver\AppDriverController@driverPayoutsView')
        ->name('driver.payouts');

    Route::post('driver/account/updateProfile/{driver_id}', 'Driver\AppDriverController@updateProfile')
        ->name('driver.account.update');
    Route::post('driver/account/profileVerify/{driver_id}', 'Driver\AppDriverController@profileVerify')
        ->name('driver.account.profileVerify');
    Route::post('driver/account/emailVerify/{driver_id}', 'Driver\AppDriverController@emailVerify')
        ->name('driver.account.emailVerify');
    Route::post('driver/account/documentVerify/{driver_id}', 'Driver\AppDriverController@documentVerify')
        ->name('driver.account.documentVerify');
    Route::post('driver/account/phoneVerify/{driver_id}', 'Driver\AppDriverController@phoneVerify')
        ->name('driver.account.phoneVerify');

    Route::post('driver/account/documents', 'Driver\AppDriverController@getVerificationDocuments')
        ->name('driver.account.documents');
    Route::post('driver/account/document/update', 'AppUsersController@updateVerificationDocumentStatus')
        ->name('driver.account.document.status');
    Route::get('driver.searchDrivers', 'Driver\AppDriverController@searchDrivers')->name('driver.search');
    Route::get('driver/vendor-wallet/{id}', 'PayoutController@getWalletBalance')->name('driver.getWalletBalance');

    // Driver recharge plans/settings (admin UI)
    Route::get('recharge-plans', 'RechargePlanController@index')->name('recharge-plans.index');
    Route::post('recharge-plans', 'RechargePlanController@store')->name('recharge-plans.store');
    Route::put('recharge-plans/{plan}', 'RechargePlanController@update')->name('recharge-plans.update');
    Route::delete('recharge-plans/{plan}', 'RechargePlanController@destroy')->name('recharge-plans.destroy');
    Route::post('recharge-plans/settings', 'RechargePlanController@updateSettings')->name('recharge-plans.settings');

    // Availability
    Route::resource('availabilities', 'AvailabilityController', ['except' => ['destroy']]);

    // Testimonial
    Route::delete('testimonials/destroy', 'TestimonialController@massDestroy')->name('testimonials.massDestroy');
    Route::post('testimonials/media', 'TestimonialController@storeMedia')->name('testimonials.storeMedia');
    Route::post('testimonials/ckmedia', 'TestimonialController@storeCKEditorImages')->name('testimonials.storeCKEditorImages');
    Route::resource('testimonials', 'TestimonialController');

    // Contact
    Route::delete('contact/delete-all', 'ContactController@deleteAll')->name('contact.deleteAll');
    Route::resource('contacts', 'ContactController', ['except' => ['destroy']]);
    Route::get('contact/delete/{id}', 'ContactController@delete')->name('contact.delete');
    // Route::resource('emails', 'EmailController', ['except' => ['destroy']]);
    Route::get('email-templates/{id}', 'EmailController@template')->name('email-templates');
    Route::post('email-template/create/{id}', 'EmailController@templatecreate')->name('email-template.create');
    Route::post('email-template/test/{id}', 'EmailController@sendTestMail')->name('email-template.test');

    // Review
    Route::resource('reviews', 'ReviewController', ['except' => ['destroy']]);
    Route::get('reviews/delete/{id}', 'ReviewController@delete')->name('reviews.delete');

    // Static Pages
    Route::delete('static-pages/destroy', 'StaticPagesController@massDestroy')->name('static-pages.massDestroy');
    Route::post('static-pages/media', 'StaticPagesController@storeMedia')->name('static-pages.storeMedia');
    Route::post('static-pages/ckmedia', 'StaticPagesController@storeCKEditorImages')->name('static-pages.storeCKEditorImages');
    Route::resource('static-pages', 'StaticPagesController');

    // Currency
    Route::delete('currency/destroy', 'CurrencyController@massDestroy')->name('currency.massDestroy');
    Route::resource('currency', 'CurrencyController');

    // All Packages
    Route::post('all-packages/media', 'AllPackagesController@storeMedia')->name('all-packages.storeMedia');
    Route::post('all-packages/ckmedia', 'AllPackagesController@storeCKEditorImages')->name('all-packages.storeCKEditorImages');
    Route::resource('all-packages', 'AllPackagesController', ['except' => ['destroy']]);

    // General Setting
    Route::controller(\App\Http\Controllers\Admin\GeneralSettingController::class)->group(function () {

        // General settings & project setup
        Route::get('general-settings', 'generalForm')->name('settings');
        Route::get('project-setup', 'projectSetup')->name('project_setup');
        Route::post('project-setup-update', 'projectSetupUpdate')->name('project_setup_update');
        Route::post('project-cleanup-update', 'projectCleanupUpdate')->name('project_cleanup_update');
        Route::post('add-configuration-wizard', 'addConfigurationWizard')->name('add_configuration_wizard');

        // Preferences & personalization
        Route::get('settings/preferences', 'preferences')->name('preferences');
        Route::post('add-personalization', 'addPersonalization')->name('addPersonalization');

        // Push notifications
        Route::get('settings/pushnotification', 'pushNotificationSetting')->name('pushnotification');
        Route::post('pushnotificationupdate', 'pushNotificationUpdate')->name('pushnotificationupdate');
        Route::post('sendusermessage', 'sendUserMessage')->name('sendusermessage');

        // Email settings
        Route::get('settings/email', 'emailSetting')->name('email');
        Route::post('settings/email/update', 'emailSettingUpdate')->name('email.update');
        Route::post('settings/email/test', 'sendTestMail')->name('email.test');

        // Fees
        Route::get('settings/fees', 'fees')->name('fees');
        Route::post('fees-setup-add', 'FeesSetupAdd')->name('FeesSetupadd');

        // Language
        Route::get('settings/language', 'language')->name('language');
        Route::get('add-language', 'addLanguage')->name('addlanguage');
        Route::post('add-language-data', 'addLanguageData')->name('addlanguagedata');
        Route::get('edit-language/{id}', 'editLanguage')->name('editlanguage');
        Route::post('edit-language-data/{id}', 'editLanguageData')->name('editlanguagedata');
        Route::get('delete-language/{id}', 'deleteLanguage')->name('deletelanguage');

        // API informations
        Route::get('settings/api-informations', 'apiInformations')->name('api-informations');
        Route::post('apiauthenticationadd', 'apiAuthenticationAdd')->name('apiauthenticationadd');
    });

    // SMS settings
    Route::get('settings/sms', [GeneralSettingController::class, 'smsSetting'])->name('smssetting');
    Route::post('smsupdate', [GeneralSettingController::class, 'smsUpdate'])->name('smsupdate');
    Route::get('settings/msg91', [GeneralSettingController::class, 'Msg91'])->name('msg91');
    Route::post('msg91update', [GeneralSettingController::class, 'msg91Update'])->name('msg91update');
    Route::get('settings/twillio', [GeneralSettingController::class, 'twillioSetting'])->name('twilliosetting');
    Route::post('settings/updatetwillio', [GeneralSettingController::class, 'twillioSmsUpdate'])->name('updatetwillio');
    Route::get('settings/nexmo', [GeneralSettingController::class, 'nexmoSetting'])->name('nexmosetting');
    Route::post('settings/updatenexmo', [GeneralSettingController::class, 'UpdateNexmoSetting'])->name('updatenexmosetting');
    Route::get('settings/2factor', [GeneralSettingController::class, 'twoFactor'])->name('twofactor');
    Route::post('settings/update2factor', [GeneralSettingController::class, 'UpdateTwoFactor'])->name('updatetwofactor');
    Route::get('settings/sinch', [GeneralSettingController::class, 'sinchSetting'])->name('sinchSetting');
    Route::post('settings/updateSinch', [GeneralSettingController::class, 'sinchSmsUpdate'])->name('updateSinch');

    Route::get('currency', [CurrencyController::class, 'index'])->name('currency');

    // Currency settings
    Route::get('settings/currency', [GeneralSettingController::class, 'currencySetting'])->name('currencySetting');
    Route::post('settings/updatetCurrencyAuthKey', [GeneralSettingController::class, 'updateCurrencyAuthKey'])->name('updateCurrencyAuthKey');

    // Booking Settings
    Route::get('settings/booking', [GeneralSettingController::class, 'bookingSetting'])->name('bookingSetting');
    Route::post('settings/updateBookingSetting', [GeneralSettingController::class, 'updateBookingSetting'])->name('updateBookingSetting');

    // App Screen Settings
    Route::get('settings/appscreensetting', [GeneralSettingController::class, 'appScreenSetting'])->name('appscreensetting');
    Route::post('settings/updateappscreensetting', [GeneralSettingController::class, 'updateAppScreenSetting'])->name('updateappscreensetting');

    Route::prefix('payment-methods')->name('payment_methods.')
        ->controller(\App\Http\Controllers\Admin\PaymentMethodController::class)
        ->group(function () {
            Route::get('{method}', 'index')->name('index');
            Route::post('{method}', 'update')->name('update');
            Route::post('{method}/status', 'updateStatus')->name('status');
            Route::post('online/status', 'updateOnlineStatus')->name('online.status');
        });

    Route::post('update-nonage-status', [GeneralSettingController::class, 'updateNonageStatus'])->name('update-nonage-status');
    Route::post('update-twillio-status', [GeneralSettingController::class, 'updateTwillioeStatus'])->name('update-twillio-status');
    Route::post('update-sms-provider-name', [GeneralSettingController::class, 'updateSMSProviderName'])->name('update-sms-provider-name');
    Route::post('updatePushNotificationStatus', [GeneralSettingController::class, 'updatePushNotificationStatus'])->name('updatePushNotificationStatus');
    Route::post('update-auto-fill-otp', [GeneralSettingController::class, 'updateAutoFillOTP'])->name('update-auto-fill-otp');
    Route::post('set-multicurrency', [GeneralSettingController::class, 'setMulticurrency'])->name('set-multicurrency');
    Route::get('settings/app-settings', [GeneralSettingController::class, 'showAppSettings'])->name('settings.app.show');
    Route::put('settings/update-app-settings', [GeneralSettingController::class, 'updateAppSettings'])->name('settings.app.update');

    // Add Coupons
    Route::delete('add-coupons/destroy', 'AddCouponsController@massDestroy')->name('add-coupons.massDestroy');
    Route::post('add-coupons/media', 'AddCouponsController@storeMedia')->name('add-coupons.storeMedia');
    Route::post('add-coupons/ckmedia', 'AddCouponsController@storeCKEditorImages')->name('add-coupons.storeCKEditorImages');
    Route::resource('add-coupons', 'AddCouponsController');

    // Payout
    Route::resource('payouts', 'PayoutController', ['except' => ['destroy']]);
    Route::post('payouts/media', 'PayoutController@storeMedia')->name('payouts.storeMedia');
    Route::get('payoutVendorSearch', 'PayoutController@payoutVendorSearch')->name('payoutVendorSearch');
    Route::post('update-payout-status/{payout}', 'PayoutController@updateStatus')->name('payouts.updateStatus');
    Route::post('payout/reject', 'PayoutController@rejectPayout')->name('payout.reject');

    // Cancelation
    Route::get('cancellation', 'Cancellation@cancellation')->name('cancellation.index');
    Route::get('cancellation/create', 'Cancellation@cancellationcreate')->name('cancellation.create');
    Route::post('cancellation/store', 'Cancellation@cancellationstore')->name('cancellation.store');
    Route::get('cancellation/edit/{order_cancellation_id}', 'Cancellation@cancellationedit')->name('cancellation.edit');
    Route::post('cancellation.update/{order_cancellation_id}', 'Cancellation@cancellationupdate')->name('cancellation.update');
    Route::delete('cancellation/delete/{order_cancellation_id}', 'Cancellation@cancellationdestroy')->name('cancellation.destroy');
    Route::delete('cancellation/delete-cancellation', 'Cancellation@deleteCancellationAll')->name('cancellation.deleteCancellationAll');

    // Ticket
    Route::get('ticket', 'TicketController@index')->name('ticket.index');
    Route::get('ticket/replies/{id}', 'TicketController@reply')->name('ticket.replies');
    Route::get('ticket/threads/{id}', 'TicketController@threads')->name('ticket.thread');
    Route::post('ticket/threads/create/{id}', 'TicketController@create')->name('ticket.thread.create');
    Route::delete('ticket/delete/{id}', 'TicketController@destroy')->name('ticket.destroy');
    Route::post('ticket/delete-all', 'TicketController@ticketDeleteAll')->name('ticket.deleteAll');

    // Report----------------
    Route::get('report-page', 'ReportController@index')->name('report-page.index');
    // Finance
    Route::get('finance', 'FinanceController@index')->name('finance');
    // Route::post('finance/delete-all', 'FinanceController@financeDeleteAll')->name('finance.deleteAll');
    Route::get('finance/vendor-commission', 'FinanceController@vendor')->name('finance.vendor-commission');
    // tostar message

    Route::post('update-appPackage-status', 'AllPackagesController@updateStatus')->name('update-appPackage-status');
    Route::post('update-addCoupon-status', 'AddCouponsController@updateStatus')->name('update-addCoupon-status');
    Route::post('update-staticpage-status', 'StaticPagesController@updateStatus')->name('update-staticpage-status');
    Route::post('update-cancellation-status', 'Cancellation@updateStatus')->name('update-cancellation-status');
    Route::post('update-slider-status', 'SliderController@updateStatus')->name('update-slider-status');
    Route::post('update-cities-status', 'CitiesController@updateStatus')->name('update-cities-status');
    Route::post('update-contact-status', 'ContactController@updateStatus')->name('update-contact-status');
    Route::post('update-currency-status', 'CurrencyController@updateStatus')->name('update-currency-status');

    // Rules item
    Route::resource('item-rule', 'ItemRulesController');
    Route::get('item/delete/{id}', 'ItemRulesController@Delete')->name('item-rule.delete');
    Route::post('update-item-rule-status', 'ItemRulesController@updateStatus')->name('update-item-rule-status');
    Route::delete('item/delete-item', 'ItemRulesController@allDelete')->name('item-rule.deleteAll');

    // SOS
    // SOS Management
    Route::resource('sos', 'SOSController');
    Route::get('sos/delete/{id}', 'SOSController@delete')->name('sos.delete');
    Route::post('update-sos-status', 'SOSController@updateStatus')->name('sos.update-status');
    Route::delete('sos/delete-all', 'SOSController@deleteAll')->name('sos.deleteAll');

    Route::post('update-wallet-status', 'BookingController@updateStatus')->name('update-wallet-status');
});
Route::group(['prefix' => 'profile', 'as' => 'profile.', 'namespace' => 'Auth', 'middleware' => ['auth']], function () {
    // Change password
    if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php'))) {
        Route::get('password', 'ChangePasswordController@edit')->name('password.edit');
        Route::post('password', 'ChangePasswordController@update')->name('password.update');
        Route::post('profile', 'ChangePasswordController@updateProfile')->name('password.updateProfile');
        Route::post('profile/destroy', 'ChangePasswordController@destroy')->name('password.destroyProfile');
    }
});
