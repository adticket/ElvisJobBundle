<?php

//  +--------------------------------------------------+
//  | Copyright (c) AD ticket GmbH                     |
//  | All rights reserved.                             |
//  +--------------------------------------------------+
//  | AD ticket GmbH                                   |
//  | Kaiserstraße 69                                  |
//  | D-60329 Frankfurt am Main                        |
//  |                                                  |
//  | phone: +49 (0)69 407 662 0                       |
//  | fax:   +49 (0)69 407 662 50                      |
//  | mail:  github@adticket.de                        |
//  | web:   www.ADticket.de                           |
//  +--------------------------------------------------+
//  | This file is part of ElvisJobBundle.             |
//  | https://github.com/adticket/ElvisJobBundle       |
//  +--------------------------------------------------+
//  | ElvisJobBundle is free software: you can         |
//  | redistribute it and/or modify it under the terms |
//  | of the GNU General Public License as published   |
//  | by the Free Software Foundation, either version  |
//  | 3 of the License, or (at your option) any later  |
//  | version.                                         |
//  |                                                  |
//  | In addition you are required to retain all       |
//  | author attributions provided in this software    |
//  | and attribute all modifications made by you      |
//  | clearly and in an appropriate way.               |
//  |                                                  |
//  | This software is distributed in the hope that    |
//  | it will be useful, but WITHOUT ANY WARRANTY;     |
//  | without even the implied warranty of             |
//  | MERCHANTABILITY or FITNESS FOR A PARTICULAR      |
//  | PURPOSE.  See the GNU General Public License for |
//  | more details.                                    |
//  |                                                  |
//  | You should have received a copy of the GNU       |
//  | General Public License along with this software. |
//  | If not, see <http://www.gnu.org/licenses/>.      |
//  +--------------------------------------------------+

namespace Adticket\Elvis\JobBundle\Tests\Controller;

use Adticket\Elvis\JobBundle\Job;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @todo FIXME Actually, run this test. See http://kriswallsmith.net/post/1338263070/how-to-test-a-symfony2-bundle
 */
class ServiceTest extends WebTestCase
{
    private $client;

    public function testAdd()
    {
        $this->client = static::createClient();
        $server = $this->getServer();
        $server->addJob('adticket_elvis_job.job.add', array('a' => 1, 'b' => 2));
    }

    /**
     * @return \Adticket\Elvis\JobBundle\Server
     */
    public function getServer()
    {
        return $this->client->get('adticket_elvis_job.server');
    }
}
