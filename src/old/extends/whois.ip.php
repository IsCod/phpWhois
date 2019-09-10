<?php

declare(strict_types=1);

/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under GNU General Public License v2.
 *
 * @copyright 1999-2005 easyDNS Technologies Inc. & Mark Jeftovic
 * @copyright 2005-2014 David Saez
 * @copyright 2014-2019 Dmitry Lukashin
 * @copyright 2019-2020 Niko Granö (https://granö.fi)
 *
 */

if (!\defined('__IP_HANDLER__')) {
    \define('__IP_HANDLER__', 1);
}

use phpWhois\WhoisClient;

class ip_handler extends WhoisClient
{
    /** @var Deep whois? */
    public $deepWhois = true;
    public $REGISTRARS = [
        'European Regional Internet Registry/RIPE NCC'              => 'whois.ripe.net',
        'RIPE Network Coordination Centre'                          => 'whois.ripe.net',
        'Asia Pacific Network Information	Center'                   => 'whois.apnic.net',
        'Asia Pacific Network Information Centre'                   => 'whois.apnic.net',
        'Latin American and Caribbean IP address Regional Registry' => 'whois.lacnic.net',
        'African Network Information Center'                        => 'whois.afrinic.net',
    ];
    public $HANDLERS = [
        'whois.krnic.net'   => 'krnic',
        'whois.apnic.net'   => 'apnic',
        'whois.ripe.net'    => 'ripe',
        'whois.arin.net'    => 'arin',
        'whois.lacnic.net'  => 'lacnic',
        'whois.afrinic.net' => 'afrinic',
    ];
    public $more_data = []; // More queries to get more accurated data
    public $done = [];

    public function parse($data, $query)
    {
        $result = [
            'regrinfo' => [],
            'regyinfo' => [],
            'rawdata'  => [],
        ];
        $result['regyinfo']['registrar'] = 'American Registry for Internet Numbers (ARIN)';

        if (false === \mb_strpos($query, '.')) {
            $result['regyinfo']['type'] = 'AS';
        } else {
            $result['regyinfo']['type'] = 'ip';
        }

        if (!$this->deepWhois) {
            return null;
        }

        $this->query = [];
        $this->query['server'] = 'whois.arin.net';
        $this->query['query'] = $query;

        $rawdata = $data['rawdata'];

        if (empty($rawdata)) {
            return $result;
        }

        $presults = [];
        $presults[] = $rawdata;
        $ip = \ip2long($query);
        $done = [];

        while (\count($presults) > 0) {
            $rwdata = \array_shift($presults);
            $found = false;

            foreach ($rwdata as $line) {
                if (!\strncmp($line, 'American Registry for Internet Numbers', 38)) {
                    continue;
                }

                $p = \mb_strpos($line, '(NETBLK-');

                if (false === $p) {
                    $p = \mb_strpos($line, '(NET-');
                }

                if (false !== $p) {
                    $net = \strtok(\mb_substr($line, $p + 1), ') ');
                    list($low, $high) = \explode('-', \str_replace(' ', '', \mb_substr($line, $p + \mb_strlen($net) + 3)));

                    if (!isset($done[$net]) && $ip >= \ip2long($low) && $ip <= \ip2long($high)) {
                        $owner = \mb_substr($line, 0, $p - 1);

                        if (!empty($this->REGISTRARS['owner'])) {
                            $this->handle_rwhois($this->REGISTRARS['owner'], $query);
                            break 2;
                        }
                        $this->query['args'] = 'n '.$net;
                        $presults[] = $this->getRawData($net);
                        $done[$net] = 1;
                    }
                    $found = true;
                }
            }

            if (!$found) {
                $this->query['file'] = 'whois.ip.arin.php';
                $this->query['handler'] = 'arin';
                $result = $this->parse_results($result, $rwdata, $query, true);
            }
        }

        unset($this->query['args']);

        while (\count($this->more_data) > 0) {
            $srv_data = \array_shift($this->more_data);
            $this->query['server'] = $srv_data['server'];
            unset($this->query['handler']);
            // Use original query
            $rwdata = $this->getRawData($srv_data['query']);

            if (!empty($rwdata)) {
                if (!empty($srv_data['handler'])) {
                    $this->query['handler'] = $srv_data['handler'];

                    if (!empty($srv_data['file'])) {
                        $this->query['file'] = $srv_data['file'];
                    } else {
                        $this->query['file'] = 'whois.'.$this->query['handler'].'.php';
                    }
                }

                $result = $this->parse_results($result, $rwdata, $query, $srv_data['reset']);
                $result = $this->setWhoisInfo($result);
                $reset = false;
            }
        }

        // Normalize nameserver fields

        if (isset($result['regrinfo']['network']['nserver'])) {
            if (!\is_array($result['regrinfo']['network']['nserver'])) {
                unset($result['regrinfo']['network']['nserver']);
            } else {
                $result['regrinfo']['network']['nserver'] = $this->fixNameServer($result['regrinfo']['network']['nserver']);
            }
        }

        return $result;
    }

    //-----------------------------------------------------------------

    public function parse_results($result, $rwdata, $query, $reset)
    {
        $rwres = $this->process($rwdata);

        if ('AS' === $result['regyinfo']['type'] && !empty($rwres['regrinfo']['network'])) {
            $rwres['regrinfo']['AS'] = $rwres['regrinfo']['network'];
            unset($rwres['regrinfo']['network']);
        }

        if ($reset) {
            $result['regrinfo'] = $rwres['regrinfo'];
            $result['rawdata'] = $rwdata;
        } else {
            $result['rawdata'][] = '';

            foreach ($rwdata as $line) {
                $result['rawdata'][] = $line;
            }

            foreach ($rwres['regrinfo'] as $key => $data) {
                $result = $this->join_result($result, $key, $rwres);
            }
        }

        if ($this->deepWhois) {
            if (isset($rwres['regrinfo']['rwhois'])) {
                $this->handle_rwhois($rwres['regrinfo']['rwhois'], $query);
                unset($result['regrinfo']['rwhois']);
            } elseif (!@empty($rwres['regrinfo']['owner']['organization'])) {
                switch ($rwres['regrinfo']['owner']['organization']) {
                    case 'KRNIC':
                        $this->handle_rwhois('whois.krnic.net', $query);
                        break;

                    case 'African Network Information Center':
                        $this->handle_rwhois('whois.afrinic.net', $query);
                        break;
                }
            }
        }

        if (!empty($rwres['regyinfo'])) {
            $result['regyinfo'] = \array_merge($result['regyinfo'], $rwres['regyinfo']);
        }

        return $result;
    }

    //-----------------------------------------------------------------

    public function handle_rwhois($server, $query): void
    {
        // Avoid querying the same server twice

        $parts = \parse_url($server);

        if (empty($parts['host'])) {
            $host = $parts['path'];
        } else {
            $host = $parts['host'];
        }

        if (\array_key_exists($host, $this->done)) {
            return;
        }

        $q = [
            'query'  => $query,
            'server' => $server,
        ];

        if (isset($this->HANDLERS[$host])) {
            $q['handler'] = $this->HANDLERS[$host];
            $q['file'] = \sprintf('whois.ip.%s.php', $q['handler']);
            $q['reset'] = true;
        } else {
            $q['handler'] = 'rwhois';
            $q['reset'] = false;
            unset($q['file']);
        }

        $this->more_data[] = $q;
        $this->done[$host] = 1;
    }

    //-----------------------------------------------------------------

    public function join_result($result, $key, $newres)
    {
        if (isset($result['regrinfo'][$key]) && !\array_key_exists(0, $result['regrinfo'][$key])) {
            $r = $result['regrinfo'][$key];
            $result['regrinfo'][$key] = [$r];
        }

        $result['regrinfo'][$key][] = $newres['regrinfo'][$key];

        return $result;
    }
}
