<?php
namespace MN\XMPP\Interface;

interface XMPPExtension
{
    /**
     * @param XMPPConnectionManager $XMPPConnectionManager
     */
    public static function init(XMPPConnectionManager $XMPPConnectionManager): void;
    /**
     * @return string
     */
    public static function extension(): string;

    /**
     * @return string
     */
    public static function name(): string;

    /**
     * @return array
     */
    public static function ns(): array;

    /**
     * @param XMPPConnectionManager $XMPPConnectionManager
     */
    public static function XMPPConnectionManager(XMPPConnectionManager $XMPPConnectionManager): void;

}
