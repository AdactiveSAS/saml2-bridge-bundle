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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractHttpBinding implements HttpBindingInterface
{
    /**
     * Validate the signature.
     *
     * Throws an exception if we are unable to validate the signature.
     *
     * @param ReceivedData $query g.
     * @param \XMLSecurityKey $key The key we should validate the query against.
     * @throws BadRequestHttpException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\LogicException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\RuntimeException
     */
    public static function validateSignature(ReceivedData $query, \XMLSecurityKey $key)
    {
        $algo = urldecode($query->getSignatureAlgorithm());

        if ($key->getAlgorithm() !== $algo) {
            $key = \SAML2_Utils::castKey($key, $algo);
        }

        if (!$key->verifySignature($query->getSignedQueryString(), $query->getDecodedSignature())) {
            throw new BadRequestHttpException(
                'The SAMLRequest has been signed, but the signature could not be validated'
            );
        }
    }

    /**
     * @param \SAML2_Request $request
     * @return Response
     * @throws \InvalidArgumentException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\LogicException
     */
    public function getSignedRequest(\SAML2_Request $request)
    {
        $destination = $request->getDestination();
        if($destination === null){
            throw new LogicException('Invalid destination');
        }

        $securityKey = $request->getSignatureKey();
        if($securityKey === null){
            throw new LogicException('Signature key is required');
        }

        $requestAsXml = $request->toUnsignedXML()->ownerDocument->saveXML();
        $encodedRequest = base64_encode(gzdeflate($requestAsXml));
        $relayState = $request->getRelayState();

        return $this->buildRequest($destination, $encodedRequest, $relayState, $request->getSignatureKey());
    }

    /**
     * @param string $destination
     * @param string $encodedRequest
     * @param string $relayState
     * @param \XMLSecurityKey $signatureKey
     * @return Response
     */
    abstract protected function buildRequest($destination, $encodedRequest, $relayState, \XMLSecurityKey $signatureKey);

    /**
     * @param Request $request
     * @return \SAML2_AuthnRequest
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException
     */
    public function receiveSignedAuthnRequest(Request $request){
        $message = $this->receiveSignedMessage($request);

        if (!$message instanceof \SAML2_AuthnRequest) {
            throw new InvalidArgumentException(sprintf(
                'The received request is not an AuthnRequest, "%s" received instead',
                substr(get_class($message), strrpos($message, '_') + 1)
            ));
        }

        return $message;
    }

    /**
     * @param Request $request
     * @return \SAML2_LogoutRequest
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException
     */
    public function receiveSignedLogoutRequest(Request $request){
        $message = $this->receiveSignedMessage($request);

        if (!$message instanceof \SAML2_LogoutRequest) {
            throw new InvalidArgumentException(sprintf(
                'The received request is not an LogoutRequest, "%s" received instead',
                substr(get_class($message), strrpos($message, '_') + 1)
            ));
        }

        return $message;
    }

    /**
     * @param Request $request
     * @return \SAML2_LogoutResponse
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException
     */
    public function receiveSignedLogoutResponse(Request $request){
        $message = $this->receiveSignedMessage($request);

        if (!$message instanceof \SAML2_LogoutResponse) {
            throw new InvalidArgumentException(sprintf(
                'The received request is not an LogoutRequest, "%s" received instead',
                substr(get_class($message), strrpos($message, '_') + 1)
            ));
        }

        return $message;
    }

    /**
     * @param Request $request
     * @return \SAML2_AuthnRequest
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException
     */
    public function receiveUnsignedAuthnRequest(Request $request){
        $message = $this->receiveUnsignedMessage($request);

        if (!$message instanceof \SAML2_AuthnRequest) {
            throw new InvalidArgumentException(sprintf(
                'The received request is not an AuthnRequest, "%s" received instead',
                substr(get_class($message), strrpos($message, '_') + 1)
            ));
        }

        return $message;
    }

    /**
     * @param Request $request
     * @return \SAML2_LogoutRequest
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException
     */
    public function receiveUnsignedLogoutRequest(Request $request){
        $message = $this->receiveUnsignedMessage($request);

        if (!$message instanceof \SAML2_LogoutRequest) {
            throw new InvalidArgumentException(sprintf(
                'The received request is not an LogoutRequest, "%s" received instead',
                substr(get_class($message), strrpos(get_class($message), '_') + 1)
            ));
        }

        return $message;
    }

    /**
     * @param Request $request
     * @return \SAML2_LogoutResponse
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException
     */
    public function receiveUnsignedLogoutResponse(Request $request){
        $message = $this->receiveUnsignedMessage($request);

        if (!$message instanceof \SAML2_LogoutResponse) {
            throw new InvalidArgumentException(sprintf(
                'The received request is not an LogoutRequest, "%s" received instead',
                substr(get_class($message), strrpos(get_class($message), '_') + 1)
            ));
        }

        return $message;
    }

    /**
     * @param Request $request
     * @return \SAML2_Message
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException
     */
    public function receiveSignedMessage(Request $request)
    {
        $query = $this->getReceivedData($request);

        if (!$query->isSigned()) {
            throw new BadRequestHttpException('The SAMLRequest is expected to be signed but it was not');
        }

        $message = $this->getReceivedSamlMessageFromRecivedData($query, $request);

        $message->addValidator(array(get_class($this), 'validateSignature'), $query);

        return $message;
    }

    /**
     * @param Request $request
     * @return \SAML2_Message
     */
    public function receiveUnsignedMessage(Request $request)
    {
        return $this->getReceivedSamlMessageFromRecivedData($this->getReceivedData($request), $request);
    }

    /**
     * @param Request $request
     * @return ReceivedData
     * @throws \AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException
     */
    abstract protected function getReceivedData(Request $request);


    /**
     * @param ReceivedData $query
     * @param Request $request
     * @return \SAML2_Message
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException
     */
    protected function getReceivedSamlMessageFromRecivedData(ReceivedData $query, Request $request)
    {
        $decodedSamlRequest = $query->getDecodedSamlRequest();

        if (!is_string($decodedSamlRequest) || empty($decodedSamlRequest)) {
            throw new InvalidArgumentException(sprintf(
                'Could not create ReceivedMessage: expected a non-empty string, received %s',
                is_object($decodedSamlRequest) ? get_class($decodedSamlRequest) : ($decodedSamlRequest)
            ));
        }

        // additional security against XXE Processing vulnerability
        $previous = libxml_disable_entity_loader(true);
        $document = \SAML2_DOMDocumentFactory::fromString($decodedSamlRequest);
        libxml_disable_entity_loader($previous);

        $message = \SAML2_Message::fromXML($document->firstChild);

        if (null === $message->getRelayState()) {
            $message->setRelayState($query->getRelayState());
        }

        $currentUri = $this->getFullRequestUri($request);
        if (!$message->getDestination() === $currentUri) {
            throw new BadRequestHttpException(sprintf(
                'Actual Destination "%s" does not match the Request Destination "%s"',
                $currentUri,
                $message->getDestination()
            ));
        }

        return $message;
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getFullRequestUri(Request $request)
    {
        return $request->getSchemeAndHttpHost() . $request->getBasePath() . $request->getRequestUri();
    }
}
