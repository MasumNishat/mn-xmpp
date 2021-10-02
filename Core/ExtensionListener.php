<?php

namespace PhpPush\XMPP\Core;

use PhpPush\XMPP\Interfaces\XMPPExtension;
use PhpPush\XMPP\Laravel\DataManager;

final class ExtensionListener
{
    private static ?ExtensionListener $instance = null;
    private array $extensions = [];

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): ExtensionListener
    {
        if (ExtensionListener::$instance === null) {
            ExtensionListener::$instance = new ExtensionListener();
        }
        return ExtensionListener::$instance;
    }

    public function connect(LaravelXMPPConnectionManager $connection): ?ExtensionListener
    {
        $this->load($connection);
        return $this::$instance;
    }

    public function onBeforeWrite(string $xml)
    {
        array_walk($this->extensions, fn($item)=> $item->onBeforeWrite($xml));
    }

    public function onAfterWrite(string $xml)
    {
        array_walk($this->extensions, fn($item)=> $item->onAfterWrite($xml));
    }

    public function onRead(string $response)
    {
        foreach ($this->extensions as $key=>$extension){
            $extension->onRead($response);
        }
    }

    /**
     * @param string $extension
     * @return XMPPExtension|false
     */
    public function getExtension(string $extension): XMPPExtension|bool
    {
        if (isset($this->extensions[$extension])) {
            return $this->extensions[$extension];
        }
        foreach ($this->extensions as $key => $value) {
            if (preg_match('/(\\\|^)' . $extension . '$/', $key)) {
                return $value;
            }
        }
        return false;
    }

    public function isExtensionLoaded(string $extension): bool
    {
        if (isset($this->extensions[$extension])) return true;
        foreach ($this->extensions as $key => $value){
            if (preg_match('/(\\\|^)'.$extension.'$/', $key)) {
                return true;
            }
        }
        return false;
    }

    private function __construct()
    {
    }

    private function isValidExtension(string $extension): bool
    {
        return (
            preg_match('/(\\\|^)XEP\d{4}$/', $extension) &&
            class_exists($extension) &&
            is_a($extension, XMPPExtension::class, true)
        );
    }

    private function load(LaravelXMPPConnectionManager $connection): void
    {
        foreach ($connection->getExtensions() as $extension) {

            if ($this->isValidExtension($extension[0])) {
                $this->extensions[$extension[0]] = $extension[0]::getInstance()->connect($connection);
                if ($this->extensions[$extension[0]]) {
                    DataManager::getInstance()->setData(DataManager::LOADED_EXTENSIONS, array_keys($this->extensions), 0, true);
                }
                foreach ($extension[1] as $key => $value) {
                    if (method_exists($extension[0], $key)) {
                        $this->extensions[$extension[0]]->$key($value);
                    } else {
                        $connection->getServerListener()->onError(['warning', "Configuration option '$key' not fount in extension $extension[0]"]);
                    }
                }
            } else {
                $connection->getServerListener()->onError(['warning', "Extension $extension[0] is invalid"]);
            }
        }
    }
}
