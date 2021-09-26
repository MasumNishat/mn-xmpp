<?php
namespace PhpPush\XMPP\Auth;

//rfc4616 implemented todo: SASLPrep, StringPrep to be implemented
use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Helpers\XMPP_XML;
use PhpPush\XMPP\Interfaces\XMPPAuth;

class Plain implements XMPPAuth{
    private LaravelXMPPConnectionManager $connection;
    private static ?Plain $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function attach(LaravelXMPPConnectionManager $connection): Plain
    {
        if (Plain::$instance === null) {
            Plain::$instance = new Plain($connection);
        }
        return Plain::$instance;
    }
    private function __construct(LaravelXMPPConnectionManager $connection) {
        $this->connection = $connection;
    }

    /**
     * @param string $method
     * @return bool
     */
    public function auth(string $method=''): bool
    {
        $xml = "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>" . $this->encodedCredentials() . "</auth>";
        $this->connection->write($xml);
        if (XMPP_XML::parse($this->connection->getResponse())->exist('success')) {
            return true;
        }
        return false;
    }
    private function encodedCredentials(): string
    {
        $credentials = $this->connection->getJid().chr(0).$this->connection->getUser().chr(0).$this->connection->getPassword();
        return base64_encode($credentials);
    }
}
