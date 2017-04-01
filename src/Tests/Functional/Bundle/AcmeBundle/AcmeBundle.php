<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional\Bundle\AcmeBundle;


use AdactiveSas\Saml2BridgeBundle\Tests\Functional\Bundle\AcmeBundle\DependencyInjection\AcmeBundleExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AcmeBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new AcmeBundleExtension();
    }
}