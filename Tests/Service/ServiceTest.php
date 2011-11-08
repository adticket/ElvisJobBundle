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
use Adticket\Sf2BundleOS\Elvis\JobBundle\DependencyInjection\Configuration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo FIXME Actually, run this test. See http://kriswallsmith.net/post/1338263070/how-to-test-a-symfony2-bundle
 */
class ServiceTest extends WebTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \GearmanJob 
     */
    private $addResult;

    /**
     * @var int
     */
    private $gearmanpid;
    
    /**
     * @var string
     */
    private $pidfile;

    protected function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = self::$kernel->getContainer();

        // Starte gearman
        $settings = $this->container->getParameter(Configuration::ROOT);
        $this->pidfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jobbundletestgearman.pid';
        $logfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jobbundletestgearman.log';
        if (is_file($this->pidfile)) {
            // Check if already running and kill old gearman
            $oldpid = (int)file_get_contents($this->pidfile);
            if (posix_kill($oldpid, SIGUSR1)) {
                if (!posix_kill($oldpid, SIGTERM)) {
                    $this->markTestSkipped(sprintf('Failed get kill old gearmand from PID file %s', $this->pidfile) . PHP_EOL);
                }
            } else {
                // Remove stale PID file
                unlink($this->pidfile);
            }
        }

        // Start new gearmand
        exec(sprintf('`which bash` -c \'`which gearmand` -d -L %s -p %d -P %s -vvvvvvv -l %s \' > /dev/null &', $settings['hostname'], $settings['port'], $this->pidfile, $logfile), $output, $return);
        if ($return !== 0) {
            $this->markTestSkipped('Failed to start gearmand: ' . PHP_EOL . join(PHP_EOL, $output));
        } else {
            $i = 0;
            while(!is_file($this->pidfile) && $i++ < 10) {
                sleep(1);
            }
            if (!is_file($this->pidfile)) {
                $this->markTestSkipped('Failed get gearmand PID' . PHP_EOL);
            }
            $this->gearmanpid = (int)file_get_contents($this->pidfile);
        }
    }

    public function testAdd()
    {
        $client = $this->getClient();
        $client->addJob('adticket_elvis_job.job.add', array('a' => 1, 'b' => 2));

        $worker = new ServiceRunnerCommand();
        $worker->setContainer($this->container);
        $worker->setOneWork(true);
        $worker->run(new Console\Input\ArgvInput(array()), new Console\Output\ConsoleOutput());
        $this->assertEquals($_SERVER['ADDJOB_RESULT'], 3);
    }

    private function addResult(\GearmanJob $job)
    {
        $this->addResult = $job;
    }

    /**
     * @return \Adticket\Sf2BundleOS\Elvis\JobBundle\Client
     */
    private function getClient()
    {
        return $this->container->get('adticket_elvis_job.client');
    }

    /**
     * Shuts the kernel down if it was used in the test.
     */
    protected function tearDown()
    {
        // Kill gearmand
        if ($this->gearmanpid !== null) {
            posix_kill($this->gearmanpid, SIGTERM);
        }

        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
    }
}
