<?php
namespace PhpPush\XMPP\Extensions;

use PhpPush\XMPP\Core\ExtensionListener;
use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Interfaces\XMPPExtension;

final class XEP0199 implements XMPPExtension
{
    private LaravelXMPPConnectionManager $connection;
    private static ?XEP0199 $instance = null;
    private int $lastServerConnectedAt = 0;
    private bool $isSupported;
    public bool $c2c_ping = false;
    public bool $c2s_ping = false;
    public array $serverPingID = [];
    public int $c2s_ping_interval = 60;
    public int $c2c_ping_interval = 60;
    public array $clientPingID = [];
    public int $c2s_ping_timeout = 45;
    public int $c2c_ping_timeout = 45;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): XEP0199
    {
        if (XEP0199::$instance === null) {
            XEP0199::$instance = new XEP0199();
        }
        return XEP0199::$instance;
    }

    public function connect(LaravelXMPPConnectionManager $connection): XEP0199 | bool
    {
        $this->connection = $connection;
        if (!self::isSupported(true)) {
            $this->connection->getServerListener()->onError(['warning', 'XEP0199 not supported by server']);
            return false;
        }
        return $this::$instance;
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return 'XEP-0199';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Service Discovery';
    }

    /**
     * @return string[]
     */
    public function ns(): array
    {
        return [
            'urn:xmpp:ping',
        ];
    }

    /**
     * @param bool $reload
     * @return bool
     */
    private function isSupported(bool $reload = false): bool
    {
        $this->isSupported = false;
        //check if XEP0030 is loaded
        $XEP0030 = ExtensionListener::getInstance()->getExtension('XEP0030');
        if ($XEP0030) {
            $this->isSupported = $XEP0030->checkNS($this->ns());
            if (!$this->isSupported && $reload) {
                $from = "from='" . $this->connection->getJid() . "'";
                $to = "to='" . $this->connection->getHost() . "'";
                $xml = "<iq type='get' $from $to id='" . md5(uniqid(rand(), true)) . "'><query xmlns='http://jabber.org/protocol/disco#info'/></iq>";
                $this->isSupported = $XEP0030->checkNS($this->ns(), true, $xml);
            }
        }
        return $this->isSupported;
    }

    public function c2c_ping(bool $value)
    {
        $this->c2c_ping = $value;
    }

    public function c2s_ping_interval(int $value)
    {
        $this->c2s_ping_interval = $value;
    }

    public function c2c_ping_interval(int $value)
    {
        $this->c2c_ping_interval = $value;
    }

    public function c2s_ping_timeout(int $value)
    {
        $this->c2s_ping_timeout = $value;
    }

    public function c2c_ping_timeout(int $value)
    {
        $this->c2c_ping_timeout = $value;
    }

    public function c2s_ping(bool $value)
    {
        $this->c2s_ping = $value;
    }

    public function pingClient(string $to)
    {
        $this->clientPingID[$to]['id'] = md5(uniqid(rand(), true));
        $this->clientPingID[$to]['time'] = time();
        $xml = "<iq from='" . $this->connection->getJid() . "' to='$to' id='" . $this->clientPingID[$to]['id'] . "' type='get'><ping xmlns='urn:xmpp:ping'/></iq>";
        $this->connection->write($xml);
    }

    public function pingServer()
    {
        $this->serverPingID['id'] = md5(uniqid(rand(), true));
        $this->serverPingID['time'] = time();
        $xml = "<iq from='" . $this->connection->getJid() . "' to='" . $this->connection->getHost() . "' id='" . $this->serverPingID['id'] . "' type='get'><ping xmlns='urn:xmpp:ping'/></iq>";
        $this->connection->write($xml);
    }


    /**
     * @param array $param
     */
    public function response(array $param)
    {
        $xml = "<iq from='$param[to]' to='$param[from]' id='$param[id]' type='result'/>";
        $this->connection->write($xml, false);
    }

    public function onBeforeWrite(string $xml)
    {
        if ($this->c2s_ping) {
            if(empty($this->serverPingID)) {
                if ((time()-$this->lastServerConnectedAt)>=($this->c2s_ping_interval)) {
                    var_dump($this->c2s_ping_interval);
                    var_dump($this->lastServerConnectedAt);
                    var_dump(time());
                    $this->pingServer();
                }
            } elseif (time()-$this->serverPingID['time'] >= $this->c2s_ping_timeout) {
                $this->connection->getServerListener()->onError(['Server ping timeout']);
                $this->connection->logout(true);
            }
        }
    }

    public function onAfterWrite(string $xml) {}

    public function onRead(string $response)
    {
        $this->lastServerConnectedAt = time();
        if (!empty($this->serverPingID) && str_contains($response, $this->serverPingID['id'])) {
            $this->serverPingID = [];
        }
        //check ping request
        preg_match_all(
            '/<iq
    (?:\s+
      (?:
         from=["\'](?P<from>[^"\'<>]+)["\']
        |
         type=["\'](?P<type>[^"\'<>]+)["\']
        |
         to=["\'](?P<to>[^"\'<>]+)["\']
        |
        id=["\'](?P<id>[^"\'<>]+)["\']
        |
         \w+=["\'][^"\'<>]+["\']
      )
    )+(.*)xmlns=["\']urn:xmpp:ping["\']/ix',
            $response, $result, PREG_PATTERN_ORDER);
        if (isset($result['type'][0]) && $result['type'][0] == 'get') {
            $this->response([
                'from' => $result['from'][0],
                'to' => $result['to'][0],
                'id' => $result['id'][0],
            ]);
        }
    }
    public function checkError(string $responseXML): bool
    {
        return false;
    }

    public function getLastError(): array
    {
        return [];
    }
}
