<?php
namespace PhpPush\XMPP\Helpers;

use DOMDocument;

class XMPP_XML
{
    public static DOMDocument $input;

    public static function hasNS(string $ns, string $tag = 'feature', string $nsname = 'var'): bool
    {
        $elements = self::$input->getElementsByTagName($tag);
        foreach ($elements as $element) {
            $attributes = $element->attributes;
            foreach ($attributes as $attribute){
                if ($attribute->name == $nsname && $attribute->value == $ns) {
                    return true;
                }
            }
        }
        return false;
    }

    public function exist(string $chain): bool
    {
        $nodes = explode('>', $chain);
        $testNode = array_pop($nodes);
        $data = self::$input;
        $elements = $data->getElementsByTagName($testNode);
        self::$input = new DOMDocument();
        foreach ($elements as $element) {
            self::$input->appendChild(
                self::$input->importNode($element, true));
        }
        $found = self::$input->getElementsByTagName($testNode);
        if ($found->length > 0) {
            if (count($nodes) > 0) {
                return $this->exist(implode('>', $nodes));
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function getAuthMethods(): array
    {
        $return = [];
        if ($this->exist('mechanism')) {
            $mechanism = self::$input->getElementsByTagName('mechanism');
            foreach ($mechanism as $value) {
                $return[] = $value->nodeValue;
            }
        }
        return $return;
    }

    public static function parse($data)
    {
        $doc = new DOMDocument(); // create DOMDocument
        libxml_use_internal_errors(true);
        $doc->loadHTML($data);

        self::$input = $doc;
//
//        $tls=$doc->getElementsByTagName('starttls');
//        $item = $tls->item(0);
//        print_r($item->attributes);
//        foreach ( $item->childNodes as $pp ) {
//
//            if ( $pp->nodeName == 'required' ) {
//
//                if ( strlen( $pp->nodeValue ) ) {
//                    echo "{$pp->nodeValue}\n";
//                }
//
//            }
//
//        }

        //print_r($item->childNodes); // load HTML you can add $html
//libxml_clear_errors();

//        if ($data) {
//            $divs = $doc->getElementById('core');
//
//            $xpath = new DOMXPath($doc);
//
//            $backLink = $xpath->query('//p[@id="back-link"]');
//
//            //$sorryId = $xpath->query('//h1[@id="sorry"]');
//            //$promiseId = $xpath->query('//p[@id="promise"]');
//
//
//            $node = $backLink->item(0);
//            $href = $node->getAttribute('href');
//            echo '<pre>';print_r($node);echo '</pre>';
//        }
        return new XMPP_XML();
    }
}
