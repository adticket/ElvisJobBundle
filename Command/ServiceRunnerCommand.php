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

/**
 * @author Markus Tacker <m@coderbyheart.de>
 * @package AdTicket:Sf2BundleOS:Elvis:JobBundle
 * @category Command
 */

namespace Adticket\Sf2BundleOS\Elvis\JobBundle\Command;

use Adticket\Sf2BundleOS\Elvis\JobBundle\Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs the worker
 */
class ServiceRunnerCommand extends ContainerAwareCommand
{
    const NAME = 'ServiceRunner';

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this
                ->setName('jobbundle:servicerunner')
                ->setDescription('Runs the service runner worker');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $gworker = new \GearmanWorker();
        $gworker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        $server = $this->getContainer()->getParameter('adticket_elvis_job.server');
        $gworker->addServer($this->getHostname(), $this->getPort());
        $gworker->addFunction(self::NAME, array($this, 'runService'));

        while (@$gworker->work() ||
               $gworker->returnCode() == GEARMAN_IO_WAIT ||
               $gworker->returnCode() == GEARMAN_NO_JOBS)
        {
            if ($gworker->returnCode() == GEARMAN_SUCCESS)
                continue;

            $output->writeln("Waiting for next job...");
            if (!@$gworker->wait()) {
                if ($gworker->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
                    $output->writeln("Disconnected from server ...");
                    # We are not connected to any servers, so wait a bit before
                    # trying to reconnect.
                    sleep(5);
                    continue;
                }
                break;
            }
        }

        $output->writeln("Worker Error: " . $gworker->error());
    }

    public function runService(\GearmanJob $job)
    {
        $this->output->writeln("Running " . $job->functionName());
        $wl = unserialize($job->workload());
        foreach(array('service', 'options') as $k) {
            if (!array_key_exists($k, $wl)) throw new Exception(sprintf('Missing property %s in workload', $k));
        }
        $this->getContainer()->get('adticket_elvis_job.optionschecker')->checkJobOptions($wl['service'], $wl['options']);
        $service = $this->getContainer()->get($wl['service']);
        foreach($wl['options'] as $k => $v) {
            $service->$k = $v;
        }
        $service->execute($this->getContainer(), $job);
    }

    public function getHostname()
    {
        $config = $this->getContainer()->getParameter('adticket_elvis_job.server');
        return $config['hostname'];
    }

    public function getPort()
    {
        $config = $this->getContainer()->getParameter('adticket_elvis_job.server');
        return $config['port'];
    }
}