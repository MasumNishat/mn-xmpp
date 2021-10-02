<?php
/*
 *
 *  // +-------------------------------------------------------------------------+
 *  // | Copyright (c) 2021 Al Masum Nishat                                      |
 *  // | All rights reserved.                                                    |
 *  // |                                                                         |
 *  // | Redistribution and use in source and binary forms, with or without      |
 *  // | modification, are permitted provided that the following conditions      |
 *  // | are met:                                                                |
 *  // |                                                                         |
 *  // | o Redistributions of source code must retain the above copyright        |
 *  // |   notice, this list of conditions and the following disclaimer.         |
 *  // | o Redistributions in binary form must reproduce the above copyright     |
 *  // |   notice, this list of conditions and the following disclaimer in the   |
 *  // |   documentation and/or other materials provided with the distribution.  |
 *  // | o The names of the authors may not be used to endorse or promote        |
 *  // |   products derived from this software without specific prior written    |
 *  // |   permission.                                                           |
 *  // |                                                                         |
 *  // | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS     |
 *  // | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT       |
 *  // | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR   |
 *  // | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT    |
 *  // | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,   |
 *  // | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT        |
 *  // | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,   |
 *  // | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY   |
 *  // | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT     |
 *  // | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE   |
 *  // | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.    |
 *  // |                                                                         |
 *  // +-------------------------------------------------------------------------+
 *  // | Author: Al Masum Nishat <masum.nishat21@gmail.com>                      |
 *  // +-------------------------------------------------------------------------+
 *
 */

namespace PhpPush\XMPP\UI;

use DOMDocument;
use PhpPush\XMPP\Core\XMPPSend;
use PhpPush\XMPP\Errors\XMPPError;
use PhpPush\XMPP\Helpers\Functions;
use PhpPush\XMPP\Helpers\XMPP_XML;
use PhpPush\XMPP\Laravel\DataManager;

final class XEP0060
{
    private static ?XEP0060 $instance = null;
    private mixed $jid;
    private mixed $host;
    private mixed $user;

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

    private function __construct()
    {
        $this->jid = DataManager::getInstance()->getData(DataManager::USER_JID);
        $this->user = DataManager::getInstance()->getData(DataManager::USER);
        $this->host = DataManager::getInstance()->getData(DataManager::HOST);
    }

