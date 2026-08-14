<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Institution Settings
    |--------------------------------------------------------------------------
    | Values specific to the deploying institution. Change these when
    | deploying to a different school — do NOT hard-code them in controllers.
    |
    */

    'name'         => env('INSTITUTION_NAME', 'Holy Angel University'),
    'short_name'   => env('INSTITUTION_SHORT_NAME', 'HAU'),
    'email_domain' => env('INSTITUTION_EMAIL_DOMAIN', 'hau.edu.ph'),
];
