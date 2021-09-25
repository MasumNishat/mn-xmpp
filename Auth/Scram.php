<?php
namespace MN\XMPP\Auth;

class Scram implements XMPPAuth {

    private static XMPPConnectionManager $XMPPConnectionManager;
    private static ?Scram $instance = null;
    private Closure $hash;
    private Closure $hmac;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function attach(XMPPConnectionManager $XMPPConnectionManager): Scram
    {
        if (Scram::$instance === null) {
            Scram::$instance = new Scram($XMPPConnectionManager);
        }
        return Scram::$instance;
    }
    private function __construct(XMPPConnectionManager $XMPPConnectionManager) {
        self::$XMPPConnectionManager = $XMPPConnectionManager;
    }
    function auth(string $method = ''): bool
    {
        // Though I could be strict, I will actually also accept the naming used in the PHP core hash framework.
        // For instance "sha1" is accepted, while the registered hash name should be "SHA-1".
        $hash = strtolower($method);
//        $hash = str_replace("-plus", "", strtolower($method));
        $hashes = array('md2' => 'md2',
            'md5' => 'md5',
            'sha-1' => 'sha1',
            'sha1' => 'sha1',
            'sha-224' => 'sha224',
            'sha224' => 'sha224',
            'sha-256' => 'sha256',
            'sha256' => 'sha256',
            'sha-384' => 'sha384',
            'sha384' => 'sha384',
            'sha-512' => 'sha512',
            'sha512' => 'sha512');
        $f = fn($x) => [$x];;

        if (function_exists('hash_hmac') && isset($hashes[$hash]))
        {
            $this->hash = fn($data) => hash($hashes[$hash], $data, TRUE);
            $this->hmac = fn($key,$str,$raw) => hash_hmac($hashes[$hash], $str, $key, $raw);
        }
        elseif ($hash == 'md5')
        {
            $this->hash = fn($data) => md5($data, true);
            $this->hmac = fn($key,$str,$raw) => Functions::HMAC_MD5($key,$str,$raw);
        }
        elseif (in_array($hash, array('sha1', 'sha-1')))
        {
            $this->hash = fn($data) => md5($data, true);
            $this->hmac = fn($key,$str,$raw) => Functions::HMAC_SHA1($key,$str,$raw);
        }
        else {

        }

        $req = base64_encode($this->getResponse(self::$XMPPConnectionManager->user, self::$XMPPConnectionManager->XMPPUser->getPassword()));
        $xml = "<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='".self::$XMPPConnectionManager->XMPPUser->getAuthMethod()."' >$req</auth>";
        self::$XMPPConnectionManager->XMPPServer->write($xml);
        if (XMPP_XML::parse(self::$XMPPConnectionManager->XMPPServer->getResponse())->exist('challenge')) {
            $challengeBase64 = strip_tags(self::$XMPPConnectionManager->XMPPServer->getResponse());
            $challenge = base64_decode($challengeBase64);
            $parsed1 = base64_encode($this->getResponse(self::$XMPPConnectionManager->user, self::$XMPPConnectionManager->XMPPUser->getPassword(), $challenge, self::$XMPPConnectionManager->jid));
            self::$XMPPConnectionManager->XMPPServer->write("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>$parsed1</response>");
        }
        if (XMPP_XML::parse(self::$XMPPConnectionManager->XMPPServer->getResponse())->exist('success')) {
            $outcomeBase64 = strip_tags(self::$XMPPConnectionManager->XMPPServer->getResponse());
            $outcome = base64_decode($outcomeBase64);
            var_dump($this->processOutcome($outcome));
            return true;
        }

        var_dump([$method]);
        return false;
    }

    /**
     * Provides the (main) client response for SCRAM-H.
     *
     * @param string $authcid   Authentication id (username)
     * @param string $pass      Password
     * @param string|null $challenge The challenge sent by the server.
     * If the challenge is NULL or an empty string, the result will be the "initial response".
     * @param string|null $authzid   Authorization id (username to proxy as)
     * @return string|false      The response (binary, NOT base64 encoded)
     * @access public
     */
    public function getResponse(string $authcid, string $pass, string $challenge = NULL, string $authzid = NULL): bool|string
    {
        $authcid = $this->_format($authcid);
        if (empty($authcid))
        {
            return false;
        }
        if (!empty($authzid))
        {
            $authzid = $this->_format($authzid);
            if (empty($authzid))
            {
                return false;
            }
        }

        if (empty($challenge))
        {
            return $this->_generateInitialResponse($authcid, $authzid);
        }
        else
        {
            return $this->_generateResponse($challenge, $pass);
        }

    }

    /**
     * Prepare a name for inclusion in a SCRAM response.
     *
     * @param string $username a name to be prepared.
     * @return string the reformated name.
     * @access private
     */
    private function _format(string $username): string
    {
        // TODO: prepare through the SASLprep profile of the stringprep algorithm.
        // See RFC-4013.

        $username = str_replace('=', '=3D', $username);
        return str_replace(',', '=2C', $username);
    }

