<?php
namespace MN\XMPP\Interfaces;

interface XMPPServerOptions
{
    /**
     * @return string
     */
    function setProtocol(): string;

    /**
     * @return string
     */
    function setHost(): string;

    /**
     * @return int
     */
    function setPort(): int;

    /**
     * @return array
     */
    function socketHeader(): array;

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
}
