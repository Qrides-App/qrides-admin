<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'firebase_web' => [
        'apiKey' => env('FIREBASE_WEB_API_KEY', 'AIzaSyDJmfSsQoJwx9OCf6t3m-0tcXNT6NilbcI'),
        'authDomain' => env('FIREBASE_WEB_AUTH_DOMAIN', 'unibookervehicle.firebaseapp.com'),
        'databaseURL' => env('FIREBASE_WEB_DATABASE_URL', 'https://unibookervehicle-default-rtdb.firebaseio.com'),
        'projectId' => env('FIREBASE_WEB_PROJECT_ID', 'unibookervehicle'),
        'storageBucket' => env('FIREBASE_WEB_STORAGE_BUCKET', 'unibookervehicle.appspot.com'),
        'messagingSenderId' => env('FIREBASE_WEB_MESSAGING_SENDER_ID', '951328556833'),
        'appId' => env('FIREBASE_WEB_APP_ID', '1:951328556833:web:4a922ffcaa3df4341de060'),
    ],

];
