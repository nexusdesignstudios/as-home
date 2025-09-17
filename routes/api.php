<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\PropertyTermsController;
use App\Http\Controllers\HotelRoomTypeController;
use App\Http\Controllers\HotelRoomController;
use App\Http\Controllers\AddonsPackageController;
use App\Http\Controllers\HotelApartmentTypeController;
use App\Http\Controllers\PaymobController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*********************************************************************** */
/** Property */
Route::post('set_property_total_click', [ApiController::class, 'set_property_total_click']);
Route::get('get_nearby_properties', [ApiController::class, 'get_nearby_properties']);
Route::get('compare-properties', [ApiController::class, 'compareProperties']);
Route::get('get-cities-data', [ApiController::class, 'getCitiesData']);
/*********************************************************************** */

/** Users */
Route::post('user_signup', [ApiController::class, 'user_signup']);
Route::post('user-register', [ApiController::class, 'userRegister']);

Route::get('forgot-password', [ApiController::class, 'forgotPassword']);
/*********************************************************************** */

/** Others */
Route::post('contct_us', [ApiController::class, 'contct_us']);
Route::get('get-slider', [ApiController::class, 'getSlider']);
Route::get('get_facilities', [ApiController::class, 'get_facilities']);
Route::get('get_seo_settings', [ApiController::class, 'get_seo_settings']);
Route::get('get_report_reasons', [ApiController::class, 'get_report_reasons']);
Route::get('get-active-room-types', [HotelRoomTypeController::class, 'getActiveRoomTypes']);
Route::get('get-hotel-apartment-types', [HotelApartmentTypeController::class, 'getHotelApartmentTypes']);
Route::get('get-property-question-fields', [ApiController::class, 'getPropertyQuestionFields']);
/*********************************************************************** */

/** Extra */
Route::get('get_articles', [ApiController::class, 'get_articles']);
Route::get('get_categories', [ApiController::class, 'get_categories']);
Route::get('get_categories_by_classification', [ApiController::class, 'get_categories_by_classification']);
Route::get('get_languages', [ApiController::class, 'get_languages']);
Route::get('get_category_classifications', [ApiController::class, 'get_category_classifications']);
/*********************************************************************** */

/** Only Declared */
Route::match(array('GET', 'POST'), 'app_payment_status', [ApiController::class, 'app_payment_status']);
Route::match(array('GET', 'POST'), 'flutterwave-payment-status', [ApiController::class, 'flutterwavePaymentStatus']);
/*********************************************************************** */

/** Paymob Payment Routes */
Route::post('payments/paymob/callback', [PaymobController::class, 'handleCallback']);
Route::get('payments/paymob/return', [PaymobController::class, 'handleReturn'])->name('payments.paymob.return');
Route::post('payments/paymob/webhook', [PaymobController::class, 'handleWebhook']);
/*********************************************************************** */

/** Confirmation needed */
Route::get('get_advertisement', [ApiController::class, 'get_advertisement']);
Route::post('mortgage_calc', [ApiController::class, 'mortgage_calc']);

Route::get('get_app_settings', [ApiController::class, 'get_app_settings']);
/*********************************************************************** */

