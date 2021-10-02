<?php

namespace PhpPush\XMPP\UI;

use DOMDocument;
use DOMNode;


final class XEP0004 {
    private static ?XEP0004 $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): XEP0004
    {
        if (XEP0004::$instance === null) {
            XEP0004::$instance = new XEP0004();
        }
        return XEP0004::$instance;
    }

    /**
     * XMPP0004: 3
     * @param $data
     * @return array
     */
    public function parse($data): array
    {
        preg_match_all(
            '/<x
    (?:\s+
      (?:
         xmlns=["\']jabber:x:data["\']
        |
         type=["\'](?P<type>[^"\'<>]+)["\']
        |
         \w+=["\'][^"\'<>]+["\']
      )
    )+>(?P<data>[\w\W]+)<\/x>/ix',
            $data, $result, PREG_PATTERN_ORDER);
        $return['type'] = $result['type'][0];
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($result['data'][0]);
        $title = $doc->getElementsByTagName('title');
        if ($title->length > 0) {
            $return['title'] = $title[0]->nodeValue;
        }
        $instructions = $doc->getElementsByTagName('instructions');
        if ($instructions->length > 0) {
            $return['instructions'] = $instructions[0]->nodeValue;
        }
        $reported = $doc->getElementsByTagName('reported');
        $index = 0;
        if ($reported->length > 0) {
            foreach ($reported[0]->getElementsByTagName('field') as $element) {
                $return['reported'][$index] = $this->parseField($element);
                $index++;
            }
            $items = $doc->getElementsByTagName('item');
            $itemIndex = 0;
            foreach ($items as $item){
                $index = 0;
                foreach ($item->getElementsByTagName('field') as $element) {
                    $return['items'][$itemIndex][$index] = $this->parseField($element);
                    $index++;
                }
                $itemIndex++;
            }
        } else {
            foreach ($doc->getElementsByTagName('field') as $element) {
                $return[$index] = $this->parseField($element);
                $index++;
            }
        }
        return $return;
    }

    /**
     * @param DOMNode $element
     * @return array
     */
    private function parseField(DOMNode $element): array
    {
        $return = [];
        foreach ($element->attributes as $attribute){
            $return[$attribute->name] = $attribute->value;
        }
        $optionIndex = 0;
        $options = [];
        foreach ($element->childNodes as $child){
            if ($child->nodeName == 'value') {
                $return['value'][] = $child->nodeValue;
            } elseif ($child->nodeName == 'option') {
                foreach ($child->attributes as $attribute){
                    $options[$optionIndex][$attribute->name] = $attribute->value;
                    foreach ($child->childNodes as $grandChild){
                        if ($grandChild->nodeName == 'value') {
                            $options[$optionIndex]['value'][] = $grandChild->nodeValue;
                        }
                    }
                    $optionIndex++;
                }
                $return['options'] = $options;
            } elseif ($child->nodeName == 'required'){
                $return['required'] = 'required';
            } elseif ($child->nodeName == 'desc'){
                $return['desc'] = $child->nodeValue;
            }
        }
        return $return;
    }

    /**
     * XEP0004: 6
     * @param string $to
     * @return bool
     */
    public function supportInMessage(string $to): bool
    {
        $data = XEP0030::getInstance()->query($to);
        foreach ($data['feature'] as $value){
            if ($value['var'] == 'jabber:x:data') {
                return true;
            }
        }
        return false;
    }
}
