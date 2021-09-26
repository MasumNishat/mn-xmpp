<?php
namespace PhpPush\XMPP\Interfaces;

use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Core\XMPPConnectionManager;

interface XMPPExtension
{
    /**
     * @return string
     */
    public function extension(): string;

    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return array
     */
    public function ns(): array;

    public function onBeforeWrite(string $xml);
    public function onAfterWrite(string $xml);
    public function onRead(string $response);
    public function connect(LaravelXMPPConnectionManager $connection): ?XMPPExtension;
    public static function getInstance(): XMPPExtension;
}
