<?php

namespace PhpPush\XMPP\Laravel;

use Illuminate\Support\ServiceProvider;
use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Laravel\Commands\phpPush;
use PhpPush\XMPP\Core\XMPPSend;
use PhpPush\XMPP\UI\XEP0004;
use PhpPush\XMPP\UI\XEP0030;
use PhpPush\XMPP\UI\XEP0060;

class LaravelServiceProvider extends ServiceProvider
{

    public function boot()
    {

        $this->mergeConfigFrom(
            __DIR__ . '/Config/php-push-xmpp.php', 'phpPushXMPPConfig'
        );

        $this->publishes([
            __DIR__ . '/Config/php-push-xmpp.php' => config_path('php-push-xmpp.php'),
        ]);
        $this->commands([
            phpPush::class
        ]);
    }

    public function register()
    {
        $this->app->singleton(XMPPSend::class, function ($app) {
            return XMPPSend::getInstance();
        });
        $this->app->singleton(DataManager::class, function ($app) {
            return DataManager::getInstance();
        });
        $this->app->singleton(XEP0030::class, function ($app) {
            return XEP0030::getInstance();
        });
        $this->app->singleton(XEP0004::class, function ($app) {
            return XEP0004::getInstance();
        });
        $this->app->singleton(XEP0060::class, function ($app) {
            return XEP0060::getInstance();
        });

    }
}
