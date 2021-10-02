<?php

namespace PhpPush\XMPP\UI;

use DOMDocument;
use PhpPush\XMPP\Core\XMPPSend;
use PhpPush\XMPP\Errors\XMPPError;
use PhpPush\XMPP\Helpers\Functions;
use PhpPush\XMPP\Laravel\DataManager;

class XEP0030 {
    private static ?XEP0030 $instance = null;
    private mixed $jid;
    private mixed $host;
    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): XEP0030
    {
        if (XEP0030::$instance === null) {
            XEP0030::$instance = new XEP0030();
        }
        return XEP0030::$instance;
    }

    private function __construct()
    {
        $this->jid = DataManager::getInstance()->getData(DataManager::USER_JID);
        $this->host = DataManager::getInstance()->getData(DataManager::HOST);
    }

    /**
     * XEP0030: 3.1, 3.2, 4.1, 4.2, 4.3, 4.4
     * @param string $to
     * @param string $for = (info | items)
     * @param string $from
     * @param string $node
     * @param bool $getRsp
     * @return false|array
     */
    public function query(string $to = '', string $for = 'info', string $from = '', string $node = '', bool $getRsp = false): false|array
    {
        $for = strtolower(trim($for));
        if ($for == 'items' || $for == 'info') {
            $ns = "http://jabber.org/protocol/disco#$for";
        } else {
            return false;
        }
        $id = Functions::createID();
        $from = $from?: $this->jid;
        $node = $node == '' ? '' : "node='$node'";
        $to = $to?: $this->host;
        $xml = "<iq type='get'
    from='$from'
    to='$to'
    id='$id'>
  <query xmlns='$ns' $node/>
</iq>";
        XMPPSend::getInstance()->send($xml);
        $data = DataManager::getInstance()->getResponseOf($xml);
        //The target entity then MUST either return an IQ result, or return an error
        if (XMPPError::check($data)) {
            return false;
        } else {
            $options = [];
            if ($getRsp) {
                $options['xmpp_response'] = DataManager::getInstance()->getResponseOf($xml);
            }
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($data);
            $iq = $doc->getElementsByTagName('iq')[0]; // this will definitely match
            foreach ($iq->attributes as $attribute){
                $options[$attribute->name] = $attribute->value;
            }
            //The result MUST contain a <query/> element qualified by the
            // 'http://jabber.org/protocol/disco#info' namespace, which in turn contains one
            // or more <identity/> elements and one or more <feature/> elements
            $queries = $iq->getElementsByTagName('query');
            foreach($queries as $query){
                foreach ($query->attributes as $attribute){
                    if ($attribute->name == 'xmlns' && $attribute->value == $ns) {
                        //this the required query
                        //get identity
                        $identities = $query->getElementsByTagName('identity');
                        $index = 0;
                        foreach ($identities as $identity){
                            foreach ($identity->attributes as $attribute){
                                $options['identity'][$index][$attribute->name] = $attribute->value;
                            }
                            $index++;
                        }
                        $features = $query->getElementsByTagName('feature');
                        $index = 0;
                        foreach ($features as $feature){
                            foreach ($feature->attributes as $attribute){
                                $options['feature'][$index][$attribute->name] = $attribute->value;
                            }
                            $index++;
                        }
                    }
                }
            }
            return $options;
        }
    }
}
