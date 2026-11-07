<?php

use App\Modules\Oee\OeeAccess;
use App\Modules\Oee\OeeSection;

return [

    /*
    |--------------------------------------------------------------------------
    | Module Sections
    |--------------------------------------------------------------------------
    |
    | Backed enums whose cases are stored on team memberships. Dashboard stays
    | on App\Modules\Platform\AppSection. Adding a module is one class in this list.
    |
    */

    'sections' => [
        OeeSection::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Access
    |--------------------------------------------------------------------------
    |
    | Classes that expose permissions() for Inertia and fallback() for users
    | who cannot open the home dashboard. Order is the redirect priority.
    |
    */

    'access' => [
        OeeAccess::class,
    ],

];
