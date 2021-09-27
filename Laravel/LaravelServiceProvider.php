<?php

namespace PhpPush\XMPP\Laravel;

use Illuminate\Support\ServiceProvider;
use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Laravel\Commands\phpPush;
use PhpPush\XMPP\Core\XMPPSend;

class LaravelServiceProvider extends ServiceProvider {

    public function boot () {

        $this->mergeConfigFrom(
            __DIR__.'/Config/php-push-xmpp.php', 'phpPushXMPPConfig'
        );

        $this->publishes([
            __DIR__.'/Config/php-push-xmpp.php' => config_path('php-push-xmpp.php'),
        ]);
        $this->commands([
            phpPush::class
        ]);
    }
    public function register() {
        $this->app->singleton(LaravelXMPPConnectionManager::class, function ($app) {
            return LaravelXMPPConnectionManager::getInstance(config('php-push-xmpp'));
        });
        $this->app->singleton(XMPPSend::class, function ($app) {
            return XMPPSend::getInstance();
        });
    }
}
