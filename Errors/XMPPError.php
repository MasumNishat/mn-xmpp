<?php
namespace PhpPush\XMPP\Errors;

use PhpPush\XMPP\Laravel\DataManager;

class XMPPError
{
    private static string $response = '';

    /**
     * @param string $responseXML
     * @return bool
     */
    public static function check(string $responseXML): bool
    {
        self::$response = $responseXML;
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
        //check extensions
        $extensions = DataManager::getInstance()->getData(DataManager::LOADED_EXTENSIONS);
        foreach ($extensions as $extension){
            if ($extension::getInstance()->checkError(self::$response)) {
                $streamError = array_merge($streamError, $extension::getInstance()->getLastError(self::$response));
            }
        }
        return $streamError;
    }
}
