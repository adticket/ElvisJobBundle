<?php

namespace Adticket\Elvis\JobBundle;

/**
 * Repräsentiert eine JobAnfrage
 */
abstract class JobRequest
{
    /**
     * @var string ID des Services, für den dieser Job ist
     */
    private $service;

    /**
     * @var string Aktion, die ausgeführt werden soll
     */
    private $action;

    /**
     * @var array Map mit Optionen für den Job
     */
    private $options;

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }
}