/** Authenticated APIS */
Route::group(['middleware' => ['auth:sanctum']], function () {
    /*********************************************************************** */
    /** Property */
    Route::post('post_property', [ApiController::class, 'post_property']);
    Route::post('update_post_property', [ApiController::class, 'update_post_property']);
    Route::post('update_property_status', [ApiController::class, 'update_property_status']);
    Route::post('delete_property', [ApiController::class, 'delete_property']);
    Route::post('interested_users', [ApiController::class, 'interested_users']);
    Route::post('change-property-status', [ApiController::class, 'changePropertyStatus']);
    Route::post('save-property-question-answers', [ApiController::class, 'savePropertyQuestionAnswers']);
    Route::post('send-property-client-email', [ApiController::class, 'sendPropertyClientEmail']);
    Route::get('get_favourite_property', [ApiController::class, 'get_favourite_property']);
    Route::get('get_property_inquiry', [ApiController::class, 'get_property_inquiry']);
    Route::get('get-added-properties', [ApiController::class, 'getAddedProperties']);
    /*********************************************************************** */

    /** Users */
    Route::post('update_profile', [ApiController::class, 'update_profile']);
    Route::post('delete_user', [ApiController::class, 'delete_user']);
    Route::post('before-logout', [ApiController::class, 'beforeLogout']);
    Route::get('get-user-data', [ApiController::class, 'getUserData']);
    Route::get('get_user_recommendation', [ApiController::class, 'get_user_recommendation']);
    /*********************************************************************** */

    /** Chat */
    Route::post('send_message', [ApiController::class, 'send_message']);
    Route::post('delete_chat_message', [ApiController::class, 'delete_chat_message']);
    Route::post('block-user', [ApiController::class, 'blockChatUser']);
    Route::post('unblock-user', [ApiController::class, 'unBlockChatUser']);
    Route::post('update-chat-approval', [ApiController::class, 'updateChatApprovalStatus']);
    Route::get('get_messages', [ApiController::class, 'get_messages']);
    Route::get('get_chats', [ApiController::class, 'get_chats']);
    /*********************************************************************** */

    /** Package */
    Route::post('assign_package', [ApiController::class, 'assign_package']);
    Route::get('check-package-limit', [ApiController::class, 'checkPackageLimit']);
    Route::delete('remove-all-packages', [ApiController::class, 'removeAllPackages']);
    /*********************************************************************** */

    /** Agents */
    Route::get('get-agent-verification-form-fields', [ApiController::class, 'getAgentVerificationFormFields']);
    Route::get('get-agent-verification-form-values', [ApiController::class, 'getAgentVerificationFormValues']);
    Route::post('apply-agent-verification', [ApiController::class, 'applyAgentVerification']);
    /*********************************************************************** */

    /** Hotel Addons */
    Route::get('get-hotel-addon-fields', [ApiController::class, 'getHotelAddonFields']);
    /*********************************************************************** */

    /** Property Taxes */
    Route::get('get-property-taxes', [ApiController::class, 'getPropertyTaxes']);
    Route::post('store-property-taxes', [ApiController::class, 'storePropertyTaxes']);
    /*********************************************************************** */

    /** Others */

    // Payment
    Route::post('flutterwave', [ApiController::class, 'flutterwave']);
    Route::post('createPaymentIntent', [ApiController::class, 'createPaymentIntent']);
    Route::post('confirmPayment', [ApiController::class, 'confirmPayment']);
    Route::get('get_payment_settings', [ApiController::class, 'get_payment_settings']);
    Route::get('get_payment_details', [ApiController::class, 'getPaymentTransactionDetails']);
    Route::get('paypal', [ApiController::class, 'paypal']);
    Route::post('generate-razorpay-orderid', [ApiController::class, 'generateRazorpayOrderId']);

    Route::post('create-payment-intent', [ApiController::class, 'getPaymentIntent']);
    Route::post('payment-transaction-fail', [ApiController::class, 'makePaymentTransactionFail']);

    // Paymob Payment
    Route::post('create-paymob-payment', [PaymobController::class, 'createPaymentIntent']);

    // Paymob Refund
    Route::post('paymob-refund', [PaymobController::class, 'processRefund']);
    Route::get('paymob-refund-status', [PaymobController::class, 'getRefundStatus']);
    Route::post('paymob-cancel-aman-transaction', [PaymobController::class, 'cancelAmanTransaction']);
    Route::post('paymob-bulk-transaction-inquiry', [PaymobController::class, 'bulkTransactionInquiry']);
    Route::get('paymob-user-budget', [PaymobController::class, 'getUserBudget']);
    Route::get('paymob-bank-codes', [PaymobController::class, 'getBankCodes']);
    Route::get('paymob-bank-transaction-types', [PaymobController::class, 'getBankTransactionTypes']);

    // Payment Receipt
    Route::get('get-payment-receipt', [ApiController::class, 'getPaymentReceipt']);

    // Bank Transfer Apis
    Route::post('initiate-bank-transfer', [ApiController::class, 'initiateBankTransaction']);
    Route::post('upload-bank-receipt-file', [ApiController::class, 'uploadBankReceiptFile']);
    // Other's APIs
    Route::get('get_notification_list', [ApiController::class, 'get_notification_list']);
    /*********************************************************************** */

    /** Personalised Interest */
    Route::get('personalised-fields', [ApiController::class, 'getUserPersonalisedInterest']);
    Route::post('personalised-fields', [ApiController::class, 'storeUserPersonalisedInterest']);
    Route::delete('personalised-fields', [ApiController::class, 'deleteUserPersonalisedInterest']);
    /*********************************************************************** */

    /** Extra */
    Route::post('store_advertisement', [ApiController::class, 'store_advertisement']);
    Route::post('post_project', [ApiController::class, 'post_project']);
    Route::post('delete_project', [ApiController::class, 'delete_project']);
    Route::get('get_interested_users', [ApiController::class, 'getInterestedUsers']);
    /*********************************************************************** */

    /** Confirmation needed */
    Route::post('remove_post_images', [ApiController::class, 'remove_post_images']);
    Route::post('set_property_inquiry', [ApiController::class, 'set_property_inquiry']);
    Route::post('add_favourite', [ApiController::class, 'add_favourite']);
    Route::post('delete_favourite', [ApiController::class, 'delete_favourite']);
    Route::post('user_purchase_package', [ApiController::class, 'user_purchase_package']);
    Route::post('delete_advertisement', [ApiController::class, 'delete_advertisement']);
    Route::post('delete_inquiry', [ApiController::class, 'delete_inquiry']);
    Route::post('user_interested_property', [ApiController::class, 'user_interested_property']);
    Route::post('add_reports', [ApiController::class, 'add_reports']);
    Route::post('add_edit_user_interest', [ApiController::class, 'add_edit_user_interest']);
    /*********************************************************************** */


    /** Projects */
    Route::get('get-added-projects', [ApiController::class, 'getAddedProjects']);
    Route::get('get-project-detail', [ApiController::class, 'getProjectDetail']);
    Route::post('change-project-status', [ApiController::class, 'changeProjectStatus']);
    /*********************************************************************** */

    /** General Apis */
    Route::get('get-featured-data', [ApiController::class, 'getFeaturedData']);

    /** Property Terms */
    Route::apiResource('property-terms', PropertyTermsController::class);
    Route::get('get-terms-by-classification/{classificationId}', [PropertyTermsController::class, 'getTermsByClassification']);
    /*********************************************************************** */

    /** Hotel Room Types */
    Route::apiResource('hotel-room-types', HotelRoomTypeController::class);

    /** Hotel Rooms */
    Route::apiResource('hotel-rooms', HotelRoomController::class);

    /** Addons Packages */
    Route::apiResource('addons-packages', AddonsPackageController::class);
    /*********************************************************************** */
});


