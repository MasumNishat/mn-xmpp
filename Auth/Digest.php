<?php
namespace PhpPush\XMPP\Auth;

use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Helpers\Functions;
use PhpPush\XMPP\Helpers\XMPP_XML;
use PhpPush\XMPP\Interfaces\XMPPAuth;

class Digest implements XMPPAuth {
    private LaravelXMPPConnectionManager $connection;
    private string $challenge='';
    private string $service='xmpp';
    private static ?Digest $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     * @param LaravelXMPPConnectionManager $connection
     * @return Digest
     */
    public static function attach(LaravelXMPPConnectionManager $connection): Digest
    {
        if (Digest::$instance === null) {
            Digest::$instance = new Digest($connection);
        }
        return Digest::$instance;
    }
    private function __construct(LaravelXMPPConnectionManager $connection) {
        $this->connection = $connection;
    }
    function auth(string $method=''): bool
    {
        //step-1: send request
        $xml = "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5' />";
        $this->connection->write($xml);
        //step-2: receive challenge
        $challengeBase64 = strip_tags($this->connection->getResponse());
        $this->challenge = base64_decode($challengeBase64);
        //step-3: sent first response
        $respond = "<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>".$this->encodedCredentials()."</response>";
        $this->connection->write($respond);
        //step-3: sent second response
        if (XMPP_XML::parse($this->connection->getResponse())->exist('challenge')) {
            $this->connection->write("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>");
            //todo: when authzid applied, <failure xmlns='urn:ietf:params:xml:ns:xmpp-sasl'><not-authorized/><text xml:lang='en'>Nodeprep failed</text></failure>
        }
        if (XMPP_XML::parse($this->connection->getResponse())->exist('success')) {
            return true;
        }
        return false;
    }
    private function encodedCredentials(): string
    {
        $credentials = $this->getResponse();
        return htmlspecialchars(base64_encode($credentials), ENT_XML1, 'utf-8');
    }

    /**
     * Provides the (main) client response for DIGEST-MD5
     * requires a few extra parameters than the other
     * mechanisms, which are unavoidable.
     *
     * @param  string $authzid   Authorization id (username to proxy as)
     * @return string            The digest response (NOT base64 encoded)
     * @access public
     */
    private function getResponse($authzid = '')
    {
        $challenge = $this->parseChallenge($this->challenge);
        $authzid_string = '';
        if ($authzid != '') {
            $authzid_string = ',authzid="' . $authzid . '"';
        }

        if (!empty($challenge)) {
            $cnonce         = Functions::getCnonce();
            $digest_uri     = sprintf('%s/%s', $this->service, $this->connection->getHost());
            $response_value = $this->getResponseValue($challenge['realm'], $challenge['nonce'], $cnonce, $digest_uri, $authzid);

            if ($challenge['realm']) {
                return sprintf('username="%s",realm="%s"' . $authzid_string  .
                    ',nonce="%s",cnonce="%s",nc=00000001,qop=auth,digest-uri="%s",response=%s,maxbuf=%d', $this->connection->getUser(), $challenge['realm'], $challenge['nonce'], $cnonce, $digest_uri, $response_value, $challenge['maxbuf']);
            } else {
                return sprintf('username="%s"' . $authzid_string  . ',nonce="%s",cnonce="%s",nc=00000001,qop=auth,digest-uri="%s",response=%s,maxbuf=%d', $this->connection->getUser(), $challenge['nonce'], $cnonce, $digest_uri, $response_value, $challenge['maxbuf']);
            }
        } else {
            //todo: throw error
        }
    }

    /**
     * Parses and verifies the digest challenge*
     *
     * @return array             The parsed challenge as an assoc
     *                           array in the form "directive => value".
     * @access private
     */
    private function parseChallenge($challenge)
    {
        $tokens = array();
        while (preg_match('/^([a-z-]+)=("[^"]+(?<!\\\)"|[^,]+)/i', $challenge, $matches)) {

            // Ignore these as per rfc2831
            if ($matches[1] == 'opaque' OR $matches[1] == 'domain') {
                $challenge = substr($challenge, strlen($matches[0]) + 1);
                continue;
            }

            // Allowed multiple "realm" and "auth-param"
            if (!empty($tokens[$matches[1]]) AND ($matches[1] == 'realm' OR $matches[1] == 'auth-param')) {
                if (is_array($tokens[$matches[1]])) {
                    $tokens[$matches[1]][] = preg_replace('/^"(.*)"$/', '\\1', $matches[2]);
                } else {
                    $tokens[$matches[1]] = array($tokens[$matches[1]], preg_replace('/^"(.*)"$/', '\\1', $matches[2]));
                }

                // Any other multiple instance = failure
            } elseif (!empty($tokens[$matches[1]])) {
                $tokens = array();
                break;

            } else {
                $tokens[$matches[1]] = preg_replace('/^"(.*)"$/', '\\1', $matches[2]);
            }

            // Remove the just parsed directive from the challenge
            $challenge = substr($challenge, strlen($matches[0]) + 1);
        }

        /**
         * Defaults and required directives
         */
        // Realm
        if (empty($tokens['realm'])) {
            $tokens['realm'] = "";
        }

        // Maxbuf
        if (empty($tokens['maxbuf'])) {
            $tokens['maxbuf'] = 65536;
        }

        // Required: nonce, algorithm
        if (empty($tokens['nonce']) OR empty($tokens['algorithm'])) {
            return array();
        }

        return $tokens;
    }

    /**
     * Creates the response= part of the digest response
     *
     * @param  string $realm      Realm as provided by the server
     * @param  string $nonce      Nonce as provided by the server
     * @param  string $cnonce     Client nonce
     * @param  string $digest_uri The digest-uri= value part of the response
     * @param  string $authzid    Authorization id
     * @return string             The response= part of the digest response
     * @access private
     */
    private function getResponseValue($realm, $nonce, $cnonce, $digest_uri, $authzid = '')
    {
        if ($authzid == '') {
            $A1 = sprintf('%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', $this->connection->getUser(), $realm, $this->connection->getPassword()))), $nonce, $cnonce);
        } else {
            $A1 = sprintf('%s:%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', $this->connection->getUser(), $realm, $this->connection->getPassword()))), $nonce, $cnonce, $authzid);
        }
        $A2 = 'AUTHENTICATE:' . $digest_uri;
        return md5(sprintf('%s:%s:00000001:%s:auth:%s', md5($A1), $nonce, $cnonce, md5($A2)));
    }
}
