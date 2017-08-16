<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional\Bundle\AcmeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SamlController extends Controller
{
    public function singleSignOnAction(Request $httpRequest)
    {
        $idpProcessor = $this->get("adactive_sas_saml2_bridge.processor.hosted_idp");

        return $idpProcessor->processSingleSignOn($httpRequest);
    }

    public function singleLogoutAction(Request $httpRequest)
    {
        $idpProcessor = $this->get("adactive_sas_saml2_bridge.processor.hosted_idp");

        return $idpProcessor->processSingleLogoutService($httpRequest);
    }

    public function metadataAction()
    {
        $idpProcessor = $this->get("adactive_sas_saml2_bridge.processor.hosted_idp");

        return $idpProcessor->getMetadataXmlResponse();
    }
}