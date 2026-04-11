<?php

return [
    'sid' => env('EXOTEL_SID'),
    'token' => env('EXOTEL_TOKEN'),
    'from' => env('EXOTEL_VIRTUAL_NUMBER'), // exophone
    'base_url' => env('EXOTEL_BASE_URL', 'https://api.exotel.com/v1/Accounts'),
    // Shared secret used to validate status callbacks from Exotel.
    'callback_token' => env('EXOTEL_CALLBACK_TOKEN'),
];
