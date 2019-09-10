<?php

declare(strict_types=1);

/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under GNU General Public License v2.
 *
 * @copyright 1999-2005 easyDNS Technologies Inc. & Mark Jeftovic
 * @copyright xxxx-xxxx Maintained by David Saez
 * @copyright 2014-2019 Dmitry Lukashin
 * @copyright 2019-2020 Niko Granö (https://granö.fi)
 *
 */

if (!\defined('__NETWORKSOLUTIONS_HANDLER__')) {
    \define('__NETWORKSOLUTIONS_HANDLER__', 1);
}

require_once 'whois.parser.php';

class networksolutions_handler
{
    public function parse($data_str, $query)
    {
        $items = [
            'owner'           => 'Registrant:',
            'admin'           => 'Administrative Contact',
            'tech'            => 'Technical Contact',
            'domain.name'     => 'Domain Name:',
            'domain.nserver.' => 'Domain servers in listed order:',
            'domain.created'  => 'Record created on',
            'domain.expires'  => 'Record expires on',
        ];

        return easy_parser($data_str, $items, 'dmy', [], true, true);
    }
}
