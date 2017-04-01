<?php

return [
    new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
    new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
    new \Symfony\Bundle\TwigBundle\TwigBundle(),
    new \AdactiveSas\Saml2BridgeBundle\AdactiveSasSaml2BridgeBundle(),
    new \AdactiveSas\Saml2BridgeBundle\Tests\Functional\Bundle\AcmeBundle\AcmeBundle()
];