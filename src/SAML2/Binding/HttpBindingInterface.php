<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Binding;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface HttpBindingInterface
{
    /**
     * @param \SAML2_StatusResponse $response
     * @return Response
     */
    public function getSignedResponse(\SAML2_StatusResponse $response);

    /**
     * @param \SAML2_StatusResponse $response
     * @return Response
     */
    public function getUnsignedResponse(\SAML2_StatusResponse $response);

    /**
     * @param \SAML2_Request $request
     * @return Response
     */
    public function getSignedRequest(\SAML2_Request $request);

    /**
     * @param \SAML2_Request $request
     * @return Response
     */
    public function getUnsignedRequest(\SAML2_Request $request);

    /**
     * @param Request $request
     * @return \SAML2_AuthnRequest
     */
    public function receiveSignedAuthnRequest(Request $request);

    /**
     * @param Request $request
     * @return \SAML2_LogoutRequest
     */
    public function receiveSignedLogoutRequest(Request $request);

    /**
     * @param Request $request
     * @return \SAML2_Message
     */
    public function receiveSignedMessage(Request $request);

    /**
     * @param Request $request
     * @return \SAML2_Message
     */
    public function receiveUnsignedMessage(Request $request);
}