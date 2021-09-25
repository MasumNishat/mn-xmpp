<?php
namespace MN\XMPP\Extensions;

class XEP0199 implements XMPPExtension
{
    private static XMPPConnectionManager $XMPPConnectionManager;
    private static bool $isSupported;
    public static bool $c2c_ping = false;
    public static bool $c2s_ping = false;
    public static array $serverPingID = [];
    public static int $c2s_ping_interval = 60;
    public static int $c2c_ping_interval = 60;
    public static array $clientPingID = [];
    public static int $c2s_ping_timeout = 60;
    public static int $c2c_ping_timeout = 60;

    public static function init(XMPPConnectionManager $XMPPConnectionManager): void
    {
        self::$XMPPConnectionManager = $XMPPConnectionManager;
        if (!self::isSupported(true)) {
            self::$XMPPConnectionManager->XMPPServer->getXMPPServerOptions()->onError([10002, 'XEP0199 not supported by server']);
        }
    }

    /**
     * @return string
     */
    public static function extension(): string
    {
        return 'XEP-0199';
    }

    /**
     * @return string
     */
    public static function name(): string
    {
        return 'Service Discovery';
    }

    /**
     * @return string[]
     */
    public static function ns(): array
    {
        return [
            'urn:xmpp:ping',
        ];
    }

    /**
     * @param XMPPConnectionManager $XMPPConnectionManager
     */
    public static function XMPPConnectionManager(XMPPConnectionManager $XMPPConnectionManager): void
    {
        self::$XMPPConnectionManager = $XMPPConnectionManager;
    }

    /**
     * @param bool $reload
     * @return bool
     */
    static function isSupported(bool $reload = false): bool
    {
        self::$isSupported = false;
        //check if XEP0030 is loaded
        if (self::$XMPPConnectionManager->isExtensionLoaded('XEP0030')) {
            self::$isSupported = XEP0030::checkNS(self::ns());
            if (!self::$isSupported && $reload) {
                $from = self::$XMPPConnectionManager->jid ? "from='" . self::$XMPPConnectionManager->jid . "'" : '';
                $to = "to='" . self::$XMPPConnectionManager->host . "'";
                $xml = "<iq type='get' $from $to id='" . uniqid(rand(), true) . "'><query xmlns='http://jabber.org/protocol/disco#info'/></iq>";
                self::$isSupported = XEP0030::checkNS(self::ns(), true, $xml);
            }
        }
        return self::$isSupported;
    }

    public static function c2c_ping(bool $value)
    {
        self::$c2c_ping = $value;
    }

    public static function c2s_ping_interval(int $value)
    {
        self::$c2s_ping_interval = $value;
    }

    public static function c2c_ping_interval(int $value)
    {
        self::$c2c_ping_interval = $value;
    }

    public static function c2s_ping_timeout(int $value)
    {
        self::$c2s_ping_timeout = $value;
    }

    public static function c2c_ping_timeout(int $value)
    {
        self::$c2c_ping_timeout = $value;
    }

    public static function c2s_ping(bool $value)
    {
        self::$c2s_ping = $value;
    }

    public static function pingClient(string $to)
    {
        self::$clientPingID[$to]['id'] = uniqid(rand(), true);
        self::$clientPingID[$to]['time'] = time();
        $xml = "<iq from='" . self::$XMPPConnectionManager->jid . "' to='$to' id='" . self::$clientPingID[$to]['id'] . "' type='get'><ping xmlns='urn:xmpp:ping'/></iq>";
        self::$XMPPConnectionManager->XMPPServer->write($xml);
    }

    public static function pingServer()
    {
        if(!empty(self::$serverPingID)) {

        }
        self::$serverPingID['id'] = uniqid(rand(), true);
        self::$serverPingID['time'] = time();
        $xml = "<iq from='" . self::$XMPPConnectionManager->jid . "' to='" . self::$XMPPConnectionManager->host . "' id='" . self::$serverPingID . "' type='get'><ping xmlns='urn:xmpp:ping'/></iq>";
        self::$XMPPConnectionManager->XMPPServer->write($xml);
//        while ( && self::$c2s_ping_timeout<(time()-self::$serverPingID['time'])){
//            //parse all responses
//        }
    }
//    public static function result(string $xml) {


    /**
     * @param array $param
     */
    public static function response(array $param)
    {
        $xml = "<iq from='$param[to]' to='$param[from]' id='$param[id]' type='result'/>";
        self::$XMPPConnectionManager->XMPPServer->write($xml);
    }
}
