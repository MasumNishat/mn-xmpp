<?php
namespace PhpPush\XMPP\Helpers;

class Functions {
    /**
     * Function which implements HMAC MD5 digest
     *
     * @param string $key  The secret key
     * @param string $data The data to hash
     * @param bool $raw_output Whether the digest is returned in binary or hexadecimal format.
     *
     * @return string       The HMAC-MD5 digest
     */
    public static function HMAC_MD5(string $key, string $data, bool $raw_output = FALSE): string
    {
        if (strlen($key) > 64) {
            $key = pack('H32', md5($key));
        }

        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        $k_ipad = substr($key, 0, 64) ^ str_repeat(chr(0x36), 64);
        $k_opad = substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64);

        $inner  = pack('H32', md5($k_ipad . $data));
        return md5($k_opad . $inner, $raw_output);
    }

    /**
     * Function which implements HMAC-SHA-1 digest
     *
     * @param string $key  The secret key
     * @param string $data The data to hash
     * @param bool $raw_output Whether the digest is returned in binary or hexadecimal format.
     * @return string       The HMAC-SHA-1 digest
     * @author Jehan <jehan.marmottard@gmail.com>
     * @access protected
     */
    public static  function HMAC_SHA1(string $key, string $data, bool $raw_output = FALSE): string
    {
        if (strlen($key) > 64) {
            $key = sha1($key, TRUE);
        }

        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        $k_ipad = substr($key, 0, 64) ^ str_repeat(chr(0x36), 64);
        $k_opad = substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64);

        $inner  = pack('H40', sha1($k_ipad . $data));
        return sha1($k_opad . $inner, $raw_output);
    }

    /**
     * Creates the client nonce for the response
     *
     * @return string  The cnonce value
     * @access private
     * @author  Richard Heyes <richard@php.net>
     */
    public static function getCnonce(): string
    {
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
