<?php
/*
 *
 *  // +-------------------------------------------------------------------------+
 *  // | Copyright (c) 2021 Al Masum Nishat                                      |
 *  // | All rights reserved.                                                    |
 *  // |                                                                         |
 *  // | Redistribution and use in source and binary forms, with or without      |
 *  // | modification, are permitted provided that the following conditions      |
 *  // | are met:                                                                |
 *  // |                                                                         |
 *  // | o Redistributions of source code must retain the above copyright        |
 *  // |   notice, this list of conditions and the following disclaimer.         |
 *  // | o Redistributions in binary form must reproduce the above copyright     |
 *  // |   notice, this list of conditions and the following disclaimer in the   |
 *  // |   documentation and/or other materials provided with the distribution.  |
 *  // | o The names of the authors may not be used to endorse or promote        |
 *  // |   products derived from this software without specific prior written    |
 *  // |   permission.                                                           |
 *  // |                                                                         |
 *  // | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS     |
 *  // | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT       |
 *  // | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR   |
 *  // | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT    |
 *  // | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,   |
 *  // | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT        |
 *  // | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,   |
 *  // | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY   |
 *  // | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT     |
 *  // | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE   |
 *  // | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.    |
 *  // |                                                                         |
 *  // +-------------------------------------------------------------------------+
 *  // | Author: Al Masum Nishat <masum.nishat21@gmail.com>                      |
 *  // +-------------------------------------------------------------------------+
 *
 */

namespace PhpPush\XMPP\Core;

use Cache;
use Exception;
use PhpPush\XMPP\Errors\XMPPError;
use PhpPush\XMPP\Helpers\XMPP_XML;
use PhpPush\XMPP\Interfaces\LaravelXMPPClientListener;
use PhpPush\XMPP\Interfaces\LaravelXMPPServerListener;

final class LaravelXMPPConnectionManager
{
    private static ?LaravelXMPPConnectionManager $instance = null;

    private array $config;
    private $socket;
    private string $response;
    private bool $authenticated = false;
    private array $authMethods = [];
    private string $jid = '';
    private string $resource = '';
    private ?ExtensionListener $extension;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance($config = []): LaravelXMPPConnectionManager
    {
        if (LaravelXMPPConnectionManager::$instance === null) {
            LaravelXMPPConnectionManager::$instance = new LaravelXMPPConnectionManager($config);
        }
        return LaravelXMPPConnectionManager::$instance;
    }

    private function __construct($config)
    {
        $this->config = $config;
        $this->socket = stream_socket_client($this->getSocketAddress(), $ec,
            $em, null, STREAM_CLIENT_ASYNC_CONNECT, $this->getSocketHeaders());
        if (!$this->socket) {
            $this->getServerListener()->onError([$ec, $em]);
        }
        stream_set_timeout($this->socket, 0, 5000);
        $this->handshake();
    }

    /**
     * @param string $xml
     * @return bool
     */
    public function write(string $xml, bool $read = true): bool
    {
        try {
            if ($this->authenticated) $this->extension->onBeforeWrite($xml);
            fwrite($this->socket, $xml);
            if ($this->authenticated) $this->extension->onAfterWrite($xml);
        } catch (Exception $e) {
            $this->getServerListener()->onError([$e->getCode(), $e->getMessage()]);
            return false;
        }
        $this->getServerListener()->onWrite($xml);

        if ($read) {
            while ($this->read() == false){}
        }

        return true;
    }

