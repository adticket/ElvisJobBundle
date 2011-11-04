<?php

namespace Adticket\Elvis\JobBundle;

use \Symfony\Component\DependencyInjection;

/**
 * Repräsentiert einen Jobserver
 */
class Server extends ContainerAware
{
    public function addJob($serviceId, array $options)
    {
        $job = $this->container->get($serviceId);
        foreach($options as $k => $v) {
            $setter = 'set' . ucfirst($k);
            $job->$setter($v);
        }
        // FIXME: Liste der Server konfigurieren
        $gclient = new \GearmanClient();
        $gclient->addServer();
        $gclient->addTaskBackground($serviceId, $this->getSerializedOptions($job));
    }

    protected function getSerializedOptions(Object $job)
    {
        $annotationReader = $this->container->get('annotation_reader');
        $refClass = new \ReflectionClass(get_class($job));
        foreach($refClass->getMethods() as $method) {
            $methodAnnotations = $annotationReader->getMethodAnnotation($method);
            print_r($methodAnnotations);
        }
    }
}
