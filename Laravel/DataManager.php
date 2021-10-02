<?php

namespace PhpPush\XMPP\Laravel;

use Cache;

class DataManager
{
    const USER = 'XMPP-USER';
    const USER_JID = 'XMPP-USER-JID';
    const USER_RESOURCE = 'XMPP-USER-RESOURCE';
    const HOST = 'XMPP-HOST';
    const PORT = 'XMPP-PORT';
    const AUTH_TYPE = 'XMPP-AUTH-TYPE';
    const PROTOCOL = 'XMPP-PROTOCOL';
    const SUPPORTED_AUTH_METHODS = 'XMPP-SUPPORTED-AUTH-METHODS';
    const LOADED_EXTENSIONS = 'XMPP-LOADED-EXTENSIONS';
    const RESPONSE = 'XMPP-RESPONSE';
    const SENT = 'XMPP-SENT';

    const REG_FIELDS = 'XMPP-REG-FIELDS';

    /**
     * @param  string $type
     * @return mixed
     */
    public function getData(string $type): mixed
    {
        $data = Cache::get($type);
        $ob = json_decode($data, true);
        return ($ob === null)? $data : $ob;
    }

    /**
     * @param string $type
     * @param mixed $data
     * @param int $expiry
     * @param bool $json
     */
    public function setData(string $type, mixed $data, int $expiry = 0, bool $json = false)
    {
        $data = $json? json_encode($data) : $data;
        if ($expiry != 0) {
            Cache::put($type, $data, $expiry);
        } else {
            Cache::put($type, $data);
        }
    }

    public function getResponseOf($xml) {
        while (true){
            if ($this->getData(DataManager::SENT) == $xml) {
                while (true){
                    $data = $this->getData(DataManager::RESPONSE);
                    if ($data != '') {
                        return $data;
                    }
                }
            }
        }
    }
    private static ?DataManager $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): DataManager
    {
        if (DataManager::$instance === null) {
            DataManager::$instance = new DataManager();
        }
        return DataManager::$instance;
    }
}