    /**
     * Generate the initial response which can be either sent directly in the first message or as a response to an empty
     * server challenge.
     *
     * @param string $authcid Prepared authentication identity.
     * @param string $authzid Prepared authorization identity.
     * @return string The SCRAM response to send.
     * @access private
     */
    private function _generateInitialResponse(string $authcid, $authzid): string
    {
        $init_rep = '';
        $gs2_cbind_flag = 'n,'; // TODO: support channel binding.
        $this->gs2_header = $gs2_cbind_flag . (!empty($authzid)? 'a=' . $authzid : '') . ',';

        // I must generate a client nonce and "save" it for later comparison on second response.
        $this->cnonce = $this->_getCnonce();
        // XXX: in the future, when mandatory and/or optional extensions are defined in any updated RFC,
        // this message can be updated.
        $this->first_message_bare = 'n=' . $authcid . ',r=' . $this->cnonce;
        return $this->gs2_header . $this->first_message_bare;
    }

    /**
     * Parses and verifies a non-empty SCRAM challenge.
     *
     * @param string $challenge The SCRAM challenge
     * @return string|false      The response to send; false in case of wrong challenge or if an initial response has not
     * been generated first.
     * @access private
     */
    private function _generateResponse(string $challenge, $password): bool|string
    {
        // XXX: as I don't support mandatory extension, I would fail on them.
        // And I simply ignore any optional extension.
        $server_message_regexp = "#^r=([\x21-\x2B\x2D-\x7E]+),s=((?:[A-Za-z0-9/+]{4})*(?:[A-Za-z0-9]{3}=|[A-Xa-z0-9]{2}==)?),i=([0-9]*)(,[A-Za-z]=[^,])*$#";
        if (!isset($this->cnonce, $this->gs2_header)
            || !preg_match($server_message_regexp, $challenge, $matches))
        {
            return false;
        }
        $nonce = $matches[1];
        $salt = base64_decode($matches[2]);
        if (!$salt)
        {
            // Invalid Base64.
            return false;
        }
        $i = intval($matches[3]);

        $cnonce = substr($nonce, 0, strlen($this->cnonce));
        if ($cnonce <> $this->cnonce)
        {
            // Invalid challenge! Are we under attack?
            return false;
        }

        $channel_binding = 'c=' . base64_encode($this->gs2_header); // TODO: support channel binding.
        $final_message = $channel_binding . ',r=' . $nonce; // XXX: no extension.

        // TODO: $password = $this->normalize($password); // SASLprep profile of stringprep.
        $saltedPassword = $this->_hi($password, $salt, $i);
        $this->saltedPassword = $saltedPassword;
        $clientKey = call_user_func($this->hmac, $saltedPassword, "Client Key", TRUE);
        $storedKey = call_user_func($this->hash, $clientKey, TRUE);
        $authMessage = $this->first_message_bare . ',' . $challenge . ',' . $final_message;
        $this->authMessage = $authMessage;
        $clientSignature = call_user_func($this->hmac, $storedKey, $authMessage, TRUE);
        $clientProof = $clientKey ^ $clientSignature;
        $proof = ',p=' . base64_encode($clientProof);

        return $final_message . $proof;
    }

    /**
     * SCRAM has also a server verification step. On a successful outcome, it will send additional data which must
     * absolutely be checked against this function. If this fails, the entity which we are communicating with is probably
     * not the server as it has not access to your ServerKey.
     *
     * @param string $data The additional data sent along a successful outcome.
     * @return bool Whether the server has been authenticated.
     * If false, the client must close the connection and consider to be under a MITM attack.
     * @access public
     */
    public function processOutcome(string $data): bool
    {
        $verifier_regexp = '#^v=((?:[A-Za-z0-9/+]{4})*(?:[A-Za-z0-9]{3}=|[A-Xa-z0-9]{2}==)?)$#';
        if (!isset($this->saltedPassword, $this->authMessage)
            || !preg_match($verifier_regexp, $data, $matches))
        {
            // This cannot be an outcome, you never sent the challenge's response.
            return false;
        }

        $verifier = $matches[1];
        $proposed_serverSignature = base64_decode($verifier);
        $serverKey = call_user_func($this->hmac, $this->saltedPassword, "Server Key", true);
        $serverSignature = call_user_func($this->hmac, $serverKey, $this->authMessage, TRUE);
        return ($proposed_serverSignature === $serverSignature);
    }

    /**
     * Hi() call, which is essentially PBKDF2 (RFC-2898) with HMAC-H() as the pseudorandom function.
     *
     * @param string $str The string to hash.
     * @param string $salt
     * @param int $i The iteration count.
     * @return mixed
     * @access private
     */
    private function _hi(string $str, string $salt, int $i): mixed
    {
        $int1 = "\0\0\0\1";
        $ui = call_user_func($this->hmac, $str, $salt . $int1, true);
        $result = $ui;
        for ($k = 1; $k < $i; $k++)
        {
            $ui = call_user_func($this->hmac, $str, $ui, true);
            $result = $result ^ $ui;
        }
        return $result;
    }


    /**
     * Creates the client nonce for the response
     *
     * @return string  The cnonce value
     * @access private
     * @author  Richard Heyes <richard@php.net>
     */
    private function _getCnonce(): string
    {
        // TODO: I reused the nonce function from the DigestMD5 class.
        // I should probably make this a protected function in Common.
        if (@file_exists('/dev/urandom') && $fd = @fopen('/dev/urandom', 'r')) {
            return base64_encode(fread($fd, 32));

        } elseif (@file_exists('/dev/random') && $fd = @fopen('/dev/random', 'r')) {
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
