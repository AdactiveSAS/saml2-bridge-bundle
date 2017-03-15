<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Metadata;

class Metadata
{
    /**
     * @var string
     */
    public $entityId;

    /**
     * @var IdentityProviderMetadata
     */
    public $idp;

    /**
     * @var string
     */
    public $certificate;

    /**
     * @return bool
     */
    public function hasIdp()
    {
        return $this->idp !== null;
    }
}