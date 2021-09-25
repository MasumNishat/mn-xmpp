<?php
namespace MN\XMPP\Interface;

interface XMPPAuth {
    /**
     * @param XMPPConnectionManager $XMPPConnectionManager
     * @return XMPPAuth
     */
    public static function attach(XMPPConnectionManager $XMPPConnectionManager): XMPPAuth;

    /**
     * @param string $method
     * @return bool
     */
    function auth(string $method=''): bool;
}
