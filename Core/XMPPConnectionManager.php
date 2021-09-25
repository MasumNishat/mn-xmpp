<?php
namespace MN\XMPP\Core;

class XMPPConnectionManager
{
    public XMPPServer $XMPPServer;
    public XMPPUser $XMPPUser;
    public string $host;
    public string $user;
    public string $jid;
    private string $resource;
    private static array $extensions = [];

    public function __construct(XMPPServer $XMPPServer, XMPPUser $XMPPUser)
    {
        $this->XMPPServer = $XMPPServer;
        $this->XMPPServer->managerConnected = true;
        $this->XMPPUser = $XMPPUser;
        $this->host = $this->XMPPServer->getConnectedHost();
        $this->user = $this->XMPPUser->getUsername();
        $this->resource = $this->XMPPUser->getResource();
        $this->jid = $this->user . "@" . $this->host . "/" . $this->resource;
        if ($this->auth()) {
            $this->resourceBinding();
            return $this;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function auth(): bool
    {
        if (in_array($this->XMPPUser->getAuthMethod(), $this->XMPPServer->getAuthMethods())) {
            $parts = explode('-',$this->XMPPUser->getAuthMethod());
            $method = ucfirst(strtolower($parts[0]));
            unset($parts[0]);
            $algo = strtolower(implode("-", $parts));
            return $method::attach($this)->auth($algo);
        } else {
            $this->XMPPUser->getXMPPClientOptions()->onError(2001, 'Unsupported authentication method configured');
        }
        return false;
    }

    private function resourceBinding()
    {
        $xml1 = "<?xml version='1.0' encoding='UTF-8'?><stream:stream
        to='" . $this->host . "' xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' version='1.0'>";
        $this->XMPPServer->write($xml1);
        $bindExist = XMPP_XML::parse($this->XMPPServer->getResponse())->exist('bind');
        $sessionExist = XMPP_XML::parse($this->XMPPServer->getResponse())->exist('session');
        if ($bindExist) {
            $xml2 = "<iq type='set' id='" . uniqid(rand(), true) .
                "'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>" .
                $this->resource . "</resource></bind></iq>";
            $this->XMPPServer->write($xml2);

            if ($sessionExist) {
                /**
                 * rfc3921, section 3
                 */
                $xml3 = "<iq to='" . $this->host . "' type='set' id='" . uniqid(rand(), true) . "'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>";
                $this->XMPPServer->write($xml3);
            }
        }

    }

    public function loadExtension(array $extensions)
    {
        foreach ($extensions as $extension) {
            if (is_a($extension[0], XMPPExtension::class, true)) {
                foreach ($extension[1] as $key=>$value){
                    $extension[0]::$key($value);
                }
                $extension[0]::init($this);
                self::$extensions[] = $extension[0];
            }
        }
    }
    public static function isExtensionLoaded($class): bool
    {
        return (in_array($class, self::$extensions) && XEP0030::checkNS($class::ns()));
    }
    public function rawSend($data)
    {
        $this->XMPPServer->write($data);
    }
}
