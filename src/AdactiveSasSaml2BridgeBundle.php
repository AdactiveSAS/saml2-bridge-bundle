<?php

namespace AdactiveSas\Saml2BridgeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AdactiveSasSaml2BridgeBundle extends Bundle
{
    public function boot()
    {
        $bridgeContainer = $this->container->get('adactive_sas_saml2_bridge.container');
        \SAML2_Compat_ContainerSingleton::setContainer($bridgeContainer);
    }
}
