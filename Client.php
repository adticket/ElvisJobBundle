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
 */

namespace Adticket\Sf2BundleOS\Elvis\JobBundle;

use Adticket\Sf2BundleOS\Elvis\JobBundle\Annotation;
use Adticket\Sf2BundleOS\Elvis\JobBundle\Exception;
use Adticket\Sf2BundleOS\Elvis\JobBundle\Command;
use Adticket\Sf2BundleOS\Elvis\JobBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Adds jobs to the queue
 */
class Client extends ContainerAware
{
    /**
     * @var \GearmanClient
     */
    private $gmclient;
    
    public function __construct(ContainerInterface $container = null)
    {
        $this->setContainer($container);
        $this->gmclient = new \GearmanClient();
        $this->gmclient->addServer($this->getHostname(), $this->getPort());
    }

    /**
     * Adds a new job to the queue. Returns the job id.
     * 
     * @param string $serviceId service id of the job to run
     * @param array|null $options for the job
     */
    public function addJob($serviceId, array $options = null)
    {
        $this->container->get('adticket_elvis_job.optionschecker')->checkJobOptions($serviceId, $options);
        $handle = @$this->gmclient->doBackground(Command\ServiceRunnerCommand::NAME, serialize(array('service' => $serviceId, 'options' => $options)));
        if ($this->gmclient->returnCode() != GEARMAN_SUCCESS) {
            throw new Exception(sprintf('Failed to add job to server\'s %s:%d queue.', $this->getHostname(), $this->getPort()));
        }
    }
    
    public function getHostname()
    {
        $config = $this->container->getParameter(Configuration::ROOT);
        return $config['hostname'];
    }

    public function getPort()
    {
        $config = $this->container->getParameter(Configuration::ROOT);
        return $config['port'];
    }
}
