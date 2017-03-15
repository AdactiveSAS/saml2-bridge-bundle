<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception;

use AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException;

class UnknownServiceProviderException extends BadRequestHttpException
{
    public function __construct($entityId)
    {
        parent::__construct(sprintf(
            'Request received from ServiceProvider with an unknown EntityId: "%s"',
            $entityId
        ));
    }
}
