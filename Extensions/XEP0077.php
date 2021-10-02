<?php

namespace PhpPush\XMPP\Extensions;

use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Interfaces\XMPPExtension;

final class XEP0077 implements XMPPExtension {
    private static ?XEP0077 $instance = null;
    private LaravelXMPPConnectionManager $connection;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): XEP0077
    {
        if (XEP0077::$instance === null) {
            XEP0077::$instance = new XEP0077();
        }
        return XEP0077::$instance;
    }

    public function connect(LaravelXMPPConnectionManager $connection): XEP0077 | bool
    {
        $this->connection = $connection;
        if (!self::isSupported()) {
            $this->connection->getServerListener()->onError(['warning', 'XEP0077 not supported by server']);
            return false;
        }
        return $this::$instance;
    }

    /**
     * @return bool
     */
    private function isSupported(): bool
    {
        //it is a permanent namespaces
        return $this->connection->regSupport;
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return 'XEP-0077';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'In-Band Registration';
    }

    /**
     * @return string[]
     */
    public function ns(): array
    {
        return [
            'jabber:iq:register',
        ];
    }

    public function onBeforeWrite(string $xml)
    {
    }

    public function onAfterWrite(string $xml)
    {
    }

    public function onRead(string $response)
    {
    }
    public function checkError(string $responseXML): bool
    {
        return false;
    }

    public function getLastError(): array
    {
        return [];
    }
}
