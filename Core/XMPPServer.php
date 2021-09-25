<?php
namespace MN\XMPP\Core;

class XMPPServer
{
    private XMPPServerOptions $XMPPServerOptions;
    public static mixed $socket;
    public array $authMethods;
    private string $response = '';
    public int $lastConnected;
    public bool $managerConnected = false;

    //public array
    public function __construct(XMPPServerOptions $XMPPServerOptions)
    {
        $this->XMPPServerOptions = $XMPPServerOptions;
        $socketAddress = $this->getConnectedProtocol() . "://" . $this->getConnectedHost() . ":" . $this->getConnectedPort();
        self::$socket = stream_socket_client($socketAddress, $ec,
            $em, null, STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create($this->getRequestedCustomSocketHeader()));
        if (!self::$socket) {
            $this->XMPPServerOptions->onError([$ec, $em]);
        }
        stream_set_timeout(self::$socket, 0, 150000);
        $this->lastConnected = time();
        $this->handshake();
        return $this;
    }

    public function getXMPPServerOptions(): XMPPServerOptions
    {
        return $this->XMPPServerOptions;
    }

    public function getAuthMethods(): array
    {
        return $this->authMethods;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    protected function checkSocketStatus(): array|bool
    {
        if ($this->managerConnected && XMPPConnectionManager::isExtensionLoaded('XEP0199') && XEP0199::$c2s_ping_interval<(time()-$this->lastConnected)) {
            XEP0199::pingServer();
        }
        $status = socket_get_status(self::$socket);
        if ($status['eof']) {
            $this->XMPPServerOptions->onError([1001, 'Connection lost']);
            return false;
        }
        return $status;
    }

    /**
     * @return void
     */
    private function handshake(): void
    {
        /**
         * rfc6120 standard. according to 4.7.1: if the client considers the XMPP identity to
         * be private information then it is advised not to include a 'from' attribute before
         * the confidentiality and integrity of the stream are protected via TLS or an
         * equivalent security layer.
         */
        $xml1 = "<?xml version='1.0' encoding='UTF-8'?>
        <stream:stream to='" . $this->getConnectedHost() . "'
        xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' version='1.0'>";
        $this->write($xml1);
        if (XMPP_XML::parse($this->response)->exist('required>starttls')) {
            /**
             * rfc6120 standard. according to 5.4.2.1
             */
            $xml2 = "<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'/>";
            $this->write($xml2);
            if (XMPP_XML::parse($this->response)->exist('proceed')) {
                stream_socket_enable_crypto(self::$socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                $xml3 = "<?xml version='1.0' encoding='UTF-8'?>
                <stream:stream to='" . $this->getConnectedHost() . "'
                xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client'
                version='1.0'>";
                $this->write($xml3);
                $this->authMethods = XMPP_XML::parse($this->response)->getAuthMethods();
                if (empty($this->authMethods)) {
                    $this->XMPPServerOptions->onError([1004, 'No valid authentication method found']);
                }
                print_r($this->response);
            } elseif (XMPP_XML::parse($this->response)->exist('failure')) {
                $this->XMPPServerOptions->onError([1002, 'TLS connection failed']);
            } else {
                $this->XMPPServerOptions->onError([1003, 'Unknown error on TLS proceed']);
            }
        }
        //todo: non tls connection to be handled
    }

    public function close()
    {
        //rfc6120, section: 4.4
        $this->write("</stream:stream>");
        fclose(self::$socket);
    }

    public function write($data): bool
    {
        if (is_array($this->checkSocketStatus())) {
            try {
                fwrite(self::$socket, $data);
                print_r("<br/>\nSent:" . $data);
            } catch (Exception $e) {
                $this->XMPPServerOptions->onError([$e->getCode(), $e->getMessage()]);
                return false;
            }
            $this->read();
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function read(): bool
    {
        $this->response = '';
        while ($out = fgets(self::$socket)) {
            $this->response .= $out;
            $this->lastConnected = time();
        }
        if (!$this->response) {
            return false;
        }
        //determine type of response
        //error type
        if (XMPPError::check($this->response)) {
            $error = XMPPError::getLastError();
            $this->getXMPPServerOptions()->onError($error);
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
            $this->response, $result, PREG_PATTERN_ORDER);
        print_r("<br/>\nRecived:" . $this->response);
        if (isset($result['type'][0]) && $result['type'][0] == 'get') {
            //check if ping is supported
            if (XMPPConnectionManager::isExtensionLoaded('XEP0199')) {
                XEP0199::response([
                    'from' => $result['from'][0],
                    'to' => $result['to'][0],
                    'id' => $result['id'][0],
                ]);
            } else {
                $xml = "<iq from='" . $result['to'][0] . "' to='" . $result['from'][0] . "' id='" . $result['id'][0] . "' type='error'>
                            <ping xmlns='urn:xmpp:ping'/>
                            <error type='cancel'>
                                <service-unavailable xmlns='urn:ietf:params:xml:ns:xmpp-stanzas'/>
                            </error>
                        </iq>";
                $this->write($xml);
            }
        }


//        var_dump($checkPing);
        print_r("</br/><br/>\n\n");
        return true;
    }

    public function getConnectedHost(): string
    {
        return $this->XMPPServerOptions->setHost();
    }

    public function getConnectedProtocol(): string
    {
        return $this->XMPPServerOptions->setProtocol();
    }

    public function getConnectedPort(): string
    {
        return $this->XMPPServerOptions->setPort();
    }

    public function getRequestedCustomSocketHeader(): array
    {
        return $this->XMPPServerOptions->socketHeader();
    }

}
