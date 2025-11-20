<?php

return [
    // Required token for MonoBank API access
    'token' => env('MONOBANK_TOKEN'),
    
    // Public key for webhook signature verification (if used)
    'public_key' => env('MONOBANK_PUBLIC_KEY'),
];