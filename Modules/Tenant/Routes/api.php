<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

Route::middleware([InitializeTenancyByRequestData::class])->group(function () {
  Route::get('/', function () {
    return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
  });
});

Route::apiResource('tenants', 'TenantController');
