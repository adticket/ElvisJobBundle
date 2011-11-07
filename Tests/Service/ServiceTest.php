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

namespace Adticket\Sf2BundleOS\Elvis\JobBundle\Tests\Controller;

use Adticket\Sf2BundleOS\Elvis\JobBundle\Command\ServiceRunnerCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console;

/**
 * @todo FIXME Actually, run this test. See http://kriswallsmith.net/post/1338263070/how-to-test-a-symfony2-bundle
 */
class ServiceTest extends WebTestCase
{
    private $container;

    /**
     * @var \GearmanJob 
     */
    private $addResult;

    protected function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
    }

    public function testAdd()
    {
        $settings = $this->container->getParameter('adticket_elvis_job.server');
        $settings['port'] = 13666;
        $settings['hostname'] = 'localhost';
        $this->container->setParameter('adticket_elvis_job.server', $settings);

        $client = $this->getClient();
        $client->setPort($testport);
        $client->addJob('adticket_elvis_job.job.add', array('a' => 1, 'b' => 2));

        $pid = pcntl_fork();
        if ($pid) {
            sleep(1);
            echo "Beende Worker" . PHP_EOL;
            posix_kill($pid, 9);

            // Hole Ergebnis
            $gworker = new \GearmanWorker();
            $gworker->addServer($settings['hostname'], $settings['port']);
            $gworker->addFunction(ServiceRunnerCommand::NAME, array($this, 'addResult'));
            $gworker->work();
            print_r($this->addResult->workload());

        } else {
            $worker = new ServiceRunnerCommand();
            $worker->setPort($testport);
            $worker->setContainer($this->container);
            $worker->run(new Console\Input\ArgvInput(array()), new Console\Output\ConsoleOutput());
        }
    }

    public function addResult(\GearmanJob $job)
    {
        $this->addResult = $job;
    }

    /**
     * @return \Adticket\Sf2BundleOS\Elvis\JobBundle\Client
     */
    public function getClient()
    {
        return $this->container->get('adticket_elvis_job.client');
    }

    /**
     * Shuts the kernel down if it was used in the test.
     */
    protected function tearDown()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
    }
}
