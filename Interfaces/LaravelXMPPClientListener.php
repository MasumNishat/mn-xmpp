<?php
namespace PhpPush\XMPP\Interfaces;

interface LaravelXMPPClientListener {
    /**
     * @param array $data
     * @return mixed
     */
    function onError(array $data): void;

    function onConnect();

    function onDisconnect();
    function onRead(string $data): void;
}
