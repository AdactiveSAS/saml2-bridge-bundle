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
    public function getSingleLogoutUrl(){
        return $this->get('singleLogoutUrl');
    }

    /**
     * @return string|null
     */
    public function getSingleLogoutBinding(){
        return $this->get('singleLogoutBinding');
    }

    public function wantSignedAuthnResponse()
    {
        return $this->get('wantSignedAuthnResponse', true);
    }

    public function wantSignedAssertions()
    {
        return $this->get('wantSignedAssertions', true);
    }

    public function wantSignedLogoutResponse()
    {
        return $this->get('wantSignedLogoutResponse', true);
    }

    public function wantSignedLogoutRequest()
    {
        return $this->get('wantSignedLogoutRequest', true);
    }
}
