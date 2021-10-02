<?php

namespace PhpPush\XMPP\Extensions;

use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Interfaces\XMPPExtension;

final class XEP0004 implements XMPPExtension {
    private static ?XEP0004 $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): XEP0004
    {
        if (XEP0004::$instance === null) {
            XEP0004::$instance = new XEP0004();
        }
        return XEP0004::$instance;
    }

    public function connect(LaravelXMPPConnectionManager $connection): XEP0004 | bool
    {
        $connection1 = $connection;
        if (!self::isSupported()) {
            $connection1->getServerListener()->onError(['warning', 'XEP0004 not supported by server']);
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
        return true;
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return 'XEP-0004';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Data Forms';
    }

    /**
     * @return string[]
     */
    public function ns(): array
    {
        return [
            'jabber:x:data',
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
