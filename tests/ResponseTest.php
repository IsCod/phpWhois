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

use phpWhois\Query;
use phpWhois\Response;

class ResponseTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor(): void
    {
        $response = new Response(new Query('www.GOOGLE.com'));
        $this->assertInstanceOf(Query::class, $response->getQuery());
    }
}
