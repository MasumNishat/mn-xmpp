<?php
namespace PhpPush\XMPP\Interfaces;

use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;

interface XMPPAuth {
    /**
     * @param LaravelXMPPConnectionManager $connection
     * @return XMPPAuth
     */
    public static function attach(LaravelXMPPConnectionManager $connection): XMPPAuth;

    /**
     * @param string $method
     * @return bool
     */
    function auth(string $method=''): bool;
}
