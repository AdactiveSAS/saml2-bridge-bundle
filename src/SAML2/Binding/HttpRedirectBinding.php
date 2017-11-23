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

use AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException;
use AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException;
use AdactiveSas\Saml2BridgeBundle\Exception\LogicException;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\UnsupportedBindingException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpRedirectBinding extends AbstractHttpBinding implements HttpBindingInterface
{
    /**
     * @param \SAML2_StatusResponse $response
     * @return RedirectResponse
     * @throws \InvalidArgumentException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\LogicException
     */
    public function getSignedResponse(\SAML2_StatusResponse $response)
    {
        $destination = $response->getDestination();
        if($destination === null){
            throw new LogicException('Invalid destination');
        }

        $securityKey = $response->getSignatureKey();
        if($securityKey === null){
            throw new LogicException('Signature key is required');
        }

        $responseAsXml = $response->toUnsignedXML()->ownerDocument->saveXML();
        $encodedResponse = base64_encode(gzdeflate($responseAsXml));

        /* Build the query string. */

        $msg = 'SAMLResponse=' . urlencode($encodedResponse);

        if ($response->getRelayState() !== NULL) {
            $msg .= '&RelayState=' . urlencode($response->getRelayState());
        }

        /* Add the signature. */
        $msg .= '&SigAlg=' . urlencode($securityKey->type);

        $signature = $securityKey->signData($msg);
        $msg .= '&Signature=' . urlencode(base64_encode($signature));

        if (strpos($destination, '?') === FALSE) {
            $destination .= '?' . $msg;
        } else {
            $destination .= '&' . $msg;
        }

        return new RedirectResponse($destination);
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @return RedirectResponse
     * @throws \InvalidArgumentException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\LogicException
     */
    public function getUnsignedResponse(\SAML2_StatusResponse $response)
    {
        $destination = $response->getDestination();
        if($destination === null){
            throw new LogicException('Invalid destination');
        }

        $responseAsXml = $response->toUnsignedXML()->ownerDocument->saveXML();
        $encodedResponse = base64_encode(gzdeflate($responseAsXml));

        /* Build the query string. */

        $msg = 'SAMLResponse=' . urlencode($encodedResponse);

        if ($response->getRelayState() !== NULL) {
            $msg .= '&RelayState=' . urlencode($response->getRelayState());
        }

        if (strpos($destination, '?') === FALSE) {
            $destination .= '?' . $msg;
        } else {
            $destination .= '&' . $msg;
        }

        return new RedirectResponse($destination);
    }

    protected function buildRequest($destination, $encodedRequest, $relayState, \XMLSecurityKey $signatureKey)
    {
        $msg = 'SAMLRequest=' . urlencode($encodedRequest);

        if ($relayState !== NULL) {
            $msg .= '&RelayState=' . urlencode($relayState);
        }

        /* Add the signature. */
        $msg .= '&SigAlg=' . urlencode($signatureKey->type);

        $signature = $signatureKey->signData($msg);
        $msg .= '&Signature=' . urlencode(base64_encode($signature));

        if (strpos($destination, '?') === FALSE) {
            $destination .= '?' . $msg;
        } else {
            $destination .= '&' . $msg;
        }

        return new RedirectResponse($destination);
    }

    /**
     * @param \SAML2_Request $request
     * @return Response
     * @throws \AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\UnsupportedBindingException
     */
    public function getUnsignedRequest(\SAML2_Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned REDIRECT Request is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return ReceivedData
     * @throws \AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException
     */
    protected function getReceivedData(Request $request)
    {
        if (!$request->isMethod(Request::METHOD_GET)) {
            throw new BadRequestHttpException(sprintf(
                'Could not receive Message from HTTP Request: expected a GET method, got %s',
                $request->getMethod()
            ));
        }

        $requestParams = $request->query->all();

        return ReceivedData::fromReceivedProviderData($requestParams);
    }
}
