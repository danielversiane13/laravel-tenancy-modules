<?php

namespace Modules\Tenant\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
  /**
   * The module namespace to assume when generating URLs to actions.
   *
   * @var string
   */
  protected $moduleNamespace = 'Modules\Tenant\Http\Controllers';

  /**
   * Called before routes are registered.
   *
   * Register any model bindings or pattern based filters.
   *
   * @return void
   */
  public function boot()
  {
    parent::boot();
  }

  /**
   * Define the routes for the application.
   *
   * @return void
   */
  public function map()
  {
    $this->configureRateLimiting();

    Route::middleware('api')
      ->namespace($this->moduleNamespace)
      ->group(module_path('Tenant', '/Routes/api.php'));
  }

  /**
   * Configure the rate limiters for the application.
   *
   * @return void
   */
  protected function configureRateLimiting()
  {
    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
    });
  }
}
