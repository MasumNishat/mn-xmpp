<?php
namespace MN\XMPP\Helpers;

class Functions {
    /**
     * Function which implements HMAC MD5 digest
     *
     * @param  string $key  The secret key
     * @param  string $data The data to hash
     * @param  bool $raw_output Whether the digest is returned in binary or hexadecimal format.
     *
     * @return string       The HMAC-MD5 digest
     */
    public static function HMAC_MD5($key, $data, $raw_output = FALSE)
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
        $digest = md5($k_opad . $inner, $raw_output);

        return $digest;
    }

    /**
     * Function which implements HMAC-SHA-1 digest
     *
     * @param  string $key  The secret key
     * @param  string $data The data to hash
     * @param  bool $raw_output Whether the digest is returned in binary or hexadecimal format.
     * @return string       The HMAC-SHA-1 digest
     * @author Jehan <jehan.marmottard@gmail.com>
     * @access protected
     */
    public static  function HMAC_SHA1($key, $data, $raw_output = FALSE)
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
        $digest = sha1($k_opad . $inner, $raw_output);

        return $digest;
    }
}
