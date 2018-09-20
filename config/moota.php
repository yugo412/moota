<?php

return [
    /**
     * Set default API host for Moota developer.
     * @link https://app.moota.co/developer/docs#base-url-dan-autentikasi
     */
    'host' => env('MOOTA_HOST', 'https://app.moota.co/api/v1/'),

    /**
     * Provided token from Moota.
     * @link https://app.moota.co/settings?tab=api
     */
    'token' => env('MOOTA_TOKEN'),

    /**
     * HTTP request configurations.
     */
    'http' => [
        /**
         * Max total request in second.
         */
        'timeout' => env('MOOTA_TIMEMOUT', 30),
    ],
];
