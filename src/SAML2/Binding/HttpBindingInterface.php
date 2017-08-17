<?php

/**
 * Copyright 2017 Adactive SAS
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
     * @return \SAML2_AuthnRequest
     */
    public function receiveAuthnRequest(Request $request);

    /**
     * @param Request $request
     * @return \SAML2_LogoutRequest
     */
    public function receiveSignedLogoutRequest(Request $request);

    /**
     * @param Request $request
     * @return \SAML2_AuthnRequest
     */
    public function receiveUnsignedAuthnRequest(Request $request);

    /**
     * @param Request $request
     * @return \SAML2_LogoutRequest
     */
    public function receiveUnsignedLogoutRequest(Request $request);

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
