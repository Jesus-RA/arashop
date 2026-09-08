<?php

return [

    'fx' => [
        'usd_to_mxn' => env('ARASHOP_FX_USD_TO_MXN', 18.00),
    ],

    'margin_percent' => env('ARASHOP_DEFAULT_MARGIN_PERCENT', 35),

    'platform_fee_percent' => env('ARASHOP_DEFAULT_PLATFORM_FEE_PERCENT', 10),

    'packaging_fee_mxn' => env('ARASHOP_DEFAULT_PACKAGING_FEE_MXN', 15),

    'discount_percent' => env('ARASHOP_DEFAULT_DISCOUNT_PERCENT', 0),

];
