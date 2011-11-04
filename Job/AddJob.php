<?php

namespace Adticket\Elvis\JobBundle\Job;

class AddJob
{
    /**
     * @var int
     * @Adticket\Elvis\JobBundle\JobOption
     */
    private $a;

    /**
     * @var int
     * @Adticket\Elvis\JobBundle\JobOption
     */
    private $b;

    public function __construct($a, $b)
    {
        $this->a = (int)$a;
        $this->b = (int)$b;
    }
}
