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


class LogoutRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var \SAML2_LogoutRequest
     */
    protected $request;

    /**
     * @return \SAML2_LogoutRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return void
     */
    protected function createRequestInstance()
    {
        $this->request = new \SAML2_LogoutRequest();
    }

    /**
     * @param $value
     * @param $format
     * @return $this
     */
    public function setNameId($value, $format) {
        $this->request->setNameId([
            "Value" => $value,
            "Format" => $format
        ]);

        return $this;
    }
}