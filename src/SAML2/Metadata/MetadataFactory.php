<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Metadata;


use AdactiveSas\Saml2BridgeBundle\Entity\HostedEntities;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;

class MetadataFactory
{
    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    private $templateEngine;

    /**
     * @var HostedEntities
     */
    private $hostedEntities;

    /**
     * MetadataFactory constructor.
     * @param EngineInterface $templateEngine
     * @param HostedEntities $hostedEntities
     */
    public function __construct(
        EngineInterface $templateEngine,
        HostedEntities $hostedEntities
    ) {
        $this->templateEngine = $templateEngine;
        $this->hostedEntities = $hostedEntities;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getMetadataResponse()
    {
        return $this->templateEngine->renderResponse(
            "AdactiveSasSaml2BridgeBundle:Metadata:metadata.xml.twig",
            [
                "metadata" => $this->buildMetadata()
            ]
        );
    }

    /**
     * @return Metadata
     */
    public function buildMetadata(){
        $metadata = new Metadata();

        $metadata->entityId = $this->hostedEntities->getEntityId();

        if($this->hostedEntities->hasIdentityProvider()){
            $idp = $this->hostedEntities->getIdentityProvider();

            $idpMetadata = new IdentityProviderMetadata();
            $idpMetadata->ssoUrl = $idp->getSsoUrl();
            $idpMetadata->ssoBinding = $idp->getSsoBinding();
            $idpMetadata->slsUrl = $idp->getSlsUrl();
            $idpMetadata->slsBinding = $idp->getSlsBinding();

            $metadata->idp = $idpMetadata;
        }

        return $metadata;
    }
}