    /**
     * @param string $xml
     * @return bool
     */
    private function processResult(string $xml): bool
    {
        XMPPSend::getInstance()->send($xml);
        $data = DataManager::getInstance()->getResponseOf($xml);
        if (XMPPError::check($data)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * * XEP0060: 5.2 [$checkItem = true], 5.3 [$checkItem = false, $node != ''], 5.4 [$checkMeta = true], 5.5 [$checkItem = true, $node != '']
     * @param bool $checkItem
     * @param string $node
     * @param bool $checkMeta
     * @param string $host
     * @return bool|array
     */
    public function getNode(bool $checkItem = true, string $node = '',bool $checkMeta = false, string $host = ''): bool|array
    {
        if ($checkItem) {
            $item = 'items';
            $checkMeta = false;
        } elseif ($node == '') {
            return false;
        } else {
            $item = 'info';
        }
        $host = $host?: $this->host;
        $return['query'] = XEP0030::getInstance()->query("pubsub.$host", $item, $this->jid, $node, $checkMeta);
        if (isset($return['query']['xmpp_response'])) {
            $return['meta'] = XEP0004::getInstance()->parse($return['query']['xmpp_response']);
            unset ($return['query']['xmpp_response']);
        }
        return $return;
    }


    /**
     * XEP0060: 6.1.1
     * @param string $node
     * @param string $host
     * @return false|array
     */
    public function subscribe(string $node, string $host = ''): false|array
    {
        $host = $host?:$this->host;
        $id = Functions::createID();
        $xml = "<iq type='set'
    from='$this->jid'
    to='pubsub.$host'
    id='$id'>
  <pubsub xmlns='http://jabber.org/protocol/pubsub'>
    <subscribe
        node='$node'
        jid='$this->user@$host'/>
  </pubsub>
</iq>";
        XMPPSend::getInstance()->send($xml);
        $data = DataManager::getInstance()->getResponseOf($xml);
        if (XMPPError::check($data)) {
            return false;
        } else {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($data);
            $options = [];
            $iq = $doc->getElementsByTagName('iq')[0]; // this will definitely match
            foreach ($iq->attributes as $attribute){
                $options['result'][$attribute->name] = $attribute->value;
            }
            $child = $doc->getElementsByTagName('subscription');

            foreach ($child[0]->attributes as $attribute){
                $options[$attribute->name] = $attribute->value;
            }
            $children = $doc->getElementsByTagName('subscribe-options');
            foreach ($children as $child){
                $options['subscribe-options'][] = $child->nodeName;
            }

            return $options;
        }
    }

    //todo: XEP0060: 6.1.6 (Multiple Subscriptions)
    //todo: XEP0060: 6.1.7 Receiving the Last Published Item

    /**
     * XEP0060: 6.1
     * @param string $node
     * @param string $host
     * @return false|array
     */
    public function unsubscribe(string $node, string $host = ''): false|array
    {
        $host = $host?:$this->host;
        $id = Functions::createID();
        $xml = "<iq type='set'
    from='$this->jid'
    to='pubsub.$host'
    id='$id'>
  <pubsub xmlns='http://jabber.org/protocol/pubsub'>
     <unsubscribe
         node='$node'
         jid='$this->user@$host'/>
  </pubsub>
</iq>";
        XMPPSend::getInstance()->send($xml);
        $data = DataManager::getInstance()->getResponseOf($xml);
        if (XMPPError::check($data)) {
            return false;
        } else {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($data);
            $options = [];
            $iq = $doc->getElementsByTagName('iq')[0]; // this will definitely match
            foreach ($iq->attributes as $attribute){
                $options['result'][$attribute->name] = $attribute->value;
            }

            $child = $doc->getElementsByTagName('subscription');
            if (isset($child[0])) {
                foreach ($child[0]->attributes as $attribute) {
                    $options[$attribute->name] = $attribute->value;
                }
                $children = $doc->getElementsByTagName('subscribe-options');
                foreach ($children as $child) {
                    $options['subscribe-options'][] = $child->nodeName;
                }
            }
            return $options;
        }
    }

    /**
     * @todo section 6.3 does not support by my server.
     * XEP0060: 6.3.1
     * @param string $host
     * @return bool
     */
    public function isSubscriptionSupported(string $host = ''): bool
    {
        $host = $host?: $this->host;
        $data = XEP0030::getInstance()->query("pubsub.$host");
        print_r($data);
        foreach ($data['feature'] as $value){
            if ($value['var'] == 'http://jabber.org/protocol/pubsub#subscription-options') {
                return true;
            }
        }
        return false;
    }

    public function configureSubscription($node, $host = '', $subscriber = '') {
        $host = $host?: $this->host;
        $subscriber = $subscriber?: $this->user.'@'.$host;
        $id = Functions::createID();
        $xml = "<iq type='get'
    from='$this->jid'
    to='pubsub.$host'
    id='$id'>
  <pubsub xmlns='http://jabber.org/protocol/pubsub'>
    <options node='$node' jid='$subscriber'/>
  </pubsub>
</iq>";
        XMPPSend::getInstance()->send($xml);
        $data = DataManager::getInstance()->getResponseOf($xml);
        if (XMPPError::check($data)) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * XEP0060: 8.1.1
     * @return string|false
     */
    public function createInstantNode(): string|false
    {
        $id = Functions::createID();
        $xml = "<iq type='set'
    from='$this->jid'
    to='pubsub.$this->host'
    id='$id'>
  <pubsub xmlns='http://jabber.org/protocol/pubsub'>
    <create/>
  </pubsub>
</iq>";
        XMPPSend::getInstance()->send($xml);
        $data = DataManager::getInstance()->getResponseOf($xml);
        if (XMPPError::check($data)) {
            return false;
        } else {
            preg_match('/node=\'(?P<name>[\W\w]+)\'/', $data, $m);
            return $m['name'];
        }
    }

    /**
     * XEP0060: 8.1.2
     * @param string $name
     * @param string $accessModel
     * @return bool
     */
    public function createNode(string $name, string $accessModel = ''): bool
    {
        $id = Functions::createID();
        if ($accessModel == '') {
            $model = "";
        } else {
            $model = "<configure>
        <x xmlns='jabber:x:data' type='submit'>
          <field var='FORM_TYPE' type='hidden'>
            <value>http://jabber.org/protocol/pubsub#node_config</value>
          </field>
          <field var='pubsub#access_model'><value>$accessModel</value></field>
        </x>
      </configure>";
        }
        $xml = "<iq type='set'
    from='$this->jid'
    to='pubsub.$this->host'
    id='$id'>
  <pubsub xmlns='http://jabber.org/protocol/pubsub'>
    <create node='$name'/>
    $model
  </pubsub>
</iq>";
        return $this->processResult($xml);
    }


    /**
     * XEP0060: 8.1.3 [$create = true], 8.2.4 [$create = false]
     * @param string $name
     * @param array $options
     * @param bool $create
     * @return bool
     */
    public function configuredNode(string $name, array $options, bool $create = false): bool
    {
        if (empty($options)) {
            return false;
        }
        $id = Functions::createID();
        $conf = '';
        foreach ($options as $key => $value) {
            $conf .= "<field var='pubsub#$key'><value>" . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "</value></field>";
        }
        if ($create) {
            $createTag = "<create node='$name'/>";
            $confTag = "<configure>";
            $xmlns = "http://jabber.org/protocol/pubsub";
        } else {
            $createTag = "";
            $confTag = "<configure node='$name'>";
            $xmlns = "http://jabber.org/protocol/pubsub#owner";
        }
        $xml = "<iq type='set'
    from='$this->jid'
    to='pubsub.$this->host'
    id='$id'>
  <pubsub xmlns='$xmlns'>
    $createTag
    $confTag
        <x xmlns='jabber:x:data' type='submit'>
          <field var='FORM_TYPE' type='hidden'>
            <value>http://jabber.org/protocol/pubsub#node_config</value>
          </field>
          $conf
        </x>
      </configure>
  </pubsub>
</iq>";
        return $this->processResult($xml);
    }

    /**
     * XEP0060: 8.2.1 [$name != ''], 8.3.1 [$name = '']
     * @param string $name
     * @return false|array
     */
    public function requestNodeConfiguration(string $name = ''): false|array
    {
        $req = trim($name) == ''? '<default/>' : "<configure node='$name'/>";
        $id = Functions::createID();
        $xml = "<iq type='get'
    from='$this->jid'
    to='pubsub.$this->host'
    id='$id'>
  <pubsub xmlns='http://jabber.org/protocol/pubsub#owner'>
    $req
  </pubsub>
</iq>";
        XMPPSend::getInstance()->send($xml);
        $data = DataManager::getInstance()->getResponseOf($xml);
        if (XMPPError::check($data)) {
            return false;
        } else {
            return XEP0004::getInstance()->parse($data);
        }
    }

    /**
     * XEP0060: 8.4.1
     * @param String $name
     * @param string $redirectTo
     * @return bool
     */
    public function deleteNode(string $name, string $redirectTo = ''): bool
    {
        $id = Functions::createID();
        if ($redirectTo == '') {
            $delete = "<delete node='$name'/>";
        } else {
            if (filter_var($redirectTo, FILTER_VALIDATE_URL)) {
                $delete = "<delete node='$name'>
      <redirect uri='$redirectTo'/>
    </delete>";
            } else {
                $delete = "<delete node='$name'>
      <redirect uri='xmpp:$name?;node=$redirectTo'/>
    </delete>";
            }
        }
        $xml = "<iq type='set'
    from='$this->jid'
    to='pubsub.$this->host'
    id='$id'>
  <pubsub xmlns='http://jabber.org/protocol/pubsub#owner'>
    $delete
  </pubsub>
</iq>";
        return $this->processResult($xml);
    }

    /**
     * XEP0060: 8.5.1
     * @param String $name
     * @return bool
     */
    public function purge(string $name): bool
    {
        $id = Functions::createID();
        $xml = "<iq type='set'
    from='$this->jid'
    to='pubsub.$this->host'
    id='$id'>
  <pubsub xmlns='http://jabber.org/protocol/pubsub#owner'>
    <purge node='$name'/>
  </pubsub>
</iq>";
        return $this->processResult($xml);
    }
}
