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

namespace AdactiveSas\Saml2BridgeBundle\Entity;

class ServiceProvider extends \SAML2_Configuration_ServiceProvider
{
    /**
     * @return \DateInterval
     */
    public function getAssertionNotBeforeInterval(){
        return $this->get('assertionNotBeforeInterval', new \DateInterval('PT0S'));
    }

    /**
     * @return \DateInterval
     */
    public function getAssertionNotOnOrAfterInterval(){
        return $this->get('assertionNotOnOrAfterInterval', new \DateInterval('PT5M'));
    }

    /**
     * @return \DateInterval
     */
    public function getAssertionSessionNotOnORAfterInterval(){
        return $this->get('assertionSessionNotOnOrAfterInterval', new \DateInterval('P1D'));
    }

    /**
     * @return string|null
     */
    public function getAssertionConsumerUrl()
    {
        return $this->get('assertionConsumerUrl');
    }

    /**
     * @return string|null
     */
    public function getAssertionConsumerBinding()
    {
        return $this->get('assertionConsumerBinding');
    }

    /**
     * @return string|null
     */
    public function getSingleLogoutUrl()
    {
        return $this->get('singleLogoutUrl');
    }

    /**
     * @return string|null
     */
    public function getSingleLogoutBinding()
    {
        return $this->get('singleLogoutBinding');
    }

    /**
     * @return string|null
     */
    public function getNameIdValue(){
        return $this->get('nameIdValue');
    }

    /**
     * @return bool
     */
    public function wantSignedAuthnRequest()
    {
        return $this->get('wantSignedAuthnRequest', true);
    }

    /**
     * @return bool
     */
    public function wantSignedAuthnResponse()
    {
        return $this->get('wantSignedAuthnResponse', true);
    }

    /**
     * @return bool
     */
    public function wantSignedAssertions()
    {
        return $this->get('wantSignedAssertions', true);
    }

    /**
     * @return bool
     */
    public function wantSignedLogoutResponse()
    {
        return $this->get('wantSignedLogoutResponse', true);
    }

    /**
     * @return bool
     */
    public function wantSignedLogoutRequest()
    {
        return $this->get('wantSignedLogoutRequest', true);
    }

    /**
     * @return string|null
     */
    public function getNameIdFormat()
    {
        return $this->get('nameIdFormat', \SAML2_Const::NAMEFORMAT_BASIC);
    }

    /**
     * @return string|null
     */
    public function getAttributes()
    {
        return $this->get('attributes', []);
    }

    /**
     * @return string|null
     */
    public function getNameQualifier()
    {
        return $this->get('NameQualifier');
    }

    /**
     * @return int
     */
    public function getMaxRetryLogin()
    {
        return $this->get('maxRetryLogin', 0);
    }

    /**
     * @return array|null
     */
    public function getValidAudiences()
    {
        return $this->get('validAudiences');
    }
}
