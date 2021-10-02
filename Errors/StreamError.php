<?php
declare(strict_types=1);
namespace PhpPush\XMPP\Errors;

use Exception;

final class StreamError
{
    //todo: rfc6120, section:4.9.4, not implemented
    private array $lastError = [];
    private static ?StreamError $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): StreamError
    {
        if (StreamError::$instance === null) {
            StreamError::$instance = new StreamError();
        }
        return StreamError::$instance;
    }

    /**
     * @param string $responseXML
     * @return $this
     * strictly followed rfc6120: section 4.9.2, even for whitespace and quotation(')
     *
     */
    public function check(string $responseXML): StreamError
    {
        $this->lastError = [];
        preg_match('/<stream:error>(\s+|)<(?P<error>[\W\w]+)(\s+)xmlns=\'urn:ietf:params:xml:ns:xmpp-streams\'\/>(.*)<\/stream:error>/s', $responseXML, $m);
        if (isset($m['error'])) {
            $this->lastError = [
                'type' => 'stream-level error',
                'error' => trim($m['error']),
                'des' => html_entity_decode(strip_tags($responseXML), ENT_QUOTES | ENT_HTML5),
                'signal' => 'end' //as '</stream:stream>' is present as rfc6920:section 4.9.1.1
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
