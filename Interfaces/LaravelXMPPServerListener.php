<?php
namespace PhpPush\XMPP\Interfaces;


interface LaravelXMPPServerListener {
    /**
     * @return mixed
     */
    function onConnect(): mixed;

    /**
     * @return mixed
     */
    function onDisconnect(): mixed;

    /**
     * @param array $error
     * @return mixed
     */
    function onError(array $error): mixed;

    function onWrite(string $data): void;
    function onRead(string $data): void;
}
