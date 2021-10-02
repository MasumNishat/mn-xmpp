<?php

namespace PhpPush\XMPP\Extensions;

use DOMDocument;
use PhpPush\XMPP\Core\ExtensionListener;
use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Interfaces\XMPPExtension;

final class XEP0060 implements XMPPExtension{
    private LaravelXMPPConnectionManager $connection;
    private static ?XEP0060 $instance = null;
    private array $lastError = [];

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): XEP0060
    {
        if (XEP0060::$instance === null) {
            XEP0060::$instance = new XEP0060();
        }
        return XEP0060::$instance;
    }

    public function connect(LaravelXMPPConnectionManager $connection): XEP0060 | bool
    {
        $this->connection = $connection;
        if (!self::isSupported(true)) {
            $this->connection->getServerListener()->onError(['warning', 'XEP0060 not supported by server']);
            return false;
        }
        return $this::$instance;
    }

    /**
     * @param bool $reload
     * @return bool
     */
    private function isSupported(bool $reload = false): bool
    {
        $this->isSupported = false;
        //check if XEP0030 is loaded
        $XEP0030 = ExtensionListener::getInstance()->getExtension('XEP0030');
        if ($XEP0030) {
            $this->isSupported = $XEP0030->checkNS($this->ns());
            if (!$this->isSupported && $reload) {
                $from = "from='" . $this->connection->getJid() . "'";
                $to = "to='" . $this->connection->getHost() . "'";
                $xml = "<iq type='get' $from $to id='" . md5(uniqid(rand(), true)) . "'><query xmlns='http://jabber.org/protocol/disco#info'/></iq>";
                $this->isSupported = $XEP0030->checkNS($this->ns(), true, $xml);
            }
        }
        return $this->isSupported;
    }

    public function extension(): string
    {
        return 'XEP-0060';
    }

    public function name(): string
    {
        return 'Publish-Subscribe';
    }

    public function ns(): array
    {
        return [
            'http://jabber.org/protocol/pubsub',
        ];
    }

    public function onBeforeWrite(string $xml)
    {
        // TODO: Implement onBeforeWrite() method.
    }

    public function onAfterWrite(string $xml)
    {
        // TODO: Implement onAfterWrite() method.
    }

    public function onRead(string $response){}
    public function checkError(string $responseXML): bool
    {
        $this->lastError = [];
        if (str_contains($responseXML, 'http://jabber.org/protocol/pubsub#errors')) {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($responseXML);
            $child = $doc->getElementsByTagName('error')[0]->childNodes;
            foreach ($child as $item){
                foreach ($item->attributes as $attribute){
                    if ($attribute->value == 'http://jabber.org/protocol/pubsub#errors') {
                        $this->lastError['pubsub'] = $item->nodeName;
                        return true;
                    }
                }
            }

        }
        return false;
    }

    public function getLastError(): array
    {
        return $this->lastError;
    }
}
