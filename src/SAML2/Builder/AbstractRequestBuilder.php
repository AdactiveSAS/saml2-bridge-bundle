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

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Builder;


abstract class AbstractRequestBuilder
{
    private $issueInstant;

    /**
     * @return void
     */
    abstract protected function createRequestInstance();

    /**
     * @return \SAML2_Request
     */
    abstract public function getRequest();

    /**
     * AuthnResponseFactory constructor.
     * @param \DateTime|null $issueInstant
     */
    public function __construct(\DateTime $issueInstant = null)
    {
        $this->createRequestInstance();

        $this->issueInstant = $issueInstant === null ? new \DateTime('now', new \DateTimeZone('UTC')): $issueInstant;

        $this->getRequest()->setIssueInstant($this->issueInstant->getTimestamp());
    }

    /**
     * @return \DateTime|null
     */
    public function getIssueInstant(){
        return $this->issueInstant;
    }

    /**
     * @param $issuer
     * @return $this
     */
    public function setIssuer($issuer){
        $this->getRequest()->setIssuer($issuer);

        return $this;
    }

    /**
     * @param $destination
     * @return $this
     */
    public function setDestination($destination){
        $this->getRequest()->setDestination($destination);

        return $this;
    }

    /**
     * @param \XMLSecurityKey $key
     * @return $this
     */
    public function setSignatureKey(\XMLSecurityKey $key){
        $this->getRequest()->setSignatureKey($key);

        return $this;
    }

    /**
     * @param $relayState
     * @return $this
     */
    public function setRelayState($relayState){
        $this->getRequest()->setRelayState($relayState);

        return $this;
    }
}