<?php
declare(strict_types=1);

namespace MN\XMPP\Errors;

final class SASLError
{
    private array $lastError = [];
    private static ?SASLError $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): SASLError
    {
        if (SASLError::$instance === null) {
            SASLError::$instance = new SASLError();
        }
        return SASLError::$instance;
    }

    /**
     * @param string $responseXML
     * @return $this
     * strictly followed rfc6120: section 6.5, even for whitespace and quotation(')
     */
    public function check(string $responseXML): SASLError
    {
        $this->lastError = [];
        preg_match('/<failure(\s+)xmlns=\'urn:ietf:params:xml:ns:xmpp-sasl\'>(\s+|)<(?P<error>[\W\w]+)\/>/', $responseXML, $m);
        if (isset($m['error'])) {
            $this->lastError = [
                'type' => 'SASL error',
                'error' => $m['error'],
                'des' => html_entity_decode(strip_tags($responseXML), ENT_QUOTES | ENT_HTML5),
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
