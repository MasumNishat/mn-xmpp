<?php
namespace PhpPush\XMPP\Core;

use PhpPush\XMPP\Interfaces\XMPPClientOptions;

class XMPPUser
{
    private XMPPClientOptions $XMPPClientOptions;

    public function __construct(XMPPClientOptions $XMPPClientOptions)
    {
        $this->XMPPClientOptions = $XMPPClientOptions;
    }

    public function getXMPPClientOptions(): XMPPClientOptions
    {
        return $this->XMPPClientOptions;
    }

    public function getUsername(): string
    {
        return $this->XMPPClientOptions->setUsername();
    }

    public function getPassword(): string
    {
        return $this->XMPPClientOptions->setPassword();
    }

    public function getResource(): string
    {
        $prefix = $this->XMPPClientOptions->setResourcePrefix();
        $suffix = $this->XMPPClientOptions->setResourceSuffix();
        $resource = $this->XMPPClientOptions->setResource();
        return ($prefix ?: 'xmpp_php_') . ($resource ?: sha1(microtime() . $this->getUsername() . rand())) . ($suffix ?: '_res');
    }

    public function getAuthMethod(): string
    {
        $method = $this->XMPPClientOptions->setAuthType() ?: 'PLAIN';
        return strtoupper($method);
    }

    public function encodedCredentials(): string
    {
        switch ($this->getAuthMethod()) {
            case 'PLAIN':
                $credentials = "\x00{$this->getUsername()}\x00{$this->getPassword()}";
                return htmlspecialchars(base64_encode($credentials), ENT_XML1, 'utf-8');
            case 'DIGEST-MD5':
                $credentials = "\x00{$this->getUsername()}\x00{$this->getPassword()}";
                return htmlspecialchars(sha1($credentials), ENT_XML1, 'utf-8');
        }
    }
}
