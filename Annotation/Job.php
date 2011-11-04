<?php

namespace Adticket\Elvis\JobBundle\Job;

interface Job {
    function run($action, array $options = null);

    /**
     * @abstract
     * @return string
     */
    function getServiceId();
}
