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

use Adticket\Sf2BundleOS\Elvis\JobBundle\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Checks job options
 */
class JobOptionsChecker extends ContainerAware
{
    public function __construct(ContainerInterface $container = null)
    {
        $this->setContainer($container);
    }
    
    /**
     * Überprüft die Optionen für einen Job
     *
     * @param string Service-ID
     * @param array die Optionen
     */
    public function checkJobOptions($serviceId, Array $options)
    {
        $job = $this->container->get($serviceId);
        $jobOptions = $this->getJobOptions($job);
        $unmatchedOptions = array_diff($jobOptions, array_keys($options));
        if (!empty($unmatchedOptions)) throw new Exception(sprintf('Unknown options provided for job "%s": %s', $serviceId, join(', ', $unmatchedOptions)));
    }

    /**
     * Liefert die mit @Annotation\JobOption markierten Properties einer Klasse
     * 
     * @param object $job
     * @return string[]
     */
    protected function getJobOptions($job)
    {
        $annotationReader = $this->container->get('annotation_reader');
        $refClass = new \ReflectionClass(is_object($job) ? get_class($job) : $job);
        $jobOptions = array();
        foreach($refClass->getProperties() as $property) {
            foreach($annotationReader->getPropertyAnnotations($property) as $propertyAnnotation) {
                if ($propertyAnnotation instanceof Annotation\JobOption) $jobOptions[] = $property->getName();
            }
        }
        return $jobOptions;
    }
}
