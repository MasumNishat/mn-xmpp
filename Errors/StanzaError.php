<?php
declare(strict_types=1);

namespace PhpPush\XMPP\Errors;

use Exception;

final class StanzaError
{
    private array $lastError = [];
    private static ?StanzaError $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): StanzaError
    {
        if (StanzaError::$instance === null) {
            StanzaError::$instance = new StanzaError();
        }
        return StanzaError::$instance;
    }

    /**
     * @param string $responseXML
     * @return $this
     * strictly followed rfc6120: section 8.3.2, even for whitespace and quotation(')
     */
    public function check(string $responseXML): StanzaError
    {
        $this->lastError = [];
        preg_match('/<(?P<stanza>(message|presence|iq))(.*)type=\'error\'(.*)>(.*)'.
            '<error(.*)type=\'(?P<type>(auth|cancel|continue|modify|wait))\'(.*)'.
            '<(?P<error>[\W\w]+)(\s+)xmlns=\'urn:ietf:params:xml:ns:xmpp-stanzas\'\/>(.*)<\/(message|presence|iq)>/s', $responseXML, $m);
        if (isset($m['error'])) {
            $this->lastError = [
                'type' => "Stanza($m[stanza]) error",
                'error' => "$m[type]: $m[error]",
                'des' => trim(html_entity_decode(strip_tags($responseXML), ENT_QUOTES | ENT_HTML5)),
                'signal' => ''
            ];
        }
        return  $this;
    }
    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
    }

    /**
     * @return array
     */
    public function getLastError(): array
    {
        return $this->lastError;
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
