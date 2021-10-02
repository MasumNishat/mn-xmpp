<?php
namespace PhpPush\XMPP\Extensions;

use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Helpers\XMPP_XML;
use PhpPush\XMPP\Interfaces\XMPPExtension;

final class XEP0030 implements XMPPExtension
{
    private LaravelXMPPConnectionManager $connection;
    private static ?XEP0030 $instance = null;
    private array $XMPP_NS;
    private bool $isSupported;
    private array $generalSupport;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): XEP0030
    {
        if (XEP0030::$instance === null) {
            var_dump("ext instance loaded");
            XEP0030::$instance = new XEP0030();
        }
        return XEP0030::$instance;
    }


    public function connect(LaravelXMPPConnectionManager $connection): XEP0030
    {
        $this->connection = $connection;
        $this->XMPP_NS = json_decode(
            <<<END
[
    {
        "support": false,
        "name": "http://jabber.org/protocol/activity",
        "xep": "XEP-0108",
        "des": "User Activity"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/address",
        "xep": "XEP-0033",
        "des": "Extended Stanza Addressing"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/amp",
        "xep": "XEP-0079",
        "des": "Advanced Message Processing"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/amp#errors",
        "xep": "XEP-0079",
        "des": "Advanced Message Processing"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/bytestreams",
        "xep": "XEP-0065",
        "des": "SOCKS5 Bytestreams"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/caps",
        "xep": "XEP-0115",
        "des": "Entity Capabilities"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/chatstates",
        "xep": "XEP-0085",
        "des": "Chat State Notifications"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/commands",
        "xep": "XEP-0050",
        "des": "Ad-Hoc Commands"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/compress",
        "xep": "XEP-0138",
        "des": "Stream Compression"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/disco#info",
        "xep": "XEP-0030",
        "des": "Service Discovery"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/disco#items",
        "xep": "XEP-0030",
        "des": "Service Discovery"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/feature-neg",
        "xep": "XEP-0020",
        "des": "Feature Negotiation"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/geoloc",
        "xep": "XEP-0080",
        "des": "User Geolocation"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/http-auth",
        "xep": "XEP-0070",
        "des": "Verifying HTTP Requests via XMPP"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/httpbind",
        "xep": "XEP-0124",
        "des": "Bidirectional-streams Over Synchronous HTTP"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/ibb",
        "xep": "XEP-0047",
        "des": "In-Band Bytestreams"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/mood",
        "xep": "XEP-0107",
        "des": "User Mood"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/muc",
        "xep": "XEP-0045",
        "des": "Multi-User Chat"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/muc#admin",
        "xep": "XEP-0045",
        "des": "Multi-User Chat"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/muc#owner",
        "xep": "XEP-0045",
        "des": "Multi-User Chat"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/muc#user",
        "xep": "XEP-0045",
        "des": "Multi-User Chat"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/nick",
        "xep": "XEP-0172",
        "des": "User Nickname"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/offline",
        "xep": "XEP-0013",
        "des": "Flexible Offline Message Retrieval"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/physloc",
        "xep": "XEP-0112",
        "des": "User Physical Location"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/pubsub",
        "xep": "XEP-0060",
        "des": "Publish-Subscribe"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/pubsub#errors",
        "xep": "XEP-0060",
        "des": "Publish-Subscribe"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/pubsub#event",
        "xep": "XEP-0060",
        "des": "Publish-Subscribe"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/pubsub#owner",
        "xep": "XEP-0060",
        "des": "Publish-Subscribe"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/rc",
        "xep": "XEP-0146",
        "des": "Remote Controlling Clients"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/rosterx",
        "xep": "XEP-0144",
        "des": "Roster Item Exchange"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/sipub",
        "xep": "XEP-0137",
        "des": "Publishing SI Requests"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/soap",
        "xep": "XEP-0072",
        "des": "SOAP Over XMPP"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/soap#fault",
        "xep": "XEP-0072",
        "des": "SOAP Over XMPP"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/waitinglist",
        "xep": "XEP-0130",
        "des": "Waiting Lists"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/xhtml-im",
        "xep": "XEP-0071",
        "des": "XHTML-IM"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/xdata-layout",
        "xep": "XEP-0141",
        "des": "Data Forms Layout"
    },
    {
        "support": false,
        "name": "http://jabber.org/protocol/xdata-validate",
        "xep": "XEP-0122",
        "des": "Data Forms Validation"
    },
    {
        "support": false,
        "name": "jabber:client",
        "xep": "RFC 6121",
        "des": "XMPP IM"
    },
    {
        "support": false,
        "name": "jabber:component:accept",
        "xep": "XEP-0114",
        "des": "Existing Component Protocol"
    },
    {
        "support": false,
        "name": "jabber:component:connect",
        "xep": "XEP-0114",
        "des": "Existing Component Protocol"
    },
    {
        "support": false,
        "name": "jabber:iq:auth",
        "xep": "XEP-0078",
        "des": "Non-SASL Authentication"
    },
    {
        "support": false,
        "name": "jabber:iq:gateway",
        "xep": "XEP-0100",
        "des": "Gateway Interaction"
    },
    {
        "support": false,
        "name": "jabber:iq:last",
        "xep": "XEP-0012",
        "des": "Last Activity"
    },
    {
        "support": false,
        "name": "jabber:iq:oob",
        "xep": "XEP-0066",
        "des": "Out of Band Data"
    },
    {
        "support": false,
        "name": "jabber:iq:privacy",
        "xep": "RFC 6121",
        "des": "XMPP IM"
    },
    {
        "support": false,
        "name": "jabber:iq:private",
        "xep": "XEP-0049",
        "des": "Private XML Storage"
    },
    {
        "support": false,
        "name": "jabber:iq:register",
        "xep": "XEP-0077",
        "des": "In-Band Registration"
    },
    {
        "support": false,
        "name": "jabber:iq:roster",
        "xep": "RFC 6121",
        "des": "XMPP IM"
    },
    {
        "support": false,
        "name": "jabber:iq:rpc",
        "xep": "XEP-0009",
        "des": "Jabber-RPC"
    },
    {
        "support": false,
        "name": "jabber:iq:search",
        "xep": "XEP-0055",
        "des": "Jabber Search"
    },
    {
        "support": false,
        "name": "jabber:iq:version",
        "xep": "XEP-0092",
        "des": "Software Version"
    },
    {
        "support": false,
        "name": "jabber:server",
        "xep": "RFC 6121",
        "des": "XMPP IM"
    },
    {
        "support": false,
        "name": "jabber:server:dialback",
        "xep": "XEP-0220",
        "des": "Server Dialback"
    },
    {
        "support": false,
        "name": "jabber:x:conference",
        "xep": "XEP-0249",
        "des": "Direct MUC Invitations"
    },
    {
        "support": false,
        "name": "jabber:x:data",
        "xep": "XEP-0004",
        "des": "Data Forms"
    },
    {
        "support": false,
        "name": "jabber:x:encrypted",
        "xep": "XEP-0027",
        "des": "Current OpenPGP Usage"
    },
    {
        "support": false,
        "name": "jabber:x:oob",
        "xep": "XEP-0066",
        "des": "Out of Band Data"
    },
    {
        "support": false,
        "name": "jabber:x:signed",
        "xep": "XEP-0027",
        "des": "Current OpenPGP Usage"
    },
    {
        "support": false,
        "name": "roster:delimiter",
        "xep": "XEP-0083",
        "des": "Nested Roster Groups"
    },
    {
        "support": false,
        "name": "urn:ietf:params:xml:ns:xmpp-bind",
        "xep": "RFC 6120",
        "des": "XMPP Core"
    },
    {
        "support": false,
        "name": "urn:ietf:params:xml:ns:xmpp-e2e",
        "xep": "RFC 3923",
        "des": "XMPP E2E"
    },
    {
        "support": false,
        "name": "urn:ietf:params:xml:ns:xmpp-sasl",
        "xep": "RFC 6120",
        "des": "XMPP Core"
    },
    {
        "support": false,
        "name": "urn:ietf:params:xml:ns:xmpp-session",
        "xep": "RFC 6121",
        "des": "XMPP IM"
    },
    {
        "support": false,
        "name": "urn:ietf:params:xml:ns:xmpp-stanzas",
        "xep": "RFC 6120",
        "des": "XMPP Core"
    },
    {
        "support": false,
        "name": "urn:ietf:params:xml:ns:xmpp-streams",
        "xep": "RFC 6120",
        "des": "XMPP Core"
    },
    {
        "support": false,
        "name": "urn:ietf:params:xml:ns:xmpp-tls",
        "xep": "RFC 6120",
        "des": "XMPP Core"
    },
    {
        "support": false,
        "name": "urn:xmpp:archive",
        "xep": "XEP-0136",
        "des": "Message Archiving"
    },
    {
        "support": false,
        "name": "urn:xmpp:attention:0",
        "xep": "XEP-0224",
        "des": "Attention"
    },
    {
        "support": false,
        "name": "urn:xmpp:avatar:data",
        "xep": "XEP-0084",
        "des": "User Avatars"
    },
    {
        "support": false,
        "name": "urn:xmpp:avatar:metadata",
        "xep": "XEP-0084",
        "des": "User Avatars"
    },
    {
        "support": false,
        "name": "urn:xmpp:bidi",
        "xep": "XEP-0288",
        "des": "Bidirectional Server-to-Server Connections"
    },
    {
        "support": false,
        "name": "urn:xmpp:bob",
        "xep": "XEP-0231",
        "des": "Bits of Binary"
    },
    {
        "support": false,
        "name": "urn:xmpp:captcha",
        "xep": "XEP-0158",
        "des": "CAPTCHA Forms"
    },
    {
        "support": false,
        "name": "urn:xmpp:delay",
        "xep": "XEP-0203",
        "des": "Delayed Delivery"
    },
    {
        "support": false,
        "name": "urn:xmpp:errors",
        "xep": "XEP-0182",
        "des": "Application-Specific Error Conditions"
    },
    {
        "support": false,
        "name": "urn:xmpp:forward:0",
        "xep": "XEP-0297",
        "des": "Stanza Forwarding"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:1",
        "xep": "XEP-0166",
        "des": "Jingle"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:errors:1",
        "xep": "XEP-0166",
        "des": "Jingle"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:apps:rtp:1",
        "xep": "XEP-0167",
        "des": "Jingle RTP Sessions"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:apps:rtp:errors:1",
        "xep": "XEP-0167",
        "des": "Jingle RTP Sessions"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:apps:rtp:info:1",
        "xep": "XEP-0167",
        "des": "Jingle RTP Sessions"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:apps:rtp:rtcp-fb:0",
        "xep": "XEP-0293",
        "des": "Jingle RTP Feedback Negotiation"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:apps:rtp:rtp-hdrext:0",
        "xep": "XEP-0294",
        "des": "Jingle RTP Header Extensions Negotiation"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:apps:rtp:zrtp:1",
        "xep": "XEP-0262",
        "des": "Use of ZRTP in Jingle RTP Sessions"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:transports:ibb:1",
        "xep": "XEP-0261",
        "des": "Jingle In-Band Bytestreams Transport Method"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:transports:ice-udp:1",
        "xep": "XEP-0176",
        "des": "Jingle ICE-UDP Transport Method"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:transports:raw-udp:1",
        "xep": "XEP-0176",
        "des": "Jingle ICE-UDP Transport Method"
    },
    {
        "support": false,
        "name": "urn:xmpp:jingle:transports:s5b:1",
        "xep": "XEP-0260",
        "des": "Jingle SOCKS5 Bytestreams Transport Method"
    },
    {
        "support": false,
        "name": "urn:xmpp:langtrans",
        "xep": "XEP-0171",
        "des": "Language Translation"
    },
    {
        "support": false,
        "name": "urn:xmpp:langtrans:items",
        "xep": "XEP-0171",
        "des": "Language Translation"
    },
    {
        "support": false,
        "name": "urn:xmpp:media-element",
        "xep": "XEP-0221",
        "des": "Data Forms Media Element"
    },
    {
        "support": false,
        "name": "urn:xmpp:message-correct:0",
        "xep": "XEP-0308",
        "des": "Last Message Correction"
    },
    {
        "support": false,
        "name": "urn:xmpp:pie",
        "xep": "XEP-0227",
        "des": "Portable Import/Export Format for XMPP-IM Servers"
    },
    {
        "support": false,
        "name": "urn:xmpp:ping",
        "xep": "XEP-0199",
        "des": "XMPP Ping"
    },
    {
        "support": false,
        "name": "urn:xmpp:reach:0",
        "xep": "XEP-0152",
        "des": "Reachability Addresses"
    },
    {
        "support": false,
        "name": "urn:xmpp:receipts",
        "xep": "XEP-0184",
        "des": "Message Receipts"
    },
    {
        "support": false,
        "name": "urn:xmpp:rtt:0",
        "xep": "XEP-0301",
        "des": "In-Band Real Time Text"
    },
    {
        "support": false,
        "name": "urn:xmpp:sec-label:0",
        "xep": "XEP-0258",
        "des": "Security Labels in XMPP"
    },
    {
        "support": false,
        "name": "urn:xmpp:sec-label:catalog:2",
        "xep": "XEP-0258",
        "des": "Security Labels in XMPP"
    },
    {
        "support": false,
        "name": "urn:xmpp:sec-label:ess:0",
        "xep": "XEP-0258",
        "des": "Security Labels in XMPP"
    },
    {
        "support": false,
        "name": "urn:xmpp:sm:3",
        "xep": "XEP-0198",
        "des": "Stream Management"
    },
    {
        "support": false,
        "name": "urn:xmpp:ssn",
        "xep": "XEP-0155",
        "des": "Stanza Session Negotiation"
    },
    {
        "support": false,
        "name": "urn:xmpp:time",
        "xep": "XEP-0202",
        "des": "Entity Time"
    },
    {
        "support": false,
        "name": "urn:xmpp:xbosh",
        "xep": "XEP-0206",
        "des": "XMPP Over BOSH"
    },
    {
        "support": false,
        "name": "vcard-temp",
        "xep": "XEP-0054",
        "des": "vcard-temp"
    },
    {
        "support": false,
        "name": "vcard-temp:x:update",
        "xep": "XEP-0153",
        "des": "vCard-Based Avatars"
    }
]
END
            , true);
        if (!$this->isSupported(true)) {
            $this->connection->getServerListener()->onError(['error', 'XEP0030 not supported by server which is minimum XMPP server requirement']);
            $this->connection->logout();
        }
        return $this::$instance;
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return 'XEP-0030';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Service Discovery';
    }

    /**
     * @return string[]
     */
    public function ns(): array
    {
        return [
            'http://jabber.org/protocol/disco#info',
            'http://jabber.org/protocol/disco#items',
        ];
    }


    /**
     * @param bool $reload
     * @return bool
     */
    function isSupported(bool $reload = false): bool
    {
        if ($reload || !isset($this->isSupported)) {
            $xml = "<iq type='get' id='" . md5(uniqid(rand(), true)) .
                "'><query xmlns='http://jabber.org/protocol/disco#info'></query></iq>";
            $this->connection->write($xml);
            XMPP_XML::parse($this->connection->getResponse());
            array_walk($this->XMPP_NS,
                function (&$item) {
                    $item['support'] = XMPP_XML::hasNS($item['name']);
                }
            );
            $this->isSupported = (XMPP_XML::hasNS($this->ns()[0]) && XMPP_XML::hasNS($this->ns()[1]));
        }
        return $this->isSupported;
    }


    public function checkNS(array $ns, bool $recheck=false, string $xml=''): bool
    {
        if ($recheck) {
            $this->connection->write($xml);
            XMPP_XML::parse($this->connection->getResponse());
            array_walk($this->XMPP_NS,
                function (&$item) {
                    $item['support'] = XMPP_XML::hasNS($item['name']);
                }
            );
        }
        $res=[];
        foreach ($ns as $value){
            $search = array_search($value, array_column($this->XMPP_NS, 'name'));
            if( $search !== false) {
                $res[] = $this->XMPP_NS[$search]['support'];
            }
        }
        return (count($ns) == count($res) && !in_array(false, $res));
    }

    public function getGeneral(bool $reload = false): array
    {
        if ($reload || !isset($this->generalSupport)) {

            $from = "from='" . $this->connection->getJid()."'";
            $to = "to='" . $this->connection->getHost() . "'";
            $xml = "<iq $from $to type='get' id='" . md5(uniqid(rand(), true)) .
                "'><query xmlns='http://jabber.org/protocol/disco#info'></query></iq>";
            $this->connection->write($xml);
            XMPP_XML::parse($this->connection->getResponse());
            array_walk($this->XMPP_NS,
                function (&$item) {
                    $item['support'] = XMPP_XML::hasNS($item['name']);
                }
            );
            foreach (XMPP_XML::$input->getElementsByTagName('feature') as $element) {
                $this->generalSupport[$element->getAttribute('var')] = true;
            }
        }
        return $this->generalSupport;
    }

    public function onBeforeWrite(string $xml) {}

    public function onAfterWrite(string $xml) {}

    public function onRead(string $response) {}
    public function checkError(string $responseXML): bool
    {
        return false;
    }

    public function getLastError(): array
    {
        return [];
    }
}
