<?php
namespace PhpPush\XMPP\Errors;

class XMPPError
{

    /**
     * @param string $responseXML
     * @return bool
     */
    public static function check(string $responseXML): bool
    {
        if (!empty(StreamError::getInstance()->check($responseXML)->getLastError()) ||
            !empty(SASLError::getInstance()->check($responseXML)->getLastError()) ||
            !empty(StanzaError::getInstance()->check($responseXML)->getLastError())
        ) return true;
        return false;
    }

    /**
     * @return array
     */
    public static function getLastError(): array
    {
        $streamError = StreamError::getInstance()->getLastError();
        if (empty($streamError)) {
            $streamError = SASLError::getInstance()->getLastError();
            if (empty($streamError)) {
                $streamError = StanzaError::getInstance()->getLastError();
            }
        }
        return $streamError;
    }
}
