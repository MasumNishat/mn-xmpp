<?php
namespace MN\XMPP\Interfaces;

interface XMPPClientOptions
{
    /**
     * @return string
     */
    function setUsername(): string;

    /**
     * @return string
     */
    function setPassword(): string;

    /**
     * @return string
     */
    function setAuthType(): string;

    /**
     * @return string
     */
    function setResourcePrefix(): string;

    /**
     * @return string
     */
    function setResourceSuffix(): string;

    /**
     * @return string
     */
    function setResource(): string;

    /**
     * @param int $code
     * @param string $message
     * @return mixed
     */
    function onError(int $code, string $message): mixed;

    function onConnect();

    function onDisconnect();
}
