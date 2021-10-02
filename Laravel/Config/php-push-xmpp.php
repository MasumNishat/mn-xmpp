<?php

use PhpPush\XMPP\Extensions\XEP0004;
use PhpPush\XMPP\Extensions\XEP0030;
use PhpPush\XMPP\Extensions\XEP0060;
use PhpPush\XMPP\Extensions\XEP0077;
use PhpPush\XMPP\Extensions\XEP0199;

return [
    'admin-user' => env('PHP_PUSH_XMPP_ADMIN_USERNAME', 'admin'),
    'admin-password' => env('PHP_PUSH_XMPP_ADMIN_PASSWORD'),
    'host' => env('PHP_PUSH_XMPP_HOST', parse_url(env('APP_URL'))['host']),
    'protocol' => env('PHP_PUSH_XMPP_PROTOCOL', 'tcp'),
    'port' => env('PHP_PUSH_XMPP_PORT', 5222),
    'autodetect-port' => (bool)env('PHP_PUSH_XMPP_AUTODETECT_PORT', false),
    'resource' => [
        'prefix' => 'MeCloak_',
        'suffix' => '_res',
    ],
    'headers' => [
        'ssl' => [
            'verify_peer' => false,
            'verify_depth' => 5,
        ]
    ],
    'auth-type' => env('PHP_PUSH_XMPP_AUTH_TYPE', 'SCRAM-SHA-512'),
    'listeners' => [
        'server' => '',
        'client' => '',
    ],

    /**
     * depends extensions should be loaded first
     */
    'extensions' => [
        [
            XEP0077::class, //@internal
            []
        ],
        [
            XEP0030::class, //@internal maximum extensions depend on this.
            []
        ],
        [
            XEP0199::class, //@internal
            [
                'c2c_ping' => true,
                'c2s_ping' => true,
                'c2s_ping_interval' => 300, //in second
                'c2c_ping_interval' => 100, //in second
                'c2s_ping_timeout' => 2, //in second
                'c2c_ping_timeout' => 2, //in second
            ]
        ],
        [
            XEP0004::class,
            []
        ],
        [
            XEP0060::class,
            []
        ]
    ],
    'read-retry' => 3,
];
