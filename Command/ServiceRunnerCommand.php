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
 * @author Dennis Oehme <oehme@gardenofconcepts.com>
 * @package AdTicket:Sf2BundleOS:Elvis:JobBundle
 * @category Command
 */

namespace Adticket\Sf2BundleOS\Elvis\JobBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;

use Adticket\Sf2BundleOS\Elvis\JobBundle\Job;
use Adticket\Sf2BundleOS\Elvis\JobBundle\Exception;
use Adticket\Sf2BundleOS\Elvis\JobBundle\DependencyInjection\Configuration;

/**
 * Runs the worker
 */
class ServiceRunnerCommand extends ContainerAwareCommand
{
    const NAME = 'ServiceRunner';

    /**
     * @var bool
     */
    private $fork = true;

    /**
     * @var int
     */
    private $timeout = 120;

    /**
     * @var int
     */
    private $nice = 19;

    /**
     * @var boolean
     */
    private $running = true;

    public $childs = array();

    public $jobs = array();

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('jobbundle:servicerunner')
            ->setDescription('Runs the service runner worker')
            ->setDefinition($this->getInputDefinition());
    }

    /**
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    public function getInputDefinition()
    {
        return new InputDefinition(array(
            new InputOption('fork', 'f', InputOption::VALUE_OPTIONAL, 'Fork worker jobs as childs', $this->fork),
            new InputOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Timeout for job execution before job will killed', $this->timeout),
            new InputOption('nice', null, InputOption::VALUE_OPTIONAL, 'Nice level for child execution', $this->nice)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return parent::getContainer();
    }

    /**
     * @param string $name
     * @return object
     */
    public function getService($name)
    {
        return clone $this->getContainer()->get($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $worker = new \GearmanWorker();
            $worker->addServer($this->getHostname(), $this->getPort());
            $worker->addFunction(self::NAME, function(\GearmanJob $job) {

            });
            $worker->work();
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Connection to gearmand failed: %s: %s</error>', get_class($e), $e->getMessage()));

            return -1;
        }

        // prevent for lost connections
        while (true) {
            try {
                $output->writeln(sprintf('<info>Start worker process (%s)...</info>', getmypid()), OutputInterface::VERBOSITY_VERBOSE);
                $this->runWorker($input, $output);
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>Working error: %s: %s</error>', get_class($e), $e->getMessage()));
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param boolean $fork
     */
    protected function runWorker(InputInterface $input, OutputInterface $output)
    {
        $self = $this;
        $worker = new \GearmanWorker();
        $worker->addServer($this->getHostname(), $this->getPort());
        //$worker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        $worker->addFunction(self::NAME, function(\GearmanJob $job) use ($self, $output) {
            $data = unserialize($job->workload());

            $job->sendComplete(1);

            $job = new Job();
            $job->setService($data['service']);
            $job->setData($data['options']);
            $job->setStatus(Job::STATUS_RUNNING);

            return $self->forkProcess($job, $output);
        });

        while (true) {
            $worker->work();

            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                $output->writeln("<error>Worker Error: " . $worker->error() . '</error>');
                break;
            }
        }
    }

    /**
     * @param \Adticket\Sf2BundleOS\Elvis\JobBundle\Job $job
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function forkProcess(Job $job, OutputInterface $output)
    {
        declare(ticks = 1);

        $pid = pcntl_fork();
        $self = $this;

        if ($pid == -1) {
            throw new Exception('Failed to fork');
        } else if ($pid) {
            $output->writeln(sprintf('<info>Main process (%s) waiting...</info>', getmypid()), OutputInterface::VERBOSITY_VERBOSE);
            pcntl_wait($status);
            /*if (pcntl_wifexited($status)) {
                $output->writeln(sprintf('<info>%s is done.</info>', $job->getService()), OutputInterface::VERBOSITY_VERBOSE);
            } else {
                $output->writeln(sprintf('<error>Child execution of service %s failed.</error>', $job->getService()), OutputInterface::VERBOSITY_QUIET);
            }*/
        } else {
            $this->callChild($job, $output, $self);
        }
    }

    /**
     * @param $job
     * @param $output
     * @param $self
     */
    public function callChild(Job $job, OutputInterface $output, ServiceRunnerCommand $self)
    {
        $pid = posix_getpid();

        $self->jobs[$pid] = $job;

        pcntl_setpriority($self->getNiceLevel());
        pcntl_alarm($self->getTimeout());

        // handle timeout process
        pcntl_signal(SIGALRM, function() use ($pid, $output, $self) {
            $job = $self->jobs[$pid];

            $output->writeln(sprintf('<error>Child execution of service %s (%s) failed: time out, process killed</error>', $job->getService(), $pid));

            if (array_key_exists($pid, $self->jobs)) {
                $self->jobs[$pid]->setStatus(Job::STATUS_TIMEOUT);
                $self->jobs[$pid]->setMessage('Timeout, job killed!');
            }

            posix_kill(getmypid(), SIGKILL);
        }, true);

        // handles success child exit
        pcntl_signal(SIGINT, function() use ($pid, $output, $self) {
            $job = $self->jobs[$pid];

            $output->writeln(sprintf('<info>Child execution of service %s (%s) is is successfully done</info>', $job->getService(), $pid), OutputInterface::VERBOSITY_VERBOSE);

            unset($self->jobs[$pid]);

            posix_kill(getmypid(), SIGKILL);
        }, true);

        $output->writeln(sprintf('<info>Child execution of service %s (%s) started...</info>', $job->getService(), $pid), OutputInterface::VERBOSITY_VERBOSE);

        $service = $self->getService($job->getService());

        foreach ($job->getData() as $k => $v) {
            $service->$k = $v;
        }

        $service->execute($self->getContainer(), $job, $job->getData());

        posix_kill(getmypid(), SIGINT);
    }

    /**
     * Runs a service requested by the job, Callback for {@link \GearmanClient GearmanClient}.
     *
     * @throws \Adticket\Sf2BundleOS\Elvis\JobBundle\Exception
     * @param \GearmanJob $job
     * @return void
     */
    /*public function runService2(\GearmanJob $job, $output, $fork)
    {
        $fork = true;
        $output->writeln('Running ' . $job->functionName(), OutputInterface::VERBOSITY_VERBOSE);
        $wl = unserialize($job->workload());
        foreach (array('service', 'options') as $k) {
            if (!array_key_exists($k, $wl)) throw new Exception(sprintf('Missing property %s in workload', $k));
        }
        $this->getContainer()->get('adticket_elvis_job.optionschecker')->checkJobOptions($wl['service'], $wl['options']);
        $service = clone $this->getContainer()->get($wl['service']);

        $output->write('Executing ' . $wl['service'] . ' ... ', OutputInterface::VERBOSITY_VERBOSE);

        if ($fork) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new Exception('Failed to fork');
            } else if ($pid) {
                pcntl_wait($status);
                if (pcntl_wifexited($status)) {
                    $output->writeln(sprintf('%s is done.', $wl['service']), OutputInterface::VERBOSITY_VERBOSE);
                } else {
                    $output->writeln(sprintf('Child execution of service %s failed.', $wl['service']), OutputInterface::VERBOSITY_QUIET);
                }
                if ($this->getOneWork()) $this->running = false;
            } else {
                $service->execute($this->getContainer(), $job);
            }
        } else {
            try {
                $service->execute($this->getContainer(), $job);
                $output->writeln(sprintf('%s is done.', $wl['service']), OutputInterface::VERBOSITY_VERBOSE);
            } catch (\Exception $e) {
                $output->writeln(sprintf('Child execution of service %s failed: %d / %s', $wl['service'], $e->getCode(), $e->getMessage()), OutputInterface::VERBOSITY_QUIET);
            }
            if ($this->getOneWork()) $this->running = false;
        }

        return true;
    }*/

    /**
     * @return string
     */
    public function getHostname()
    {
        $config = $this->getContainer()->getParameter(Configuration::ROOT);

        return $config['hostname'];
    }

    /**
     * @return int
     */
    public function getPort()
    {
        $config = $this->getContainer()->getParameter(Configuration::ROOT);

        return $config['port'];
    }

    /**
     * @param boolean $fork
     */
    public function setFork($fork)
    {
        $this->fork = $fork;
    }

    /**
     * @return boolean
     */
    public function getFork()
    {
        return $this->fork;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return int
     */
    public function getNiceLevel()
    {
        return $this->nice;
    }
}