/** Using Auth guard sanctum for get the data with or without authentication */

/** Property */
Route::get('get_property', [ApiController::class, 'get_property']);
Route::get('get-property-list', [ApiController::class, 'getPropertyList']);
Route::get('get-facilities-for-filter', [ApiController::class, 'getFacilitiesForFilter']);
Route::get('get-all-similar-properties', [ApiController::class, 'getAllSimilarProperties']);
/*********************************************************************** */

/** Projects */
Route::get('get-projects', [ApiController::class, 'getProjects']);
/*********************************************************************** */

/** User */
Route::get('get-otp', [ApiController::class, 'getOtp']);
Route::get('verify-otp', [ApiController::class, 'verifyOtp']);
/*********************************************************************** */

/** Package */
// Route::get('get_package', [ApiController::class, 'get_package']);
Route::get('get-package', [ApiController::class, 'getPackages']);
Route::get('get-features', [ApiController::class, 'getFeatures']);
/*********************************************************************** */

/** Agents */
Route::get('agent-list', [ApiController::class, 'getAgentList']);
Route::get('agent-properties', [ApiController::class, 'getAgentProperties']);
/*********************************************************************** */

/** Settings */
Route::get('web-settings', [ApiController::class, 'getWebSettings']);
Route::get('app-settings', [ApiController::class, 'getAppSettings']);
Route::get('get-active-room-types', [HotelRoomTypeController::class, 'getActiveRoomTypes']);
/*********************************************************************** */

/** Mortgage Calculator */
Route::get('mortgage-calculator', [ApiController::class, 'calculateMortgageCalculator']);
/*********************************************************************** */

/** Extra */
Route::post('get_system_settings', [ApiController::class, 'get_system_settings']);
Route::get('homepage-data', [ApiController::class, 'homepageData']);
Route::get('faqs', [ApiController::class, 'getFaqData']);
Route::get('privacy-policy', [ApiController::class, 'getPrivacyPolicy']);
Route::get('terms-conditions', [ApiController::class, 'getTermsAndConditions']);


// Deep Link
Route::get('deep-link', [ApiController::class, 'deepLink']);

// Temp
Route::get('remove-account-temp', [ApiController::class, 'removeAccountTemp']);
/*********************************************************************** */

/** Hotel Room */
Route::get('search-available-rooms', [HotelRoomController::class, 'searchAvailableRooms']);

/*
|--------------------------------------------------------------------------
| Reservation Routes
|--------------------------------------------------------------------------
*/

// Check availability
Route::post('/check-availability', [App\Http\Controllers\ReservationController::class, 'checkAvailability']);

// Customer routes (authenticated)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/reservations', [App\Http\Controllers\ReservationController::class, 'createReservation']);
    Route::get('/reservations', [App\Http\Controllers\ReservationController::class, 'getCustomerReservations']);
    Route::get('/reservations/{id}', [App\Http\Controllers\ReservationController::class, 'getReservation']);
    Route::post('/reservations/{id}/cancel', [App\Http\Controllers\ReservationController::class, 'cancelReservation']);
    Route::post('/reservations/{id}/update-status', [App\Http\Controllers\ReservationsAdminController::class, 'updateStatusApi'])->name('api.reservations.update-status');
    Route::get('/property-owner-reservations', [App\Http\Controllers\ReservationController::class, 'getPropertyOwnerReservations']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/reservations', [App\Http\Controllers\ReservationController::class, 'getAllReservations']);
    Route::put('/reservations/{id}/status', [App\Http\Controllers\ReservationController::class, 'updateReservationStatus']);
});

/* Reservation Payment Routes */
Route::post('/payments/paymob/callback', [App\Http\Controllers\PaymobController::class, 'handleCallback']);
Route::get('/payments/paymob/return', [App\Http\Controllers\PaymobController::class, 'handleReturn']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/payments/create-payment-intent', [App\Http\Controllers\PaymobController::class, 'createPaymentIntent']);
    Route::post('/payments/refund', [App\Http\Controllers\PaymobController::class, 'processRefund']);
    Route::get('/payments/refund-status', [App\Http\Controllers\PaymobController::class, 'getRefundStatus']);
    Route::get('/reservations/{id}/payment', [App\Http\Controllers\ReservationController::class, 'getReservationPayment']);
    Route::post('/reservations/with-payment', [App\Http\Controllers\ReservationController::class, 'createReservationWithPayment']);
});
