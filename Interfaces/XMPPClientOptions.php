<?php
namespace PhpPush\XMPP\Interfaces;

interface XMPPClientOptions extends LaravelXMPPClientListener
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
}
