<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Modifications copyright (C) 2017 Adactive SAS
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

use AdactiveSas\Saml2BridgeBundle\Exception\LogicException;
use AdactiveSas\Saml2BridgeBundle\Exception\RuntimeException;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidRequestException;

final class ReceivedData
{
    const PARAMETER_REQUEST = 'SAMLRequest';
    const PARAMETER_RESPONSE = 'SAMLResponse';
    const PARAMETER_SIGNATURE = 'Signature';
    const PARAMETER_SIGNATURE_ALGORITHM = 'SigAlg';
    const PARAMETER_RELAY_STATE = 'RelayState';

    private static $samlParameters = [
        self::PARAMETER_REQUEST,
        self::PARAMETER_RESPONSE,
        self::PARAMETER_SIGNATURE,
        self::PARAMETER_SIGNATURE_ALGORITHM,
        self::PARAMETER_RELAY_STATE,
    ];

    /**
     * @var string
     */
    private $samlMessage;

    /**
     * @var string|null
     */
    private $signature;

    /**
     * @var string|null
     */
    private $signatureAlgorithm;

    /**
     * @var string|null
     */
    private $relayState;

    private function __construct($samlMessage)
    {
        $this->samlMessage = $samlMessage;
    }

    /**
     * @param array $requestParams
     * @return ReceivedData
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) Extensive validation
     * @SuppressWarnings(PHPMD.NPathComplexity) Extensive validation
     */
    public static function fromReceivedProviderData(array $requestParams)
    {
        foreach ($requestParams as $paramName => $paramValue) {
            if (!in_array($paramName, self::$samlParameters, true)) {
                unset($requestParams[$paramName]);
                continue;
            }
        }

        $isRequestMessage = array_key_exists(self::PARAMETER_REQUEST, $requestParams);
        $isResponseMessage = array_key_exists(self::PARAMETER_RESPONSE, $requestParams);

        if (!$isRequestMessage && !$isResponseMessage) {
            throw new InvalidReceivedMessageQueryStringException(sprintf(
                'Invalid ReceivedMessage query params (%s): neither parameter "%s" nor parameter "%s" found',
                json_encode($requestParams),
                self::PARAMETER_REQUEST,
                self::PARAMETER_RESPONSE
            ));
        }

        if($isRequestMessage && $isResponseMessage){
            throw new InvalidReceivedMessageQueryStringException(sprintf(
                'Invalid ReceivedMessage query ("%s"): message must be either a request or a response but found parameters "%s" and "%s"',
                json_encode($requestParams),
                self::PARAMETER_REQUEST,
                self::PARAMETER_RESPONSE
            ));
        }

        $encodedMessage = $isRequestMessage ? $requestParams[self::PARAMETER_REQUEST] : $requestParams[self::PARAMETER_RESPONSE];

        if (base64_decode($encodedMessage, true) === false) {
            throw new InvalidRequestException('Failed decoding SAML message, did not receive a valid base64 string');
        }

        $parsedQueryString = new self($encodedMessage);

        if (isset($requestParams[self::PARAMETER_RELAY_STATE])) {
            $parsedQueryString->relayState = $requestParams[self::PARAMETER_RELAY_STATE];
        }

        if (isset($requestParams[self::PARAMETER_SIGNATURE])) {
            if (!isset($requestParams[self::PARAMETER_SIGNATURE_ALGORITHM])) {
                throw new InvalidReceivedMessageQueryStringException(sprintf(
                    'Invalid ReceivedMessage query string ("%s") contains a signature but not a signature algorithm',
                    json_encode($requestParams)
                ));
            }

            if (base64_decode($requestParams[self::PARAMETER_SIGNATURE], true) === false) {
                throw new InvalidReceivedMessageQueryStringException(sprintf(
                    'Invalid ReceivedMessage query string ("%s"): signature is not base64 encoded correctly',
                    json_encode($requestParams)
                ));
            }

            $parsedQueryString->signature = $requestParams[self::PARAMETER_SIGNATURE];
            $parsedQueryString->signatureAlgorithm = $requestParams[self::PARAMETER_SIGNATURE_ALGORITHM];

            return $parsedQueryString;
        }

        if (isset($requestParams[self::PARAMETER_SIGNATURE_ALGORITHM])) {
            throw new InvalidReceivedMessageQueryStringException(sprintf(
                'Invalid ReceivedMessage query string ("%s") contains a signature algorithm but not a signature',
                json_encode($requestParams)
            ));
        }

        return $parsedQueryString;
    }

    /**
     * @return string
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\LogicException
     */
    public function getSignedQueryString()
    {
        if (!$this->isSigned()) {
            throw new LogicException(
                'Cannot get a signed query string from an unsigned ReceivedRequestQueryString'
            );
        }

        $query = self::PARAMETER_REQUEST . '=' . urlencode($this->samlMessage);

        if ($this->hasRelayState()) {
            $query .= '&' . self::PARAMETER_RELAY_STATE . '=' . urlencode($this->relayState);
        }

        $query .= '&' . self::PARAMETER_SIGNATURE_ALGORITHM . '=' . urlencode($this->signatureAlgorithm);

        return $query;
    }

    /**
     * @return bool
     */
    public function hasRelayState()
    {
        return $this->relayState !== null;
    }

    /**
     * @return string
     * @throws \AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidRequestException
     */
    public function getDecodedSamlRequest()
    {
        $samlRequest = base64_decode($this->samlMessage, true);

        if(substr($samlRequest, 0, 7) !== "<samlp:") {
            // Catch any errors gzinflate triggers
            $errorNo = $errorMessage = null;
            set_error_handler(function ($number, $message) use (&$errorNo, &$errorMessage) {
                $errorNo      = $number;
                $errorMessage = $message;
            });
            $samlRequest = gzinflate($samlRequest);
            restore_error_handler();

            if ($samlRequest === false) {
                throw new InvalidRequestException(sprintf(
                    'Failed inflating SAML Request; error "%d": "%s"',
                    $errorNo,
                    $errorMessage
                ));
            }
        }

        return $samlRequest;
    }

    /**
     * @return string
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\RuntimeException
     */
    public function getDecodedSignature()
    {
        if (!$this->isSigned()) {
            throw new RuntimeException('Cannot decode signature: SAMLRequest is not signed');
        }

        return base64_decode($this->signature, true);
    }

    /**
     * @return null|string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @return bool
     */
    public function isSigned()
    {
        return $this->signature !== null && $this->signatureAlgorithm !== null;
    }

    /**
     * @return string
     */
    public function getSamlMessage()
    {
        return $this->samlMessage;
    }

    /**
     * @return null|string
     */
    public function getSignatureAlgorithm()
    {
        return $this->signatureAlgorithm;
    }

    /**
     * @return null|string
     */
    public function getRelayState()
    {
        return $this->relayState;
    }
}
