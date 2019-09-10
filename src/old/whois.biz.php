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

if (!\defined('__BIZ_HANDLER__')) {
    \define('__BIZ_HANDLER__', 1);
}

require_once 'whois.parser.php';

class biz_handler
{
    public function parse($data_str, $query)
    {
        $r = [];
        $r['regrinfo'] = generic_parser_b($data_str['rawdata'], [], '-md--y');
        $r['regyinfo'] = [
            'referrer'  => 'http://www.neulevel.biz',
            'registrar' => 'NEULEVEL',
        ];

        return $r;
    }
}
