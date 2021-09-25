<?php
namespace MN\XMPP\Auth;

//rfc4616 implemented todo: SASLPrep, StringPrep to be implemented
class Plain implements XMPPAuth{
    private static XMPPConnectionManager $XMPPConnectionManager;
    private static ?Plain $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function attach(XMPPConnectionManager $XMPPConnectionManager): Plain
    {
        if (Plain::$instance === null) {
            Plain::$instance = new Plain($XMPPConnectionManager);
        }
        return Plain::$instance;
    }
    private function __construct(XMPPConnectionManager $XMPPConnectionManager) {
        self::$XMPPConnectionManager = $XMPPConnectionManager;
    }

    /**
     * @param string $method
     * @return bool
     */
    public function auth(string $method=''): bool
    {
        $xml = "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" . $this->encodedCredentials() . "</auth>";
        self::$XMPPConnectionManager->XMPPServer->write($xml);
        if (XMPP_XML::parse(self::$XMPPConnectionManager->XMPPServer->getResponse())->exist('success')) {
            return true;
        }
        return false;
    }
    private function encodedCredentials(): string
    {
        $credentials = self::$XMPPConnectionManager->jid."\x00".self::$XMPPConnectionManager->user."\x00".self::$XMPPConnectionManager->XMPPUser->getPassword();
        return base64_encode($credentials);
    }
}
