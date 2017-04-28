<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Binding;


use AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException;
use AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException;
use AdactiveSas\Saml2BridgeBundle\Exception\LogicException;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\UnsupportedBindingException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpRedirectBinding implements HttpBindingInterface
{
    /**
     * Validate the signature on a HTTP-Redirect message.
     *
     * Throws an exception if we are unable to validate the signature.
     *
     * @param ReceivedMessageQueryString $query g.
     * @param \XMLSecurityKey $key The key we should validate the query against.
     * @throws BadRequestHttpException
     */
    public static function validateSignature(ReceivedMessageQueryString $query, \XMLSecurityKey $key)
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
     * @param \SAML2_StatusResponse $response
     * @return RedirectResponse
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

    /**
     * @param \SAML2_Request $request
     * @return Response
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

        /* Build the query string. */

        $msg = 'SAMLRequest=' . urlencode($encodedRequest);

        if ($request->getRelayState() !== NULL) {
            $msg .= '&RelayState=' . urlencode($request->getRelayState());
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
     * @param \SAML2_Request $request
     * @return Response
     */
    public function getUnsignedRequest(\SAML2_Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned REDIRECT Request is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return \SAML2_AuthnRequest
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
     * @return \SAML2_AuthnRequest
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
     */
    public function receiveUnsignedLogoutRequest(Request $request){
        $message = $this->receiveUnsignedMessage($request);

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
     * @return \SAML2_Message
     */
    public function receiveSignedMessage(Request $request)
    {
        $query = $this->getReceivedMessageQueryString($request);

        if (!$query->isSigned()) {
            throw new BadRequestHttpException('The SAMLRequest is expected to be signed but it was not');
        }

        $message = $this->getReceivedSamlMessageFromQuery($query, $request);

        $message->addValidator(array(get_class($this), 'validateSignature'), $query);

        return $message;
    }

    /**
     * @param Request $request
     * @return \SAML2_Message
     */
    public function receiveUnsignedMessage(Request $request)
    {
        return $this->getReceivedSamlMessageFromQuery($this->getReceivedMessageQueryString($request), $request);
    }

    /**
     * @param Request $request
     * @return ReceivedMessageQueryString
     */
    protected function getReceivedMessageQueryString(Request $request)
    {
        if (!$request->isMethod(Request::METHOD_GET)) {
            throw new BadRequestHttpException(sprintf(
                'Could not receive Message from HTTP Request: expected a GET method, got %s',
                $request->getMethod()
            ));
        }

        $requestUri = $request->getRequestUri();
        if (strpos($requestUri, '?') === false) {
            throw new BadRequestHttpException(
                'Could not receive Message from HTTP Request: expected query parameters, none found'
            );
        }

        list(, $rawQueryString) = explode('?', $requestUri);

        return ReceivedMessageQueryString::parse($rawQueryString);
    }

    /**
     * @param ReceivedMessageQueryString $query
     * @param Request $request
     * @return \SAML2_Message
     */
    protected function getReceivedSamlMessageFromQuery(ReceivedMessageQueryString $query, Request $request)
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