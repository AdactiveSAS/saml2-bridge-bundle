<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional\Bundle\AcmeBundle\Controller;


use Symfony\Component\HttpFoundation\Response;

class DefaultController
{
    public function helloAction(){
        return new Response("Hello !");
    }
}