    /**
     * @param int $retry
     * @return bool
     */
    public function read(int $retry = 0): bool
    {
        $this->response = '';
        while ($out = fgets($this->socket)) {
            $this->response .= $out;
        }
        if (!$this->response) {
            if ($retry <= $this->config['read-retry']) {
                return $this->read(++$retry);
            } else {
                return false;
            }
        }
        if ($this->authenticated) {
            if (XMPPError::check($this->response)) {
                $error = XMPPError::getLastError();
                $this->getClientListener()->onError($error);
            }
            $this->getClientListener()->onRead($this->response);
            $this->extension->onRead($this->response);
            //check ping
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
                $this->response, $checkPing, PREG_PATTERN_ORDER);
            if (isset($checkPing['type'][0]) && $checkPing['type'][0] == 'get') {
                //check if ping is supported
                if (!$this->extension->isExtensionLoaded('XEP0199')) {
                    $xml = "<iq from='" . $checkPing['to'][0] . "' to='" . $checkPing['from'][0] . "' id='" . $checkPing['id'][0] . "' type='error'>
                            <ping xmlns='urn:xmpp:ping'/>
                            <error type='cancel'>
                                <service-unavailable xmlns='urn:ietf:params:xml:ns:xmpp-stanzas'/>
                            </error>
                        </iq>";
                    $this->write($xml);
                }
            }
        } else {
            if (XMPPError::check($this->response)) {
                $error = XMPPError::getLastError();
                $this->getServerListener()->onError($error);
            }
            $this->getServerListener()->onRead($this->response);
        }
        return true;
    }

    public function listen() {
        while ($this->socket) {
            $i=0;
            if (Cache::has('xmpp-write-data'.$i)) {
                while (true){
                    $data = Cache::pull('xmpp-write-data'.$i);
                    if ($data == null) {
                        break;
                    } else {
                        $this->write($data);
                        $i++;
                    }
                }
            } else {
                $this->read();
            }
        }
        die("Socket disconnected");
    }

    public function logout(bool $force = false) {
        //rfc6120, section: 4.4
        if (!$force) {
            $this->write("</stream:stream>");
        }
        fclose($this->socket);
    }


    /**
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getJid(): string
    {
        if ($this->jid == '') {
            $this->setResource();
            $this->jid =$this->config['admin-user']."@". $this->config['host']."/$this->resource";
        }
        return $this->jid;
    }

    private function setResource(): void
    {
        if ($this->resource == '') {
            $this->resource = $this->config['resource']['prefix'].md5(uniqid("", true)).$this->config['resource']['suffix'];
        }
    }

    /**
     * @return array
     */
    public function getSupportedAuthMethods(): array
    {
        return $this->authMethods;
    }

    /**
     * @return LaravelXMPPServerListener
     */
    public function getServerListener(): LaravelXMPPServerListener
    {
        return new $this->config['listeners']['server']();
    }

    /**
     * @return LaravelXMPPClientListener
     */
    public function getClientListener(): LaravelXMPPClientListener
    {
        return new $this->config['listeners']['client']();
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->config['host'];
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->config['admin-user'];
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->config['admin-password'];
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->config['protocol'];
    }

    /**
     * @return string
     */
    public function getAuthType(): string
    {
        return strtoupper($this->config['auth-type']);
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        if ($this->config['autodetect-port']) {
            //todo: autodetect from host srv record
        } else {
            return ($this->config['port'] + 0);
        }
    }

    /**
     * @return string
     */
    private function getSocketAddress(): string
    {
        return $this->getProtocol() . "://" . $this->getHost() . ":" . $this->getPort();
    }

    /**
     * @return resource
     */
    private function getSocketHeaders()
    {
        return stream_context_create($this->config['headers']);
    }

    private function handshake()
    {
        /**
         * rfc6120 standard. according to 4.7.1: if the client considers the XMPP identity to
         * be private information then it is advised not to include a 'from' attribute before
         * the confidentiality and integrity of the stream are protected via TLS or an
         * equivalent security layer.
         */
        $xml1 = "<?xml version='1.0' encoding='UTF-8'?>
        <stream:stream to='" . $this->getHost() . "'
        xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' version='1.0'>";
        $this->write($xml1);
        if (XMPP_XML::parse($this->response)->exist('required>starttls')) {
            /**
             * rfc6120 standard. according to 5.4.2.1
             */
            $xml2 = "<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'/>";
            $this->write($xml2);
            if (XMPP_XML::parse($this->response)->exist('proceed')) {
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                $xml3 = "<?xml version='1.0' encoding='UTF-8'?>
                <stream:stream to='" . $this->getHost() . "'
                xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client'
                version='1.0'>";
                $this->write($xml3);
                $this->authMethods = XMPP_XML::parse($this->response)->getAuthMethods();
                if (empty($this->authMethods)) {
                    $this->getServerListener()->onError(['No valid authentication method found']);
                } elseif ($this->auth()) {
                    $this->resourceBinding();
                    $this->extension = ExtensionListener::getInstance()->connect($this);
                    $this->authenticated = true;
                }
            } elseif (XMPP_XML::parse($this->response)->exist('failure')) {
                $this->getServerListener()->onError(['TLS connection failed']);
            } else {
                $this->getServerListener()->onError(['Unknown error on TLS proceed']);
            }
        }
        //todo: non tls connection to be handled
    }

    /**
     * @return bool
     */
    private function auth(): bool
    {
        if (in_array($this->getAuthType(), $this->authMethods)) {
            $parts = explode('-', strtolower($this->getAuthType()));
            $method = "PhpPush\XMPP\Auth\\" . ucfirst($parts[0]);
            unset($parts[0]);
            $algo = implode("-", $parts);
            if (class_exists($method)) {
                return $method::attach($this)->auth($algo);
            }
        } else {
            $this->getClientListener()->onError(['Unsupported authentication method configured']);
        }
        return false;
    }
    private function resourceBinding()
    {
        $xml1 = "<?xml version='1.0' encoding='UTF-8'?><stream:stream
        to='" . $this->getHost() . "' xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' version='1.0'>";
        $this->write($xml1);
        $bindExist = XMPP_XML::parse($this->getResponse())->exist('bind');
        $sessionExist = XMPP_XML::parse($this->getResponse())->exist('session');
        if ($bindExist) {
            $xml2 = "<iq type='set' id='" . uniqid(rand(), true) .
                "'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>" .
                $this->resource . "</resource></bind></iq>";
            $this->write($xml2);
            if ($sessionExist) {
                /**
                 * rfc3921, section 3
                 */
                $xml3 = "<iq to='" . $this->getHost() . "' type='set' id='" . uniqid(rand(), true) . "'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>";
                $this->write($xml3);
            }
        }
    }

    /**
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->config['extensions'];
    }

}
