<?php

namespace Modules\Tenant\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class TenantServiceProvider extends ServiceProvider
{
  /**
   * @var string $moduleName
   */
  protected $moduleName = 'Tenant';

  /**
   * @var string $moduleNameLower
   */
  protected $moduleNameLower = 'tenant';

  /**
   * Boot the application events.
   *
   * @return void
   */
  public function boot()
  {
    /**
     * Boot tenants events and middleware priorities.
     */
    $this->bootEvents();
    $this->makeTenancyMiddlewareHighestPriority();

    /**
     * Register Translations and Configs.
     */
    $this->registerTranslations();
    $this->registerConfig();
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {
    $this->app->register(RouteServiceProvider::class);
  }

  public function events()
  {
    return [
      // Tenant events
      Events\CreatingTenant::class => [],
      Events\TenantCreated::class => [
        JobPipeline::make([
          Jobs\CreateDatabase::class,
          Jobs\MigrateDatabase::class,
          // Jobs\SeedDatabase::class,

          // Your own jobs to prepare the tenant.
          // Provision API keys, create S3 buckets, anything you want!

        ])->send(function (Events\TenantCreated $event) {
          return $event->tenant;
        })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
      ],
      Events\SavingTenant::class => [],
      Events\TenantSaved::class => [],
      Events\UpdatingTenant::class => [],
      Events\TenantUpdated::class => [],
      Events\DeletingTenant::class => [],
      Events\TenantDeleted::class => [
        JobPipeline::make([
          Jobs\DeleteDatabase::class,
        ])->send(function (Events\TenantDeleted $event) {
          return $event->tenant;
        })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
      ],

      // Domain events
      Events\CreatingDomain::class => [],
      Events\DomainCreated::class => [],
      Events\SavingDomain::class => [],
      Events\DomainSaved::class => [],
      Events\UpdatingDomain::class => [],
      Events\DomainUpdated::class => [],
      Events\DeletingDomain::class => [],
      Events\DomainDeleted::class => [],

      // Database events
      Events\DatabaseCreated::class => [],
      Events\DatabaseMigrated::class => [],
      Events\DatabaseSeeded::class => [],
      Events\DatabaseRolledBack::class => [],
      Events\DatabaseDeleted::class => [],

      // Tenancy events
      Events\InitializingTenancy::class => [],
      Events\TenancyInitialized::class => [
        Listeners\BootstrapTenancy::class,
      ],

      Events\EndingTenancy::class => [],
      Events\TenancyEnded::class => [
        Listeners\RevertToCentralContext::class,
      ],

      Events\BootstrappingTenancy::class => [],
      Events\TenancyBootstrapped::class => [],
      Events\RevertingToCentralContext::class => [],
      Events\RevertedToCentralContext::class => [],

      // Resource syncing
      Events\SyncedResourceSaved::class => [
        Listeners\UpdateSyncedResource::class,
      ],

      // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
      Events\SyncedResourceChangedInForeignDatabase::class => [],
    ];
  }

  protected function bootEvents()
  {
    foreach ($this->events() as $event => $listeners) {
      foreach (array_unique($listeners) as $listener) {
        if ($listener instanceof JobPipeline) {
          $listener = $listener->toListener();
        }

        Event::listen($event, $listener);
      }
    }
  }

  protected function makeTenancyMiddlewareHighestPriority()
  {
    $tenancyMiddleware = [
      // Even higher priority than the initialization middleware
      Middleware\PreventAccessFromCentralDomains::class,

      Middleware\InitializeTenancyByDomain::class,
      Middleware\InitializeTenancyBySubdomain::class,
      Middleware\InitializeTenancyByDomainOrSubdomain::class,
      Middleware\InitializeTenancyByPath::class,
      Middleware\InitializeTenancyByRequestData::class,
    ];

    foreach (array_reverse($tenancyMiddleware) as $middleware) {
      $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
    }
  }

  /**
   * Register config.
   *
   * @return void
   */
  protected function registerConfig()
  {
    $this->publishes([
      module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
    ], 'config');
    $this->mergeConfigFrom(
      module_path($this->moduleName, 'Config/config.php'),
      $this->moduleNameLower
    );
  }

  /**
   * Register translations.
   *
   * @return void
   */
  public function registerTranslations()
  {
    $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

    if (is_dir($langPath)) {
      $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
    } else {
      $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
    }
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {
    return [];
  }
}
