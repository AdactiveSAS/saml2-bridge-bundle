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

class IdentityProvider extends \SAML2_Configuration_IdentityProvider
{
    /**
     * @return string
     */
    public function getSsoUrl()
    {
        return $this->get('ssoUrl');
    }

    /**
     * @return string
     */
    public function getSsoBinding(){
        return \SAML2_Const::BINDING_HTTP_REDIRECT;
    }

    /**
     * @return string
     */
    public function getSlsUrl()
    {
        return $this->get('slsUrl');
    }

    /**
     * @return string
     */
    public function getSlsBinding(){
        return \SAML2_Const::BINDING_HTTP_REDIRECT;
    }
}
