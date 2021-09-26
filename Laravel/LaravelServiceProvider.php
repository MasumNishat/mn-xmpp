<?php

namespace PhpPush\XMPP\Laravel;

use Illuminate\Support\ServiceProvider;
use PhpPush\XMPP\Laravel\Commands\phpPush;

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

    }
}
