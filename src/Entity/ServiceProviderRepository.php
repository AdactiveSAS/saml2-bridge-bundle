<?php

namespace AdactiveSas\Saml2BridgeBundle\Entity;

interface ServiceProviderRepository
{
    /**
     * @param string $entityId
     * @return ServiceProvider
     */
    public function getServiceProvider($entityId);

    /**
     * @param string $entityId
     * @return bool
     */
    public function hasServiceProvider($entityId);
}
