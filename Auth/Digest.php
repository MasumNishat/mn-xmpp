<?php
namespace MN\XMPP\Auth;

class Digest implements XMPPAuth {
    private static XMPPConnectionManager $XMPPConnectionManager;
    private static string $challenge='';
    private static string $service='xmpp';
    private static ?Digest $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function attach(XMPPConnectionManager $XMPPConnectionManager): Digest
    {
        if (Digest::$instance === null) {
            Digest::$instance = new Digest($XMPPConnectionManager);
        }
        return Digest::$instance;
    }
    private function __construct(XMPPConnectionManager $XMPPConnectionManager) {
        self::$XMPPConnectionManager = $XMPPConnectionManager;
    }
    function auth(string $method=''): bool
    {
        //step-1: send request
        $xml = "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5' />";
        self::$XMPPConnectionManager->XMPPServer->write($xml);
        //step-2: receive challenge
        $challengeBase64 = strip_tags(self::$XMPPConnectionManager->XMPPServer->getResponse());
        self::$challenge = base64_decode($challengeBase64);
        //step-3: sent first response
        $respond = "<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>".$this->encodedCredentials()."</response>";
        self::$XMPPConnectionManager->XMPPServer->write($respond);
        //step-3: sent second response
        if (XMPP_XML::parse(self::$XMPPConnectionManager->XMPPServer->getResponse())->exist('challenge')) {
            self::$XMPPConnectionManager->XMPPServer->write("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>");
            //todo: when authzid applied, <failure xmlns='urn:ietf:params:xml:ns:xmpp-sasl'><not-authorized/><text xml:lang='en'>Nodeprep failed</text></failure>
        }
        if (XMPP_XML::parse(self::$XMPPConnectionManager->XMPPServer->getResponse())->exist('success')) {
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
        $challenge = $this->parseChallenge(self::$challenge);
        $authzid_string = '';
        if ($authzid != '') {
            $authzid_string = ',authzid="' . $authzid . '"';
        }

        if (!empty($challenge)) {
            $cnonce         = $this->getCnonce();
            $digest_uri     = sprintf('%s/%s', self::$service, self::$XMPPConnectionManager->host);
            $response_value = $this->getResponseValue($challenge['realm'], $challenge['nonce'], $cnonce, $digest_uri, $authzid);

            if ($challenge['realm']) {
                return sprintf('username="%s",realm="%s"' . $authzid_string  .
                    ',nonce="%s",cnonce="%s",nc=00000001,qop=auth,digest-uri="%s",response=%s,maxbuf=%d', self::$XMPPConnectionManager->user, $challenge['realm'], $challenge['nonce'], $cnonce, $digest_uri, $response_value, $challenge['maxbuf']);
            } else {
                return sprintf('username="%s"' . $authzid_string  . ',nonce="%s",cnonce="%s",nc=00000001,qop=auth,digest-uri="%s",response=%s,maxbuf=%d', self::$XMPPConnectionManager->user, $challenge['nonce'], $cnonce, $digest_uri, $response_value, $challenge['maxbuf']);
            }
        } else {
            throw new Auth_SASL_Exception('Invalid digest challenge');
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
            $A1 = sprintf('%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', self::$XMPPConnectionManager->user, $realm, self::$XMPPConnectionManager->XMPPUser->getPassword()))), $nonce, $cnonce);
        } else {
            $A1 = sprintf('%s:%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', self::$XMPPConnectionManager->user, $realm, self::$XMPPConnectionManager->XMPPUser->getPassword()))), $nonce, $cnonce, $authzid);
        }
        $A2 = 'AUTHENTICATE:' . $digest_uri;
        return md5(sprintf('%s:%s:00000001:%s:auth:%s', md5($A1), $nonce, $cnonce, md5($A2)));
    }

    /**
     * Creates the client nonce for the response
     *
     * @return string  The cnonce value
     * @access private
     */
    private function getCnonce(): string
    {
        if (file_exists('/dev/urandom') && $fd = fopen('/dev/urandom', 'r')) {
            return base64_encode(fread($fd, 32));

        } elseif (file_exists('/dev/random') && $fd = fopen('/dev/random', 'r')) {
            return base64_encode(fread($fd, 32));

        } else {
            $str = '';
            for ($i=0; $i<32; $i++) {
                $str .= chr(mt_rand(0, 255));
            }

            return base64_encode($str);
        }
    }
}
