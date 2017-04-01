<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional;

class AcmeBundleTest extends WebTestCase
{
    public function testBoot(){
        $kernel = $this->createKernel(array('test_case' => 'AcmeBundle'));

        $kernel->boot();
    }
}