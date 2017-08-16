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


class LogoutResponseBuilder extends AbstractResponseBuilder
{
    /**
     * @var \SAML2_LogoutResponse
     */
    protected $response;

    /**
     * @return \SAML2_LogoutResponse
     */
    public function getResponse()
    {
        $key = $this->getSignatureKey();
        if($key !== null){
            $this->response->setSignatureKey($key);
        }

        return $this->response;
    }

    /**
     * @return void
     */
    protected function createResponseInstance()
    {
        $this->response = new \SAML2_LogoutResponse();
    }
}