<?php

namespace Adticket\Sf2BundleOS\Elvis\JobBundle;

class Job
{
    const STATUS_RUNNING = 0;
    const STATUS_COMPLETE = 1;
    const STATUS_FAILED = 2;
    const STATUS_TIMEOUT = 3;

    protected $started;
    protected $service;
    protected $data;
    protected $status;
    protected $message;

    public function setService($service)
    {
        $this->started = new \DateTime();
        $this->service = $service;
    }

    public function getService()
    {
        return $this->service;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function sendStatus()
    {

    }

    public function sendComplete()
    {

    }
}
