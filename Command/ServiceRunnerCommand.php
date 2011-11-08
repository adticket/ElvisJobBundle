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
use Adticket\Sf2BundleOS\Elvis\JobBundle\DependencyInjection\Configuration;
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

    /**
     * @var boolean
     */
    private $oneWork = false;

    /**
     * @var bool
     */
    private $verbose = false;

    /**
     * @var boolean
     */
    private $running = true;
    
    protected function configure()
    {
        $this
            ->setName('jobbundle:servicerunner')
            ->setDescription('Runs the service runner worker')
            ->setDefinition(array(
                new InputArgument('verbose', InputArgument::OPTIONAL, 'Turn verbose messages on', $this->verbose)
            ));

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->verbose = $input->getArgument('verbose');
        $this->output = $output;
        $gworker = new \GearmanWorker();
        $gworker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        $gworker->addServer($this->getHostname(), $this->getPort());
        $gworker->addFunction(self::NAME, array($this, 'runService'));

        while (@$gworker->work() ||
                $gworker->returnCode() == GEARMAN_IO_WAIT ||
                $gworker->returnCode() == GEARMAN_NO_JOBS)
        {
            if ($gworker->returnCode() == GEARMAN_SUCCESS) {
                continue;
            }
            if (!$this->running) {
                if ($this->verbose) $output->writeln("Terminating ...");
                return;
            }
            if (!@$gworker->wait()) {
                if ($gworker->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
                    if ($input->getArgument('verbose')) $output->writeln("Disconnected from server ...");
                    sleep(5);
                    continue;
                }
                break;
            }
        }

        $output->writeln("Worker Error: " . $gworker->error());
    }

    /**
     * Runs a service requested by the job, Callback for {@link \GearmanClient GearmanClient}.
     *
     * @throws \Adticket\Sf2BundleOS\Elvis\JobBundle\Exception
     * @param \GearmanJob $job
     * @return void
     */
    public function runService(\GearmanJob $job)
    {
        if ($this->verbose) $this->output->writeln("Running " . $job->functionName());
        $wl = unserialize($job->workload());
        foreach (array('service', 'options') as $k) {
            if (!array_key_exists($k, $wl)) throw new Exception(sprintf('Missing property %s in workload', $k));
        }
        $this->getContainer()->get('adticket_elvis_job.optionschecker')->checkJobOptions($wl['service'], $wl['options']);
        $service = clone $this->getContainer()->get($wl['service']);
        foreach ($wl['options'] as $k => $v) {
            $service->$k = $v;
        }
        if ($this->verbose) $this->output->write("Executing " . $wl['service'] . ' ... ');
        $service->execute($this->getContainer(), $job);
        if ($this->verbose) $this->output->writeln("Done.");
        if ($this->getOneWork()) $this->running = false;
    }

    public function getHostname()
    {
        $config = $this->getContainer()->getParameter(Configuration::ROOT);
        return $config['hostname'];
    }

    public function getPort()
    {
        $config = $this->getContainer()->getParameter(Configuration::ROOT);
        return $config['port'];
    }

    /**
     * Terminate after on completed task
     *
     * @param boolean $oneWork
     * @return void
     */
    public function setOneWork($oneWork)
    {
        $this->oneWork = $oneWork;
    }

    public function getOneWork()
    {
        return $this->oneWork;
    }
}