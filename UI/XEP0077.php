<?php

namespace PhpPush\XMPP\UI;

use DOMDocument;
use PhpPush\XMPP\Core\XMPPSend;
use PhpPush\XMPP\Errors\XMPPError;
use PhpPush\XMPP\Helpers\Functions;
use PhpPush\XMPP\Laravel\DataManager;

class XEP0077 {
    private static ?XEP0077 $instance = null;
    private mixed $jid;
    private mixed $host;

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
    private function __construct()
    {
        $this->jid = DataManager::getInstance()->getData(DataManager::USER_JID);
        $this->host = DataManager::getInstance()->getData(DataManager::HOST);
    }

    /**
     * @return string
     */
    public function newRegXML (): string
    {
        $id = Functions::createID();
        return "<iq type='get' id='$id' to='$this->host'>
  <query xmlns='jabber:iq:register'/>
</iq>";
    }
    public function parseRegFields(string $data): bool|array
    {
        if (XMPPError::check($data)) {
            return false;
        } else {
            $return = [];
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($data);
            $query = $doc->getElementsByTagName('query');
            foreach ($query as $item){
                $index = 0;
                foreach ($item->childNodes as $element) {
                    $return[$element->nodeName] = $element->nodeValue;
                    $index++;
                }
            }
            return $return;
        }
    }
}
