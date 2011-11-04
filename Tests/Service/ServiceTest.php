<?php

namespace Adticket\Elvis\JobBundle\Tests\Controller;

use Adticket\Elvis\JobBundle\Job;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @todo FIXME Actually, run this test. See http://kriswallsmith.net/post/1338263070/how-to-test-a-symfony2-bundle
 */
class ServiceTest extends WebTestCase
{
    private $client;

    public function testAdd()
    {
        $this->client = static::createClient();
        $server = $this->getServer();
        $server->addJob('adticket_elvis_job.job.add', array('a' => 1, 'b' => 2));
    }

    /**
     * @return \Adticket\Elvis\JobBundle\Server
     */
    public function getServer()
    {
        return $this->client->get('adticket_elvis_job.server');
    }
}
