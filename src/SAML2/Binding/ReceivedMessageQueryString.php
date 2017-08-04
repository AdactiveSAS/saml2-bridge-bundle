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

final class ReceivedMessageQueryString
{
    const PARAMETER_REQUEST = 'SAMLRequest';
    const PARAMETER_RESPONSE = 'SAMLResponse';
    const PARAMETER_SIGNATURE = 'Signature';
    const PARAMETER_SIGNATURE_ALGORITHM = 'SigAlg';
    const PARAMETER_RELAY_STATE = 'RelayState';

    protected static $samlParameters = [
        self::PARAMETER_REQUEST,
        self::PARAMETER_RESPONSE,
        self::PARAMETER_SIGNATURE,
        self::PARAMETER_SIGNATURE_ALGORITHM,
        self::PARAMETER_RELAY_STATE,
    ];

    /**
     * @var string
     */
    protected $samlMessage;

    /**
     * @var string|null
     */
    protected $signature;

    /**
     * @var string|null
     */
    protected $signatureAlgorithm;

    /**
     * @var string|null
     */
    protected $relayState;

    protected function __construct($samlMessage)
    {
        $this->samlMessage = $samlMessage;
    }
    
    /**
     * @param string $query
     * @return ReceivedMessageQueryString
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) Extensive validation
     * @SuppressWarnings(PHPMD.NPathComplexity) Extensive validation
     */
    public static function parse($query)
    {
        if (!is_string($query)) {
            throw new InvalidReceivedMessageQueryStringException(sprintf(
                'Could not parse query string: expected a non-empty string, %s given',
                is_object($query) ? get_class($query) : gettype($query)
            ));
        }

        $queryWithoutSeparator = ltrim($query, '?');

        $queryParameters = explode('&', $queryWithoutSeparator);

        $parameters = [];
        foreach ($queryParameters as $queryParameter) {
            if (!(strpos($queryParameter, '=') > 0)) {
                continue;
            }

            list($key, $value) = explode('=', $queryParameter, 2);

            if (!in_array($key, self::$samlParameters)) {
                continue;
            }

            if (array_key_exists($key, $parameters)) {
                throw new InvalidReceivedMessageQueryStringException(sprintf(
                    'Invalid ReceivedMessage query string ("%s"): parameter "%s" already present',
                    $queryWithoutSeparator,
                    $key
                ));
            }

            $parameters[$key] = urldecode($value);
        }

        $isRequestMessage = array_key_exists(self::PARAMETER_REQUEST, $parameters);
        $isResponseMessage = array_key_exists(self::PARAMETER_RESPONSE, $parameters);

        if (!$isRequestMessage && !$isResponseMessage) {
            throw new InvalidReceivedMessageQueryStringException(sprintf(
                'Invalid ReceivedMessage query string ("%s"): neither parameter "%s" nor parameter "%s" found',
                $queryWithoutSeparator,
                self::PARAMETER_REQUEST,
                self::PARAMETER_RESPONSE
            ));
        }

        if($isRequestMessage && $isResponseMessage){
            throw new InvalidReceivedMessageQueryStringException(sprintf(
                'Invalid ReceivedMessage query string ("%s"): message must be either a request or a response but found parameters "%s" and "%s"',
                $queryWithoutSeparator,
                self::PARAMETER_REQUEST,
                self::PARAMETER_RESPONSE
            ));
        }

        $encodedMessage = $isRequestMessage ? $parameters[self::PARAMETER_REQUEST] : $parameters[self::PARAMETER_RESPONSE];

        if (base64_decode($encodedMessage, true) === false) {
            throw new InvalidRequestException('Failed decoding SAML message, did not receive a valid base64 string');
        }

        $parsedQueryString = new self($encodedMessage);

        if (isset($parameters[self::PARAMETER_RELAY_STATE])) {
            $parsedQueryString->relayState = $parameters[self::PARAMETER_RELAY_STATE];
        }

        if (isset($parameters[self::PARAMETER_SIGNATURE])) {
            if (!isset($parameters[self::PARAMETER_SIGNATURE_ALGORITHM])) {
                throw new InvalidReceivedMessageQueryStringException(sprintf(
                    'Invalid ReceivedMessage query string ("%s") contains a signature but not a signature algorithm',
                    $queryWithoutSeparator
                ));
            }

            if (base64_decode($parameters[self::PARAMETER_SIGNATURE], true) === false) {
                throw new InvalidReceivedMessageQueryStringException(sprintf(
                    'Invalid ReceivedMessage query string ("%s"): signature is not base64 encoded correctly',
                    $queryWithoutSeparator
                ));
            }

            $parsedQueryString->signature = $parameters[self::PARAMETER_SIGNATURE];
            $parsedQueryString->signatureAlgorithm = $parameters[self::PARAMETER_SIGNATURE_ALGORITHM];

            return $parsedQueryString;
        }

        if (isset($parameters[self::PARAMETER_SIGNATURE_ALGORITHM])) {
            throw new InvalidReceivedMessageQueryStringException(sprintf(
                'Invalid ReceivedMessage query string ("%s") contains a signature algorithm but not a signature',
                $queryWithoutSeparator
            ));
        }

        return $parsedQueryString;
    }

    /**
     * @return string
     */
    public function getSignedQueryString()
    {
        if (!$this->isSigned()) {
            throw new LogicException(
                'Cannot get a signed query string from an unsigned ReceivedRequestQueryString'
            );
        }

        $query = self::PARAMETER_REQUEST . '=' . $this->samlMessage;

        if ($this->hasRelayState()) {
            $query .= '&' . self::PARAMETER_RELAY_STATE . '=' . $this->relayState;
        }

        $query .= '&' . self::PARAMETER_SIGNATURE_ALGORITHM . '=' . $this->signatureAlgorithm;

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
     */
    public function getDecodedSamlRequest()
    {
        $samlRequest = base64_decode($this->samlMessage, true);

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

        return $samlRequest;
    }

    /**
     * @return string
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
