<?php
namespace PhpPush\XMPP\Interfaces;

interface XMPPServerOptions extends LaravelXMPPServerListener
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
}
